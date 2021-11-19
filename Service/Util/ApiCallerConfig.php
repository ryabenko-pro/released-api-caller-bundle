<?php


namespace Released\ApiCallerBundle\Service\Util;


use Released\ApiCallerBundle\Exception\ApiCallerException;

class ApiCallerConfig
{

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    protected $name;
    protected $path;
    protected $method;
    protected $params;
    protected $headers;
    protected $pathParams;

    /**
     * If set - response will be casted to this class
     * @var string
     */
    protected $responseClass;
    protected $responseFormat;

    function __construct($name, $path, $params = [], $method = self::METHOD_GET, $headers = null, $responseClass = null, $responseFormat = null)
    {
        $this->name = $name;
        $this->path = $path;

        $this->params = $this->cleanParams($params);
        $this->pathParams = $this->parsePathParams($path);

        $this->method = $method;

        $this->headers = $headers;

        $this->responseClass = $responseClass;
        $this->responseFormat = $responseFormat;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getPathParams()
    {
        return $this->pathParams;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getResponseClass()
    {
        return $this->responseClass;
    }

    /**
     * @return string
     */
    public function getResponseFormat()
    {
        return $this->responseFormat;
    }

    /**
     * @param string|array $api
     * @param array $values
     * @param array $params
     * @return string
     */
    public function buildPath($api, array $values, array $params)
    {
        $apiPathValues = is_array($api) ? $api[1] : [];

        $replace = [];
        foreach ($this->pathParams as $key => $value) {
            if (isset($apiPathValues[$key])) {
                $replace[sprintf('{%s}', $key)] = urlencode($apiPathValues[$key]);
            } else if (isset($values[$key])) {
                $replace[sprintf('{%s}', $key)] = urlencode($values[$key]);
            } else if (isset($params[$key]) && isset($params[$key]['value'])) {
                $replace[sprintf('{%s}', $key)] = urlencode($params[$key]['value']);
            }
        }

        return strtr($this->path, $replace);
    }

    /**
     * @param array $values
     * @return array
     */
    public function filterParams($values)
    {
        $result = [];

        foreach ($this->params as $key => $param) {
            if (isset($param['value'])) {
                $result[$key] = $param['value'];
            }
        }

        foreach ($values as $key => $value) {
            if (isset($this->pathParams[$key]) && !isset($this->params[$key])) {
                continue;
            }

            if (isset($this->params[$key]) && 'file' == $this->params[$key]['type']) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param $values
     * @return array
     */
    public function filterFiles($values)
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (isset($this->params[$key]) && 'file' == $this->params[$key]['type']) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param $path
     * @return array
     * @throws ApiCallerException
     */
    private function parsePathParams($path)
    {
        $result = [];

        preg_match_all('/\{(\w*)\}/', $path, $matches);
        foreach ((array)$matches[1] as $match) {
            if (isset($this->params[$match]) && 'file' == $this->params[$match]['type']) {
                throw new ApiCallerException("Path param '{$match}' overrides same 'file' param");
            }

            $result[$match] = $this->normalizeParam(null);
        }

        return $result;
    }

    /**
     * @param array $params
     * @return array
     */
    private function cleanParams($params)
    {
        $result = [];
        foreach ($params as $key => $param) {
            $result[$key] = $this->normalizeParam($param);
        }

        return $result;
    }

    /**
     * @param $value
     * @return array
     */
    private function normalizeParam($value)
    {
        if (is_null($value)) {
            $value = ['type' => 'string'];
        }

        if (is_string($value)) {
            $value = ['type' => $value];
        }

        if (!isset($value['type'])) {
            $value['type']  = 'string';
        }

        return $value;
    }

    /**
     * Merge headers with prepared
     * @param string[] $headers
     * @return string[]
     */
    public function mergeHeaders($headers)
    {
        if (is_null($this->headers)) {
            return $headers;
        }

        return array_merge($this->headers, (array)$headers);
    }

}