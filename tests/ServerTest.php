<?php

use PHPUnit\Framework\TestCase;

class Zend_Json_ServerTest extends TestCase
{
    protected $server;

    protected function setUp(): void
    {
        $this->server = new Zend_Json_Server();
    }

    protected function tearDown(): void
    {
    }

    public function testShouldBeAbleToBindFunctionToServer(): void
    {
        $this->server->addFunction('strtolower');
        $methods = $this->server->getFunctions();
        $this->assertTrue($methods->hasMethod('strtolower'));
    }

    public function testShouldBeAbleToBindCallback1ToServer(): void
    {
        $this->server->addFunction(array('Zend_Json_ServerTest_Foo', 'staticBar'));
        $methods = $this->server->getFunctions();
        $this->assertTrue($methods->hasMethod('staticBar'));
    }

    public function testShouldBeAbleToBindCallback2ToServer(): void
    {
        $this->server->addFunction(array(new Zend_Json_ServerTest_Foo, 'bar'));
        $methods = $this->server->getFunctions();
        $this->assertTrue($methods->hasMethod('bar'));
    }

    public function testShouldBeAbleToBindClassToServer(): void
    {
        $this->server->setClass('Zend_Json_Server');
        $test = $this->server->getFunctions();
        $this->assertTrue(0 < count($test));
    }

    public function testBindingClassToServerShouldRegisterAllPublicMethods(): void
    {
        $this->server->setClass('Zend_Json_Server');
        $test = $this->server->getFunctions();
        $methods = get_class_methods('Zend_Json_Server');
        foreach ($methods as $method) {
            if ('_' == $method[0]) {
                continue;
            }
            $this->assertTrue($test->hasMethod($method), 'Testing for method ' . $method . ' against ' . var_export($test, 1));
        }
    }

    public function testShouldBeAbleToBindObjectToServer(): void
    {
        $object = new Zend_Json_Server();
        $this->server->setClass($object);
        $test = $this->server->getFunctions();
        $this->assertTrue(0 < count($test));
    }

    public function testBindingObjectToServerShouldRegisterAllPublicMethods(): void
    {
        $object = new Zend_Json_Server();
        $this->server->setClass($object);
        $test = $this->server->getFunctions();
        $methods = get_class_methods($object);
        foreach ($methods as $method) {
            if ('_' == $method[0]) {
                continue;
            }
            $this->assertTrue($test->hasMethod($method), 'Testing for method ' . $method . ' against ' . var_export($test, 1));
        }
    }

    public function testShouldBeAbleToBindMultipleClassesAndObjectsToServer(): void
    {
        $this->server->setClass('Zend_Json_Server')
                     ->setClass(new Zend_Json());
        $methods = $this->server->getFunctions();
        $zjsMethods = get_class_methods('Zend_Json_Server');
        $zjMethods  = get_class_methods('Zend_Json');
        $this->assertTrue(count($zjsMethods) < count($methods));
        $this->assertTrue(count($zjMethods) < count($methods));
    }

    public function testNamingCollisionsShouldResolveToLastRegisteredMethod(): void
    {
        $this->server->setClass('Zend_Json_Server_Request')
                     ->setClass('Zend_Json_Server_Response');
        $methods = $this->server->getFunctions();
        $this->assertTrue($methods->hasMethod('toJson'));
        $toJson = $methods->getMethod('toJson');
        $this->assertEquals('Zend_Json_Server_Response', $toJson->getCallback()->getClass());
    }

    public function testGetRequestShouldInstantiateRequestObjectByDefault(): void
    {
        $request = $this->server->getRequest();
        $this->assertTrue($request instanceof Zend_Json_Server_Request);
    }

    public function testShouldAllowSettingRequestObjectManually(): void
    {
        $orig = $this->server->getRequest();
        $new  = new Zend_Json_Server_Request();
        $this->server->setRequest($new);
        $test = $this->server->getRequest();
        $this->assertSame($new, $test);
        $this->assertNotSame($orig, $test);
    }

    public function testGetResponseShouldInstantiateResponseObjectByDefault(): void
    {
        $response = $this->server->getResponse();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
    }

    public function testShouldAllowSettingResponseObjectManually(): void
    {
        $orig = $this->server->getResponse();
        $new  = new Zend_Json_Server_Response();
        $this->server->setResponse($new);
        $test = $this->server->getResponse();
        $this->assertSame($new, $test);
        $this->assertNotSame($orig, $test);
    }

