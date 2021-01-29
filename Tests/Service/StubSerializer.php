<?php

namespace Released\ApiCallerBundle\Tests\Service;


use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

class StubSerializer implements SerializerInterface
{

    public function serialize($data, string $format, ?SerializationContext $context = null, ?string $type = null): string {}

    public function deserialize(string $data, string $type, string $format, ?DeserializationContext $context = null) {}
}

























































































