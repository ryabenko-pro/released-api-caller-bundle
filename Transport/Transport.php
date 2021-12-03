<?php

namespace Released\ApiCallerBundle\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\RequestOptions;

class Transport implements TransportInterface
{

    /** @var Client */
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @inheritdoc
     */
    public function request($url, $method = self::METHOD_GET, $data = null, $headers = null, $cookies = null, $files = null)
    {
        switch ($method) {
            case self::METHOD_PUT:
            case self::METHOD_PATCH:
            case self::METHOD_POST:

                // TODO: use another HTTP client
                $multipart = [];
                if (!is_null($files) && is_array($files)) {
                    foreach ($files as $key => $value) {
                        $filename = null;
                        if (is_array($value)) {
                            $filename = $value['filename'];
                            $value = $value['file'];
                        }

                        if (is_resource($value)) {
                            $multipart[] = [
                                'name' => $key,
                                'contents' => new Stream($value),
                                'filename' => $filename,
                            ];
                        } else if ($value instanceof \SplFileInfo) {
                            $multipart[] = [
                                'name' => $key,
                                // TODO: close the file after the request
                                'contents' => $value,
                                'filename' => $value->getFilename(),
                            ];
                        } else if (is_file($value) && is_readable($value)) {
                            $multipart[] = [
                                'name' => $key,
                                // TODO: close the file after the request
                                'contents' => new Stream(fopen($value, 'r')),
                                'filename' => $filename,
                            ];
                        } else {
                            $multipart[] = [
                                'name' => $key,
                                'contents' => $value,
                                'filename' => $filename,
                            ];
                        }
                    }
                }

                foreach ($data as $key => $value) {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }

                if (self::METHOD_POST === $method) {
                    if (is_array($files) && count($files) > 1) {
                        $response = $this->client->post($url, [
                            RequestOptions::MULTIPART => $multipart,
                            'headers' => $headers,
                            'exceptions' => false,
                        ]);
                    } else {
                        $response = $this->client->post($url, [
                            RequestOptions::JSON => $data,
                            'headers' => $headers,
                            'exceptions' => false,
                        ]);
                    }
                }

                if (self::METHOD_PATCH === $method) {
                    $response = $this->client->patch($url, [
                        RequestOptions::JSON => $data,
                        'headers' => $headers,
                        'exceptions' => false,
                    ]);
                }

                if (self::METHOD_PUT === $method) {
                    $response = $this->client->put($url, [
                        RequestOptions::JSON => $data,
                        'headers' => $headers,
                        'exceptions' => false,
                    ]);
                }

                break;
            default:
                $response = $this->client->get($url, [
                    'query' => (array)$data,
                    'headers' => $headers,
                    'exceptions' => false,
                ]);
                break;
        }

        if ($response->hasHeader("Content-Disposition") && str_starts_with($response->getHeader("Content-Disposition")[0], "attachment")) {
            $content = $response->getBody();
        } else {
            $content = $response->getBody()->getContents();
            $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
        }


        if ($response->getHeader("Content-Type") == 'application/json') {
            $content = json_decode($content, true);
        }

        return new TransportResponse($content, $response->getStatusCode());
    }
}
