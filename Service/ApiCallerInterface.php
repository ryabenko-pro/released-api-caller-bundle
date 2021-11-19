<?php


namespace Released\ApiCallerBundle\Service;


use Released\ApiCallerBundle\Exception\ApiCallerException;
use Released\ApiCallerBundle\Service\Util\ApiCallerListenerInterface;
use Released\ApiCallerBundle\Transport\TransportResponse;

interface ApiCallerInterface
{

    /**
     * @param string|array $api Name of API to call
     * @param array $values
     * @param ApiCallerListenerInterface $listener
     * @param array $headers
     * @param null $domain
     * @return TransportResponse
     */
    public function makeRequest($api, $values = [], ApiCallerListenerInterface $listener = null, $headers = null, $domain = null);

}