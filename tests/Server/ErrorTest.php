<?php

use PHPUnit\Framework\TestCase;

class Zend_Json_Server_ErrorTest extends TestCase
{
    protected $error;

    protected function setUp(): void
    {
        $this->error = new Zend_Json_Server_Error();
    }

    protected function tearDown(): void
    {
    }

    public function testCodeShouldBeErrOtherByDefault(): void
    {
        $this->assertEquals(Zend_Json_Server_Error::ERROR_OTHER, $this->error->getCode());
    }

    public function testSetCodeShouldCastToInteger(): void
    {
        $this->error->setCode('-32768');
        $this->assertEquals(-32768, $this->error->getCode());
    }

    public function testCodeShouldBeLimitedToStandardIntegers(): void
    {
        foreach (array(true, 'foo', array(), new stdClass, 2.0, 25) as $code) {
            $this->error->setCode($code);
            $this->assertEquals(Zend_Json_Server_Error::ERROR_OTHER, $this->error->getCode());
        }
    }

    public function testCodeShouldAllowArbitraryAppErrorCodesInXmlRpcErrorCodeRange(): void
    {
        foreach (range(-32099, -32000) as $code) {
            $this->error->setCode($code);
            $this->assertEquals($code, $this->error->getCode());
        }
    }

    public function testMessageShouldBeNullByDefault(): void
    {
        $this->assertNull($this->error->getMessage());
    }

    public function testSetMessageShouldCastToString(): void
    {
        foreach (array(true, 2.0, 25) as $message) {
            $this->error->setMessage($message);
            $this->assertEquals((string) $message, $this->error->getMessage());
        }
    }

    public function testSetMessageToNonScalarShouldSilentlyFail(): void
    {
        foreach (array(array(), new stdClass) as $message) {
            $this->error->setMessage($message);
            $this->assertNull($this->error->getMessage());
        }
    }

    public function testDataShouldBeNullByDefault(): void
    {
        $this->assertNull($this->error->getData());
    }

    public function testShouldAllowArbitraryData(): void
    {
        foreach (array(true, 'foo', 2, 2.0, array(), new stdClass) as $datum) {
            $this->error->setData($datum);
            $this->assertEquals($datum, $this->error->getData());
        }
    }

    public function testShouldBeAbleToCastToArray(): void
    {
        $this->setupError();
        $array = $this->error->toArray();
        $this->validateArray($array);
    }

    public function testShouldBeAbleToCastToJson(): void
    {
        $this->setupError();
        $json = $this->error->toJson();
        $this->validateArray(Zend_Json::decode($json));
    }

    public function testCastingToStringShouldCastToJson(): void
    {
        $this->setupError();
        $json = $this->error->__toString();
        $this->validateArray(Zend_Json::decode($json));
    }

    public function setupError(): void
    {
        $this->error->setCode(Zend_Json_Server_Error::ERROR_OTHER)
                    ->setMessage('Unknown Error')
                    ->setData(array('foo' => 'bar'));
    }

    public function validateArray($error): void
    {
        $this->assertIsArray($error);
        $this->assertTrue(array_key_exists('code', $error));
        $this->assertTrue(array_key_exists('message', $error));
        $this->assertTrue(array_key_exists('data', $error));

        $this->assertIsInt($error['code']);
        $this->assertIsString($error['message']);
        $this->assertIsArray($error['data']);

        $this->assertEquals($this->error->getCode(), $error['code']);
        $this->assertEquals($this->error->getMessage(), $error['message']);
        $this->assertSame($this->error->getData(), $error['data']);
    }
}
