<?php

namespace Released\ApiCallerBundle\Transport;


interface TransportInterface
{

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';

    /**
     * @param string $url
     * @param string $method
     * @param string $data
     * @param null $headers
     * @param array $cookies
     * @param array $files
     * @return TransportResponse
     */
    public function request($url, $method = self::METHOD_GET, $data = null, $headers = null, $cookies = null, $files = null);

} 