<?php


namespace Released\ApiCallerBundle\Service\Factory;


use JMS\Serializer\SerializerInterface;
use Released\ApiCallerBundle\Exception\ApiCallerException;
use Released\ApiCallerBundle\Transport\TransportInterface;
use Released\ApiCallerBundle\Service\ApiCaller;
use Released\ApiCallerBundle\Service\ApiCallerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ApiCallerFactory
{

    /** @var TransportInterface */
    protected $transport;

    /** @var array */
    protected $cases;

    /** @var ApiCallerInterface[] */
    protected $instances = [];

    /** @var SerializerInterface */
    protected $serializer;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(TransportInterface $transport, SerializerInterface $serializer, EventDispatcherInterface $eventDispatcher, array $cases)
    {
        $this->transport = $transport;
        $this->cases = $cases;

        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $case
     * @param TransportInterface|null $transport
     * @return ApiCallerInterface
     */
    public function createApiCaller(string $case, TransportInterface $transport = null)
    {
        $this->checkCase($case);

        if (!isset($this->instances[$case])) {
            $caseConfig = $this->cases[$case];

            $instance = new ApiCaller($case, $transport ?? $this->transport, $this->serializer, $this->eventDispatcher, $caseConfig['domain'], $caseConfig['endpoints']);
            $this->instances[$case] = $instance;
        }

        return $this->instances[$case];
    }

    /**
     * @param string $case
     * @throws ApiCallerException
     */
    protected function checkCase(string $case)
    {
        if (!isset($this->cases[$case])) {
            throw new ApiCallerException("ApiCaller case '{$case}' is not defined.");
        }
    }
}