    public function testFaultShouldCreateErrorResponse(): void
    {
        $response = $this->server->getResponse();
        $this->assertFalse($response->isError());
        $this->server->fault('error condition', -32000);
        $this->assertTrue($response->isError());
        $error = $response->getError();
        $this->assertEquals(-32000, $error->getCode());
        $this->assertEquals('error condition', $error->getMessage());
    }

    public function testResponseShouldBeEmittedAutomaticallyByDefault(): void
    {
        $this->assertTrue($this->server->autoEmitResponse());
    }

    public function testShouldBeAbleToDisableAutomaticResponseEmission(): void
    {
        $this->testResponseShouldBeEmittedAutomaticallyByDefault();
        $this->server->setAutoEmitResponse(false);
        $this->assertFalse($this->server->autoEmitResponse());
    }

    public function testShouldBeAbleToRetrieveSmdObject(): void
    {
        $smd = $this->server->getServiceMap();
        $this->assertTrue($smd instanceof Zend_Json_Server_Smd);
    }

    public function testShouldBeAbleToSetArbitrarySmdMetadata(): void
    {
        $this->server->setTransport('POST')
                     ->setEnvelope('JSON-RPC-1.0')
                     ->setContentType('application/x-json')
                     ->setTarget('/foo/bar')
                     ->setId('foobar')
                     ->setDescription('This is a test service');

        $this->assertEquals('POST', $this->server->getTransport());
        $this->assertEquals('JSON-RPC-1.0', $this->server->getEnvelope());
        $this->assertEquals('application/x-json', $this->server->getContentType());
        $this->assertEquals('/foo/bar', $this->server->getTarget());
        $this->assertEquals('foobar', $this->server->getId());
        $this->assertEquals('This is a test service', $this->server->getDescription());
    }

    public function testSmdObjectRetrievedFromServerShouldReflectServerState(): void
    {
        $this->server->addFunction('strtolower')
                     ->setClass('Zend_Json_Server')
                     ->setTransport('POST')
                     ->setEnvelope('JSON-RPC-1.0')
                     ->setContentType('application/x-json')
                     ->setTarget('/foo/bar')
                     ->setId('foobar')
                     ->setDescription('This is a test service');
        $smd = $this->server->getServiceMap();
        $this->assertEquals('POST', $this->server->getTransport());
        $this->assertEquals('JSON-RPC-1.0', $this->server->getEnvelope());
        $this->assertEquals('application/x-json', $this->server->getContentType());
        $this->assertEquals('/foo/bar', $this->server->getTarget());
        $this->assertEquals('foobar', $this->server->getId());
        $this->assertEquals('This is a test service', $this->server->getDescription());

        $services = $smd->getServices();
        $this->assertIsArray($services);
        $this->assertTrue(0 < count($services));
        $this->assertTrue(array_key_exists('strtolower', $services));
        $methods = get_class_methods('Zend_Json_Server');
        foreach ($methods as $method) {
            if ('_' == $method[0]) {
                continue;
            }
            $this->assertTrue(array_key_exists($method, $services));
        }
    }

    public function testHandleValidMethodShouldWork(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->addFunction('Zend_Json_ServerTest_FooFunc')
                     ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(array(true, 'foo', 'bar'))
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertFalse($response->isError());


        $request->setMethod('Zend_Json_ServerTest_FooFunc')
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertFalse($response->isError());
    }

    public function testHandleValidMethodWithTooFewParamsShouldPassDefaultsOrNullsForMissingParams(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(array(true))
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertFalse($response->isError());
        $result = $response->getResult();
        $this->assertIsArray($result);
        $this->assertTrue(3 == count($result));
        $this->assertEquals('two', $result[1], var_export($result, 1));
        $this->assertNull($result[2]);
    }

