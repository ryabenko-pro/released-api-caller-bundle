<?php

namespace Released\ApiCallerBundle\Tests\Service;

use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Released\ApiCallerBundle\Exception\ApiCallerException;
use Released\ApiCallerBundle\Exception\ApiResponseException;
use Released\ApiCallerBundle\Service\ApiCaller;
use Released\ApiCallerBundle\Service\Util\ApiCallerListenerInterface;
use Released\ApiCallerBundle\Transport\StubTransport;
use Released\ApiCallerBundle\Transport\TransportInterface;
use Released\ApiCallerBundle\Transport\TransportResponse;

class ApiCallerTest extends TestCase
{

    public function testShouldThrowApiNotExists()
    {
        $this->expectException(ApiCallerException::class);
        $this->expectExceptionMessage("Api 'test' does not exists");

        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];

        $caller = new ApiCaller(new StubTransport(), $this->createStub(SerializerInterface::class), $domain, $apis);
        $caller->makeRequest('test', null);
    }

    public function testShouldThrowNotEnoughParameters()
    {
        $this->expectException(ApiCallerException::class);
        $this->expectExceptionMessage("Not enough parameters: param, param1");

        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path/{param}/{param1}'];

        $caller = new ApiCaller(new StubTransport(), $this->createStub(SerializerInterface::class), $domain, $apis);
        $caller->makeRequest('test', null);
    }

    public function testShouldThrowClassDoesNotMatch()
    {
        $this->expectException(ApiCallerException::class);
        $this->expectExceptionMessage("Param 'some' should be instance of 'Released\ApiCallerBundle\Tests\Service\ApiCallerTest'");

        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = [
            'name' => 'Test',
            'path' => '/path/{param}/{param1}',
            'params' => [
                'some' => [
                    'name' => 'some',
                    'class' => get_class($this),
                ]
            ],
        ];

        $caller = new ApiCaller(new StubTransport(), $this->createStub(SerializerInterface::class), $domain, $apis);
        $caller->makeRequest('test', ['some' => ''], null);
    }

    public function testShouldMakeRequest()
    {
        // GIVEN
        $domain = "http://domain.com/";
        $fileContent = "File content";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path/{param}', 'params' => [
            'file' => 'file',
        ], 'method' => 'POST'];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transportResponse = new TransportResponse("some content");
        $transport->expects($this->once())->method('request')
            ->with(
                $domain . "path/value",
                StubTransport::METHOD_POST,
                [],
                null,
                null,
                ['file' => $fileContent]
            )
            ->willReturn($transportResponse);

        $caller = new ApiCaller($transport, $this->createStub(SerializerInterface::class), $domain, $apis);
        $response = $caller->makeRequest('test', [
            'param' => 'value',
            'file' => $fileContent,
        ], null);

        $this->assertEquals($transportResponse, $response);
    }

    public function testShouldCastRequestObject()
    {
        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path/{param}', 'method' => 'POST'];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transportResponse = new TransportResponse("some content");
        $transport->expects($this->once())->method('request')
            ->with(
                $domain . "path/value",
                StubTransport::METHOD_POST,
                ['a' => 1, 'b' => 2],
                null,
                null,
            )
            ->willReturn($transportResponse);


        $caller = new ApiCaller($transport, $this->createStub(SerializerInterface::class), $domain, $apis);
        $response = $caller->makeRequest('test', new class {
            // Must be getters because of normilizer
            public function getParam(): string { return 'value'; }
            public function getA() { return 1; }
            public function getB() { return 2; }
        }, null);

        $this->assertEquals($transportResponse, $response);
    }

    public function testShouldCastResponse()
    {
        // GIVEN
        $apis = [
            'test' => ['name' => 'Test', 'path' => '/path', 'response_class' => 'Class\To\Cast']
        ];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transportResponse = new TransportResponse("some content");
        $transport->expects($this->once())->method('request')
            ->willReturn($transportResponse);

        /** @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->getMockBuilder(SerializerInterface::class)
            ->getMock();

        $caller = new ApiCaller($transport, $serializer, "http://domain.com/", $apis);

        // EXPECTS
        $serializer->expects($this->once())->method('deserialize')
            ->with('some content', 'Class\To\Cast', 'json')
            ->willReturn(['some casted value']);

        // WHEN
        $response = $caller->makeRequest('test', null);

        $this->assertEquals(new TransportResponse(['some casted value']), $response);
    }

    public function testShouldMergeHeaders()
    {
        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path', 'headers' => [
            'Header A' => 1,
            'Header B' => 2,
        ]];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transportResponse = new TransportResponse("some content");
        $transport->expects($this->once())->method('request')
            ->with(
                $domain . "path",
                StubTransport::METHOD_GET,
                [],
                ['Header A' => 3, 'Header B' => 2, 'Header C' => 4],
                null,
                []
            )
            ->willReturn($transportResponse);

        $caller = new ApiCaller($transport, $this->createStub(SerializerInterface::class), $domain, $apis);
        $response = $caller->makeRequest('test', [], null, [
            'Header A' => 3,
            'Header C' => 4,
        ], null);

        $this->assertEquals($transportResponse, $response);
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportInterface
     */
    private function getTransportMock()
    {
        $transport = $this->getMockBuilder(StubTransport::class)
            ->getMock();

        return $transport;
    }

    public function testShouldCallCallback()
    {
        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path/{param}', 'params' => [], 'method' => 'POST'];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transportResponse = new TransportResponse("some content");
        $transport->expects($this->once())->method('request')
            ->with($domain . "path/value", StubTransport::METHOD_POST, [
                'a' => 'b',
            ])
            ->willReturn($transportResponse);

        $callback = $this->getMockBuilder(ApiCallerListenerInterface::class)
            ->getMock();

        $callback->expects($this->once())->method('onRequest')
            ->with('http://domain.com/path/value', ['a' => 'b'], 'some content', 200, StubTransport::METHOD_POST);

        $caller = new ApiCaller($transport, $this->createStub(SerializerInterface::class), $domain, $apis);
        $response = $caller->makeRequest('test', [
            'param' => 'value',
            'a' => 'b',
        ], $callback, null);

        $this->assertEquals($transportResponse, $response);
    }

    public function testShouldThrowNotSuccessful()
    {
        $this->expectException(ApiResponseException::class);
        $this->expectExceptionMessage("Response status is 500");

        // GIVEN
        $domain = "http://domain.com/";
        $apis = [];
        $apis['test'] = ['name' => 'Test', 'path' => '/path'];

        $transport = $this->getTransportMock();

        /** @var StubTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transport->expects($this->once())->method('request')
            ->with($domain . "path", StubTransport::METHOD_GET)
            ->willReturn(new TransportResponse("some content", 500));

        $caller = new ApiCaller($transport, $this->createStub(SerializerInterface::class), $domain, $apis);
        $caller->makeRequest('test', null);
    }

}

