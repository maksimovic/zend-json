<?php

use PHPUnit\Framework\TestCase;

class Zend_Json_Server_ResponseTest extends TestCase
{
    protected $response;

    protected function setUp(): void
    {
        $this->response = new Zend_Json_Server_Response();
    }

    protected function tearDown(): void
    {
    }

    public function testResultShouldBeNullByDefault(): void
    {
        $this->assertNull($this->response->getResult());
    }

    public function testResultAccessorsShouldWorkWithNormalInput(): void
    {
        foreach (array(true, 'foo', 2, 2.0, array(), array('foo' => 'bar')) as $result) {
            $this->response->setResult($result);
            $this->assertEquals($result, $this->response->getResult());
        }
    }

    public function testResultShouldNotBeErrorByDefault(): void
    {
        $this->assertFalse($this->response->isError());
    }

    public function testSettingErrorShouldMarkRequestAsError(): void
    {
        $error = new Zend_Json_Server_Error();
        $this->response->setError($error);
        $this->assertTrue($this->response->isError());
    }

    public function testShouldBeAbleToRetrieveErrorObject(): void
    {
        $error = new Zend_Json_Server_Error();
        $this->response->setError($error);
        $this->assertSame($error, $this->response->getError());
    }

    public function testIdShouldBeNullByDefault(): void
    {
        $this->assertNull($this->response->getId());
    }

    public function testIdAccesorsShouldWorkWithNormalInput(): void
    {
        $this->response->setId('foo');
        $this->assertEquals('foo', $this->response->getId());
    }

    public function testVersionShouldBeNullByDefault(): void
    {
        $this->assertNull($this->response->getVersion());
    }

    public function testVersionShouldBeLimitedToV2(): void
    {
        $this->response->setVersion('2.0');
        $this->assertEquals('2.0', $this->response->getVersion());
        foreach (array('a', 1, '1.0', array(), true) as $version) {
            $this->response->setVersion($version);
            $this->assertNull($this->response->getVersion());
        }
    }

    public function testResponseShouldBeAbleToCastToJson(): void
    {
        $this->response->setResult(true)
                       ->setId('foo')
                       ->setVersion('2.0');
        $json = $this->response->toJson();
        $test = Zend_Json::decode($json);

        $this->assertIsArray($test);
        $this->assertTrue(array_key_exists('result', $test));
        $this->assertFalse(array_key_exists('error', $test), "'error' may not coexist with 'result'");
        $this->assertTrue(array_key_exists('id', $test));
        $this->assertTrue(array_key_exists('jsonrpc', $test));

        $this->assertTrue($test['result']);
        $this->assertEquals($this->response->getId(), $test['id']);
        $this->assertEquals($this->response->getVersion(), $test['jsonrpc']);
    }

    public function testResponseShouldCastErrorToJsonIfIsError(): void
    {
        $error = new Zend_Json_Server_Error();
        $error->setCode(Zend_Json_Server_Error::ERROR_INTERNAL)
              ->setMessage('error occurred');
        $this->response->setId('foo')
                       ->setResult(true)
                       ->setError($error);
        $json = $this->response->toJson();
        $test = Zend_Json::decode($json);

        $this->assertIsArray($test);
        $this->assertFalse(array_key_exists('result', $test), "'result' may not coexist with 'error'");
        $this->assertTrue(array_key_exists('id', $test));
        $this->assertFalse(array_key_exists('jsonrpc', $test));

        $this->assertEquals($this->response->getId(), $test['id']);
        $this->assertEquals($error->getCode(), $test['error']['code']);
        $this->assertEquals($error->getMessage(), $test['error']['message']);
    }

    public function testCastToStringShouldCastToJson(): void
    {
        $this->response->setResult(true)
                       ->setId('foo');
        $json = $this->response->__toString();
        $test = Zend_Json::decode($json);

        $this->assertIsArray($test);
        $this->assertTrue(array_key_exists('result', $test));
        $this->assertFalse(array_key_exists('error', $test), "'error' may not coexist with 'result'");
        $this->assertTrue(array_key_exists('id', $test));
        $this->assertFalse(array_key_exists('jsonrpc', $test));

        $this->assertTrue($test['result']);
        $this->assertEquals($this->response->getId(), $test['id']);
    }
}