    public function testHandleValidMethodWithMissingParamsShouldThrowException(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
            ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
            ->setParams(array('one' => null))
            ->setId('foo');
        try {
            $response = $this->server->handle();
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Zend_Server_Exception);
            $this->assertEquals('Method bar is missing required parameter: one', $e->getMessage());
        }
    }

    public function testHandleValidMethodWithTooManyParamsShouldWork(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(array(true, 'foo', 'bar', 'baz'))
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertFalse($response->isError());
        $result = $response->getResult();
        $this->assertIsArray($result);
        $this->assertTrue(3 == count($result));
        $this->assertEquals('foo', $result[1]);
        $this->assertEquals('bar', $result[2]);
    }

    public function testHandleShouldAllowNamedParamsInAnyOrder1(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse( false );
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams( array(
                    'three' => 3,
                    'two'   => 2,
                    'one'   => 1
                ))
                ->setId( 'foo' );
        $response = $this->server->handle();
        $result = $response->getResult();

        $this->assertIsArray($result);
        $this->assertEquals( 1, $result[0] );
        $this->assertEquals( 2, $result[1] );
        $this->assertEquals( 3, $result[2] );
    }

    public function testHandleShouldAllowNamedParamsInAnyOrder2(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse( false );
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams( array(
                    'three' => 3,
                    'one'   => 1,
                    'two'   => 2,
                ) )
                ->setId( 'foo' );
        $response = $this->server->handle();
        $result = $response->getResult();

        $this->assertIsArray($result);
        $this->assertEquals( 1, $result[0] );
        $this->assertEquals( 2, $result[1] );
        $this->assertEquals( 3, $result[2] );
    }

    public function testHandleRequestWithErrorsShouldReturnErrorResponse(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse(false);
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertTrue($response->isError());
        $this->assertEquals(Zend_Json_Server_Error::ERROR_INVALID_REQUEST, $response->getError()->getCode());
    }

    public function testHandleRequestWithInvalidMethodShouldReturnErrorResponse(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('bogus')
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertTrue($response->isError());
        $this->assertEquals(Zend_Json_Server_Error::ERROR_INVALID_METHOD, $response->getError()->getCode());
    }

    public function testHandleRequestWithExceptionShouldReturnErrorResponse(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo')
                     ->setAutoEmitResponse(false);
        $request = $this->server->getRequest();
        $request->setMethod('baz')
                ->setId('foo');
        $response = $this->server->handle();
        $this->assertTrue($response instanceof Zend_Json_Server_Response);
        $this->assertTrue($response->isError());
        $this->assertEquals(Zend_Json_Server_Error::ERROR_OTHER, $response->getError()->getCode());
        $this->assertEquals('application error', $response->getError()->getMessage());
    }

    public function testHandleShouldEmitResponseByDefault(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo');
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(array(true, 'foo', 'bar'))
                ->setId('foo');
        ob_start();
        $this->server->handle();
        $buffer = ob_get_clean();

        $decoded = Zend_Json::decode($buffer);
        $this->assertIsArray($decoded);
        $this->assertTrue(array_key_exists('result', $decoded));
        $this->assertTrue(array_key_exists('id', $decoded));

        $response = $this->server->getResponse();
        $this->assertEquals($response->getResult(), $decoded['result']);
        $this->assertEquals($response->getId(), $decoded['id']);
    }

    public function testResponseShouldBeEmptyWhenRequestHasNoId(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo');
        $request = $this->server->getRequest();
        $request->setMethod('bar')
                ->setParams(array(true, 'foo', 'bar'));
        ob_start();
        $this->server->handle();
        $buffer = ob_get_clean();

        $this->assertTrue(empty($buffer));
    }

    public function testLoadFunctionsShouldLoadResultOfGetFunctions(): void
    {
        $this->server->setClass('Zend_Json_ServerTest_Foo');
        $functions = $this->server->getFunctions();
        $server = new Zend_Json_Server();
        $server->loadFunctions($functions);
        $this->assertEquals($functions->toArray(), $server->getFunctions()->toArray());
    }
}

class Zend_Json_ServerTest_Foo
{
    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @param  mixed $three
     * @return array
     */
    static public function staticBar($one, $two = 'two', $three = null)
    {
        return array($one, $two, $three);
    }

    /**
     * Bar
     *
     * @param  bool $one
     * @param  string $two
     * @param  mixed $three
     * @return array
     */
    public function bar($one, $two = 'two', $three = null)
    {
        return array($one, $two, $three);
    }

    /**
     * Baz
     *
     * @return void
     */
    public function baz()
    {
        throw new Exception('application error');
    }
}

function Zend_Json_ServerTest_FooFunc()
{
    return true;
}
