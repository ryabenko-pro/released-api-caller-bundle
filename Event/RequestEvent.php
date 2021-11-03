<?php

namespace Released\ApiCallerBundle\Event;

use Released\ApiCallerBundle\Service\Util\ApiCallerConfig;

class RequestEvent
{
    /** @var string */
    private $callerCaseName;
    /** @var string */
    protected $api;
    /** @var ApiCallerConfig */
    protected $config;
    /** @var array|null */
    protected $values;
    /** @var string */
    protected $path;
    /** @var array|null */
    protected $data;
    /** @var array|null */
    protected $files;
    /** @var array|null */
    protected $headers;

    public function __construct(string $callerCaseName, string $apiName, ApiCallerConfig $config, ?array $values, string $path, ?array $data, ?array $files, ?array $headers)
    {
        $this->callerCaseName = $callerCaseName;
        $this->api = $apiName;
        $this->config = $config;
        $this->values = $values;
        $this->path = $path;
        $this->data = $data;
        $this->files = $files;
        $this->headers = $headers;
    }

    public function getCallerCaseName(): string
    {
        return $this->callerCaseName;
    }

    public function getApi(): string
    {
        return $this->api;
    }

    public function getConfig(): ApiCallerConfig
    {
        return $this->config;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }

    public function setValues(?array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath($path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function setFiles(?array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setHeaders(?array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }
}