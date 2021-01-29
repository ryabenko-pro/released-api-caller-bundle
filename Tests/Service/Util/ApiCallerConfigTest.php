<?php

namespace Released\ApiCallerBundle\Tests\Unit\Service\Util;


use PHPUnit\Framework\TestCase;
use Released\ApiCallerBundle\Service\Util\ApiCallerConfig;

class ApiCallerConfigTest extends TestCase
{
    public function testShouldParseEmptyPath()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test", [
            'file' => 'file',
            'empty' => null,
        ]);

        // WHEN
        $result = $config->getParams();

        // THEN
        $expected = [
            'file' => ['type' => 'file'],
            'empty' => ['type' => 'string'],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException \Released\ApiCallerBundle\Exception\ApiCallerException
     * @expectedExceptionMessage Path param 'file' overrides same 'file' param
     */
    public function testShouldThrowOverrideFile()
    {
        new ApiCallerConfig("Test", "/test/{file}", [
            'file' => ['type' => 'file'],
        ]);
    }

    public function testShouldParseAdditionalPath()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test/{param}/path/{param}/with/{param2}", [
        ]);

        // WHEN
        $result = $config->getPathParams();

        // THEN
        $expected = [
            'param' => ['type' => 'string'],
            'param2' => ['type' => 'string'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testShouldBuildPath()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test/{param}/{param}/{param2}");

        // WHEN
        $result = $config->buildPath([
            'empty' => 'some',
            'param' => 'value1',
            'param2' => '%value2&',
        ], []);

        // THEN
        $this->assertEquals("/test/value1/value1/%25value2%26", $result);
    }

    public function testShouldMergeHeaders()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test", [], 'get', ['Header A' => 1, 'Header B' => 2]);

        // WHEN
        $result = $config->mergeHeaders([
            'Header A' => 3,
            'Header C' => 4,
        ]);

        // THEN
        $expected = [
            'Header A' => 3,
            'Header B' => 2,
            'Header C' => 4,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testShouldFilterParams()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test/{param}/{param}/{param2}", [
            'param2' => null,
        ]);

        // WHEN
        $result = $config->filterParams([
            'empty' => 'some',
            'param' => 'value1',
            'param2' => 'value2',
        ]);

        // THEN
        $expected = [
            'empty' => 'some',
            'param2' => 'value2',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldUseDefaultValue()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/", [
            'param' => [
                'type' => 'string',
                'value' => 'Default value',
            ],
            'param2' => [
                'type' => 'string',
                'value' => 'Default value',
            ],
        ]);

        // WHEN
        $result = $config->filterParams([
            'param' => 'value 1',
        ]);

        // THEN
        $expected = [
            'param' => 'value 1',
            'param2' => 'Default value',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldFilterFiles()
    {
        // GIVEN
        $config = new ApiCallerConfig("Test", "/test", [
            'file' => 'file',
        ]);

        // WHEN
        $result = $config->filterFiles([
            'file' => 'some',
            'param' => 'value1',
            'param2' => 'value2',
        ]);

        // THEN
        $expected = [
            'file' => 'some',
        ];
        $this->assertEquals($expected, $result);
    }

}
