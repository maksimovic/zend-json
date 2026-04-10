<?php

use PHPUnit\Framework\TestCase;

class Zend_Json_Server_RequestTest extends TestCase
{
    protected $request;

    protected function setUp(): void
    {
        $this->request = new Zend_Json_Server_Request();
    }

    protected function tearDown(): void
    {
    }

    public function testShouldHaveNoParamsByDefault(): void
    {
        $params = $this->request->getParams();
        $this->assertTrue(empty($params));
    }

    public function testShouldBeAbleToAddAParamAsValueOnly(): void
    {
        $this->request->addParam('foo');
        $params = $this->request->getParams();
        $this->assertEquals(1, count($params));
        $test = array_shift($params);
        $this->assertEquals('foo', $test);
    }

    public function testShouldBeAbleToAddAParamAsKeyValuePair(): void
    {
        $this->request->addParam('bar', 'foo');
        $params = $this->request->getParams();
        $this->assertEquals(1, count($params));
        $this->assertTrue(array_key_exists('foo', $params));
        $this->assertEquals('bar', $params['foo']);
    }

    public function testInvalidKeysShouldBeIgnored(): void
    {
        $count = 0;
        foreach (array(array('foo', true), array('foo', new stdClass), array('foo', array())) as $spec) {
            $this->request->addParam($spec[0], $spec[1]);
            $this->assertNull($this->request->getParam('foo'));
            $params = $this->request->getParams();
            ++$count;
            $this->assertEquals($count, count($params));
        }
    }

    public function testShouldBeAbleToAddMultipleIndexedParamsAtOnce(): void
    {
        $params = array(
            'foo',
            'bar',
            'baz',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMultipleNamedParamsAtOnce(): void
    {
        $params = array(
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertSame($params, $test);
    }

    public function testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce(): void
    {
        $params = array(
            'foo' => 'bar',
            'baz',
            'baz' => 'bat',
        );
        $this->request->addParams($params);
        $test = $this->request->getParams();
        $this->assertEquals(array_values($params), array_values($test));
        $this->assertTrue(array_key_exists('foo', $test));
        $this->assertTrue(array_key_exists('baz', $test));
        $this->assertTrue(in_array('baz', $test));
    }

    public function testSetParamsShouldOverwriteParams(): void
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = array(
            'one',
            'two',
            'three',
        );
        $this->request->setParams($params);
        $this->assertSame($params, $this->request->getParams());
    }

    public function testShouldBeAbleToRetrieveParamByKeyOrIndex(): void
    {
        $this->testShouldBeAbleToAddMixedIndexedAndNamedParamsAtOnce();
        $params = $this->request->getParams();
        $this->assertEquals('bar', $this->request->getParam('foo'), var_export($params, 1));
        $this->assertEquals('baz', $this->request->getParam(1), var_export($params, 1));
        $this->assertEquals('bat', $this->request->getParam('baz'), var_export($params, 1));
    }

    public function testMethodShouldBeNullByDefault(): void
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodErrorShouldBeFalseByDefault(): void
    {
        $this->assertFalse($this->request->isMethodError());
    }

    public function testMethodAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setMethod('foo');
        $this->assertEquals('foo', $this->request->getMethod());
    }

    public function testSettingMethodWithInvalidNameShouldSetError(): void
    {
        foreach (array('1ad', 'abc-123', 'ad$$832r#@') as $method) {
            $this->request->setMethod($method);
            $this->assertNull($this->request->getMethod());
            $this->assertTrue($this->request->isMethodError());
        }
    }

    public function testIdShouldBeNullByDefault(): void
    {
        $this->assertNull($this->request->getId());
    }

    public function testIdAccessorsShouldWorkUnderNormalInput(): void
    {
        $this->request->setId('foo');
        $this->assertEquals('foo', $this->request->getId());
    }

    public function testVersionShouldBeJsonRpcV1ByDefault(): void
    {
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testVersionShouldBeLimitedToV1AndV2(): void
    {
        $this->testVersionShouldBeJsonRpcV1ByDefault();
        $this->request->setVersion('2.0');
        $this->assertEquals('2.0', $this->request->getVersion());
        $this->request->setVersion('foo');
        $this->assertEquals('1.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToLoadRequestFromJsonString(): void
    {
        $options = $this->getOptions();
        $json    = Zend_Json::encode($options);
        $this->request->loadJson($json);

        $this->assertEquals('foo', $this->request->getMethod());
        $this->assertEquals('foobar', $this->request->getId());
        $this->assertEquals($options['params'], $this->request->getParams());
    }

    public function testLoadingFromJsonShouldSetJsonRpcVersionWhenPresent(): void
    {
        $options = $this->getOptions();
        $options['jsonrpc'] = '2.0';
        $json    = Zend_Json::encode($options);
        $this->request->loadJson($json);
        $this->assertEquals('2.0', $this->request->getVersion());
    }

    public function testShouldBeAbleToCastToJson(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->toJson();
        $this->validateJson($json, $options);
    }

    public function testCastingToStringShouldCastToJson(): void
    {
        $options = $this->getOptions();
        $this->request->setOptions($options);
        $json    = $this->request->__toString();
        $this->validateJson($json, $options);
    }

    public function testMethodNamesShouldAllowDotNamespacing(): void
    {
        $this->request->setMethod('foo.bar');
        $this->assertEquals('foo.bar', $this->request->getMethod());
    }

    public function getOptions(): array
    {
        return array(
            'method' => 'foo',
            'params' => array(
                5,
                'four',
                true,
            ),
            'id'     => 'foobar'
        );
    }

    public function validateJson($json, array $options): void
    {
        $test = Zend_Json::decode($json);
        $this->assertIsArray($test, var_export($json, 1));

        $this->assertTrue(array_key_exists('id', $test));
        $this->assertTrue(array_key_exists('method', $test));
        $this->assertTrue(array_key_exists('params', $test));

        $this->assertIsString($test['id']);
        $this->assertIsString($test['method']);
        $this->assertIsArray($test['params']);

        $this->assertEquals($options['id'], $test['id']);
        $this->assertEquals($options['method'], $test['method']);
        $this->assertSame($options['params'], $test['params']);
    }
}
