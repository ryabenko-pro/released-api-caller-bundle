<?php


namespace Released\ApiCallerBundle\Service;


use JMS\Serializer\SerializerInterface as JmsSerializerInterface;
use PHPUnit\Framework\ExpectationFailedException;
use Released\ApiCallerBundle\Event\RequestEvent;
use Released\ApiCallerBundle\Exception\ApiCallerException;
use Released\ApiCallerBundle\Exception\ApiResponseException;
use Released\ApiCallerBundle\Service\Util\ApiCallerConfig;
use Released\ApiCallerBundle\Service\Util\ApiCallerListenerInterface;
use Released\ApiCallerBundle\Transport\TransportInterface;
use Released\ApiCallerBundle\Transport\TransportResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiCaller implements ApiCallerInterface
{

    /** @var string */
    private $caseName;
    /** @var ApiCallerConfig[] */
    private $apis;
    /** @var string */
    private $domain;
    /** @var TransportInterface */
    private $transport;
    /** @var JmsSerializerInterface */
    private $serializer;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    function __construct(
        string $caseName,
        TransportInterface $transport,
        JmsSerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        $domain,
        $apis
    ) {
        $this->caseName = $caseName;
        $this->transport = $transport;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;

        $this->domain = rtrim($domain, "/");

        foreach ($apis as $key => $api) {
            $this->apis[$key] = new ApiCallerConfig(
                $api['name'],
                $api['path'],
                $api['params'] ?? [],
                $api['method'] ?? 'GET',
                $api['headers'] ?? null,
                // TODO: space for request class
                $api['response_class'] ?? null,
                $api['response_format'] ?? null
            );
        }
    }

    /**
     * {@inheritdoc}
     * @throws ExceptionInterface
     */
    public function makeRequest($api, $values = [], ApiCallerListenerInterface $listener = null, $headers = null, $domain = null): TransportResponse
    {
        if (is_object($values)) {
            // Easiest way to convert object into array respecting serialization rules
            $values = json_decode($this->serializer->serialize($values, "json"), true);
        }

        if (null === $values) {
            $values = [];
        }

        $config = $this->checkApi($api, $values);

        $values = $this->cleanValues($values);

        $path = $config->buildPath($api, $values, $config->getParams());
        $data = $config->filterParams($values);
        $files = $config->filterFiles($values);
        $headers = $config->mergeHeaders($headers);

        if ($this->eventDispatcher->hasListeners(RequestEvent::class)) {
            $event = new RequestEvent($this->caseName, $api, $config, $values, $path, $data, $files, $headers);
            $this->eventDispatcher->dispatch($event);

            $path = $event->getPath();
            $data = $event->getData();
            $files = $event->getFiles();
            $headers = $event->getHeaders();
        }

        $url = ($domain ?? $this->domain) . $path;
        $method = $config->getMethod();
        try {
            $result = $this->transport->request($url, $method, $data, $headers, null, $files);
        } catch (ExpectationFailedException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $result = new TransportResponse("Exception: " . $exception->getMessage(), $exception->getCode()); ;
        }

        if (!is_null($listener)) {
            $listener->onRequest($url, $data, $result->getContent(), $result->getStatus(), $method);
        }

        if (!$result->isOk()) {
            throw new ApiResponseException($result, "Response status is " . $result->getStatus() . "; " . $result->getContent());
        }

        if (!is_null($config->getResponseClass())) {
            $result = new TransportResponse($this->serializer->deserialize(
                $result->getArrayContentAsString(),
                $config->getResponseClass(),
                $config->getResponseFormat() ?: 'json')
            );
        }

        return $result;
    }

    /**
     * @param string|array $api
     * @param array $values
     * @return ApiCallerConfig
     * @throws ApiCallerException
     */
    private function checkApi($api, array $values): ApiCallerConfig
    {
        if (is_array($api)) {
            $apiName = $api[0];
            $pathParams = $api[1];
        } else {
            $apiName = $api;
            $pathParams = [];
        }

        if (!isset($this->apis[$apiName])) {
            throw new ApiCallerException("Api '{$apiName}' does not exists");
        }

        $config = $this->apis[$apiName];

        $notExistingParams = [];
        $params = $config->getParams();
        foreach ($params as $key => $param) {
            if (!isset($values[$key]) && !isset($param['value'])) {
                $notExistingParams[] = $key;
            }

            if (isset($param['class']) && !is_a($values[$key], $param['class'])) {
                throw new ApiCallerException(sprintf("Param '%s' should be instance of '%s'.", $key, $param['class']));
            }
        }

        foreach ($config->getPathParams() as $key => $param) {
            if (!isset($values[$key]) && !isset($params[$key]) && !isset($pathParams[$key])) {
                $notExistingParams[] = $key;
            }
        }

        if (!empty($notExistingParams)) {
            throw new ApiCallerException("Not enough parameters: " . implode(", ", $notExistingParams));
        }

        return $config;
    }

    /**
     * Serialize objects
     *
     * @param array $values
     * @return array
     * @throws ExceptionInterface
     */
    private function cleanValues(array $values): array
    {
        $values = $values ?? [];

        foreach ($values as $key => $value) {
            if (is_object($value)) {
                $normalizer = new GetSetMethodNormalizer();

                $serializer = new Serializer(array($normalizer));
                $normalizer->setSerializer($serializer);

                $values[$key] = $normalizer->normalize($value);
            }
        }

        return $values;
    }

}