<?php

use PHPUnit\Framework\TestCase;

class Zend_JsonTest extends TestCase
{
    private $_originalUseBuiltinEncoderDecoderValue;

    protected function setUp(): void
    {
        $this->_originalUseBuiltinEncoderDecoderValue = Zend_Json::$useBuiltinEncoderDecoder;
    }

    protected function tearDown(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = $this->_originalUseBuiltinEncoderDecoderValue;
    }

    public function testJsonWithPhpJsonExtension(): void
    {
        if (!extension_loaded('json')) {
            $this->markTestSkipped('JSON extension is not loaded');
        }
        Zend_Json::$useBuiltinEncoderDecoder = false;
        $this->_testJson(array('string', 327, true, null));
    }

    public function testJsonWithBuiltins(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = true;
        $this->_testJson(array('string', 327, true, null));
    }

    protected function _testJson($values): void
    {
        $encoded = Zend_Json::encode($values);
        $this->assertEquals($values, Zend_Json::decode($encoded));
    }

    public function testNull(): void
    {
        $this->_testEncodeDecode(array(null));
    }

    public function testBoolean(): void
    {
        $this->assertTrue(Zend_Json_Decoder::decode(Zend_Json_Encoder::encode(true)));
        $this->assertFalse(Zend_Json_Decoder::decode(Zend_Json_Encoder::encode(false)));
    }

    public function testInteger(): void
    {
        $this->_testEncodeDecode(array(-2));
        $this->_testEncodeDecode(array(-1));

        $zero = Zend_Json_Decoder::decode(Zend_Json_Encoder::encode(0));
        $this->assertEquals(0, $zero, 'Failed 0 integer test. Encoded: ' . serialize(Zend_Json_Encoder::encode(0)));
    }

    public function testJsonPrettyPrintWorksWithArrayNotationInStringLiteral(): void
    {
        $o = new stdClass();
        $o->test = 1;
        $o->faz = 'fubar';

        $test = array(
            'simple'=>'simple test string',
            'stringwithjsonchars'=>'\"[1,2]',
            'complex'=>array(
                'foo'=>'bar',
                'far'=>'boo',
                'faz'=>array(
                    'obj'=>$o
                )
            )
        );
        $pretty = Zend_Json::prettyPrint(Zend_Json::encode($test), array('indent' => ' '));
        $expected = <<<EOB
{
 "simple":"simple test string",
 "stringwithjsonchars":"\\\\\\"[1,2]",
 "complex":{
  "foo":"bar",
  "far":"boo",
  "faz":{
   "obj":{
    "test":1,
    "faz":"fubar"
   }
  }
 }
}
EOB;
        $this->assertSame($expected, $pretty);
    }

    public function testFloat(): void
    {
        $this->_testEncodeDecode(array(-2.1, 1.2));
    }

    public function testString(): void
    {
        $this->_testEncodeDecode(array('string'));
        $this->assertEquals('', Zend_Json_Decoder::decode(Zend_Json_Encoder::encode('')), 'Empty string encoded: ' . serialize(Zend_Json_Encoder::encode('')));
    }

    public function testString2(): void
    {
        $string   = 'INFO: Path \\\\test\\123\\abc';
        $expected = '"INFO: Path \\\\\\\\test\\\\123\\\\abc"';
        $encoded = Zend_Json_Encoder::encode($string);
        $this->assertEquals($expected, $encoded, 'Backslash encoding incorrect: expected: ' . serialize($expected) . '; received: ' . serialize($encoded) . "\n");
        $this->assertEquals($string, Zend_Json_Decoder::decode($encoded));
    }

    public function testString3(): void
    {
        $expected = '"INFO: Path\nSome more"';
        $string   = "INFO: Path\nSome more";
        $encoded  = Zend_Json_Encoder::encode($string);
        $this->assertEquals($expected, $encoded, 'Newline encoding incorrect: expected ' . serialize($expected) . '; received: ' . serialize($encoded) . "\n");
        $this->assertEquals($string, Zend_Json_Decoder::decode($encoded));
    }

    public function testString4(): void
    {
        $expected = '"INFO: Path\\t\\\\tSome more"';
        $string   = "INFO: Path\t\\tSome more";
        $encoded  = Zend_Json_Encoder::encode($string);
        $this->assertEquals($expected, $encoded, 'Tab encoding incorrect: expected ' . serialize($expected) . '; received: ' . serialize($encoded) . "\n");
        $this->assertEquals($string, Zend_Json_Decoder::decode($encoded));
    }

    public function testString5(): void
    {
        $expected = '"INFO: Path \"Some more\""';
        $string   = 'INFO: Path "Some more"';
        $encoded  = Zend_Json_Encoder::encode($string);
        $this->assertEquals($expected, $encoded, 'Quote encoding incorrect: expected ' . serialize($expected) . '; received: ' . serialize($encoded) . "\n");
        $this->assertEquals($string, Zend_Json_Decoder::decode($encoded));
    }

    public function testArray(): void
    {
        $array = array(1, 'one', 2, 'two');
        $encoded = Zend_Json_Encoder::encode($array);
        $this->assertSame($array, Zend_Json_Decoder::decode($encoded), 'Decoded array does not match: ' . serialize($encoded));
    }

    public function testAssocArray(): void
    {
        $this->_testEncodeDecode(array(array('one' => 1, 'two' => 2)));
    }

    public function testAssocArray2(): void
    {
        $this->_testEncodeDecode(array(array('one' => 1, 2 => 2)));
    }

    public function testAssocArray3(): void
    {
        $this->_testEncodeDecode(array(array(1 => 'one', 2 => 'two')));
    }

    public function testObject(): void
    {
        $value = new stdClass();
        $value->one = 1;
        $value->two = 2;

        $array = array('__className' => 'stdClass', 'one' => 1, 'two' => 2);

        $encoded = Zend_Json_Encoder::encode($value);
        $this->assertSame($array, Zend_Json_Decoder::decode($encoded));
    }

    public function testObjectAsObject(): void
    {
        $value = new stdClass();
        $value->one = 1;
        $value->two = 2;

        $encoded = Zend_Json_Encoder::encode($value);
        $decoded = Zend_Json_Decoder::decode($encoded, Zend_Json::TYPE_OBJECT);
        $this->assertIsObject($decoded, 'Not decoded as an object');
        $this->assertTrue($decoded instanceof StdClass, 'Not a StdClass object');
        $this->assertTrue(isset($decoded->one), 'Expected property not set');
        $this->assertEquals($value->one, $decoded->one, 'Unexpected value');
    }

    public function testDecodeArrayOfObjects(): void
    {
        $value = '[{"id":1},{"foo":2}]';
        $expect = array(array('id' => 1), array('foo' => 2));
        $this->assertEquals($expect, Zend_Json_Decoder::decode($value));
    }

    public function testDecodeObjectOfArrays(): void
    {
        $value = '{"codeDbVar" : {"age" : ["int", 5], "prenom" : ["varchar", 50]}, "234" : [22, "jb"], "346" : [64, "francois"], "21" : [12, "paul"]}';
        $expect = array(
            'codeDbVar' => array(
                'age'   => array('int', 5),
                'prenom' => array('varchar', 50),
            ),
            234 => array(22, 'jb'),
            346 => array(64, 'francois'),
            21  => array(12, 'paul')
        );
        $this->assertEquals($expect, Zend_Json_Decoder::decode($value));
    }

    protected function _testEncodeDecode($values): void
    {
        foreach ($values as $value) {
            $encoded = Zend_Json_Encoder::encode($value);
            $this->assertEquals($value, Zend_Json_Decoder::decode($encoded));
        }
    }

    public function testEncodeReleaseNumber(): void
    {
        $value = '4.10';
        $this->_testEncodeDecode(array($value));
    }

    public function testEarlyLineBreak(): void
    {
        $expected = array('data' => array(1, 2, 3, 4));

        $json = '{"data":[1,2,3,4' . "\n]}";
        $this->assertEquals($expected, Zend_Json_Decoder::decode($json));

        $json = '{"data":[1,2,3,4 ]}';
        $this->assertEquals($expected, Zend_Json_Decoder::decode($json));
    }

    public function testZf504(): void
    {
        $test = array();
        $this->assertSame('[]', Zend_Json_Encoder::encode($test));

        try {
            $json = '[a"],["a],[][]';
            $test = Zend_Json_Decoder::decode($json);
            $this->fail("Should not be able to decode '$json'");

            $json = '[a"],["a]';
            $test = Zend_Json_Decoder::decode($json);
            $this->fail("Should not be able to decode '$json'");
        } catch (Exception $e) {
            // success
        }

        try {
            $expected = 010;
            $test = Zend_Json_Decoder::decode('010');
            $this->fail('Octal values are not supported in JSON notation');
        } catch (Exception $e) {
            // success
        }
    }

    public function testZf461(): void
    {
        $item1 = new Zend_JsonTest_Item() ;
        $item2 = new Zend_JsonTest_Item() ;
        $everything = array() ;
        $everything['allItems'] = array($item1, $item2) ;
        $everything['currentItem'] = $item1 ;

        try {
            $encoded = Zend_Json_Encoder::encode($everything);
            $this->addToAssertionCount(1);
        } catch (Exception $e) {
            $this->fail('Object cycling checks should check for recursion, not duplicate usage of an item');
        }

        try {
            $encoded = Zend_Json_Encoder::encode($everything, true);
            $this->fail('Object cycling not allowed when cycleCheck parameter is true');
        } catch (Exception $e) {
            $this->assertStringContainsString('Cycles not supported', $e->getMessage());
        }
    }

    public function testZf4053(): void
    {
        $item1 = new Zend_JsonTest_Item() ;
        $item2 = new Zend_JsonTest_Item() ;
        $everything = array() ;
        $everything['allItems'] = array($item1, $item2) ;
        $everything['currentItem'] = $item1 ;

        $options = array('silenceCyclicalExceptions'=>true);

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $encoded = Zend_Json::encode($everything, true, $options);
        $json = '{"allItems":[{"__className":"Zend_JsonTest_Item"},{"__className":"Zend_JsonTest_Item"}],"currentItem":"* RECURSION (Zend_JsonTest_Item) *"}';

        $this->assertEquals($encoded,$json);
    }

    public function testEncodeObject(): void
    {
        $actual  = new Zend_JsonTest_Object();
        $encoded = Zend_Json_Encoder::encode($actual);
        $decoded = Zend_Json_Decoder::decode($encoded, Zend_Json::TYPE_OBJECT);

        $this->assertTrue(isset($decoded->__className));
        $this->assertEquals('Zend_JsonTest_Object', $decoded->__className);
        $this->assertTrue(isset($decoded->foo));
        $this->assertEquals('bar', $decoded->foo);
        $this->assertTrue(isset($decoded->bar));
        $this->assertEquals('baz', $decoded->bar);
        $this->assertFalse(isset($decoded->_foo));
    }

    public function testEncodeClass(): void
    {
        $encoded = Zend_Json_Encoder::encodeClass('Zend_JsonTest_Object');

        $this->assertStringContainsString("Class.create('Zend_JsonTest_Object'", $encoded);
        $this->assertStringContainsString("ZAjaxEngine.invokeRemoteMethod(this, 'foo'", $encoded);
        $this->assertStringContainsString("ZAjaxEngine.invokeRemoteMethod(this, 'bar'", $encoded);
        $this->assertStringNotContainsString("ZAjaxEngine.invokeRemoteMethod(this, 'baz'", $encoded);

        $this->assertStringContainsString('variables:{foo:"bar",bar:"baz"}', $encoded);
        $this->assertStringContainsString('constants : {FOO: "bar"}', $encoded);
    }

    public function testEncodeClasses(): void
    {
        $encoded = Zend_Json_Encoder::encodeClasses(array('Zend_JsonTest_Object', 'Zend_JsonTest'));

        $this->assertStringContainsString("Class.create('Zend_JsonTest_Object'", $encoded);
        $this->assertStringContainsString("Class.create('Zend_JsonTest'", $encoded);
    }

    public function testToJsonSerialization(): void
    {
        $toJsonObject = new ToJsonClass();

        $result = Zend_Json::encode($toJsonObject);

        $this->assertEquals('{"firstName":"John","lastName":"Doe","email":"john@doe.com"}', $result);
    }

    public function testEncodingArrayWithExpr(): void
    {
        $expr = new Zend_Json_Expr('window.alert("Zend Json Expr")');
        $array = array('expr'=>$expr, 'int'=>9, 'string'=>'text');
        $result = Zend_Json::encode($array, false, array('enableJsonExprFinder' => true));
        $expected = '{"expr":window.alert("Zend Json Expr"),"int":9,"string":"text"}';
        $this->assertEquals($expected, $result);
    }

    public function testEncodingObjectWithExprAndInternalEncoder(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = true;

        $expr = new Zend_Json_Expr('window.alert("Zend Json Expr")');
        $obj = new stdClass();
        $obj->expr = $expr;
        $obj->int = 9;
        $obj->string = 'text';
        $result = Zend_Json::encode($obj, false, array('enableJsonExprFinder' => true));
        $expected = '{"__className":"stdClass","expr":window.alert("Zend Json Expr"),"int":9,"string":"text"}';
        $this->assertEquals($expected, $result);
    }

    public function testEncodingObjectWithExprAndExtJson(): void
    {
        if(!function_exists('json_encode')) {
            $this->markTestSkipped('Test only works with ext/json enabled!');
        }

        Zend_Json::$useBuiltinEncoderDecoder = false;

        $expr = new Zend_Json_Expr('window.alert("Zend Json Expr")');
        $obj = new stdClass();
        $obj->expr = $expr;
        $obj->int = 9;
        $obj->string = 'text';
        $result = Zend_Json::encode($obj, false, array('enableJsonExprFinder' => true));
        $expected = '{"expr":window.alert("Zend Json Expr"),"int":9,"string":"text"}';
        $this->assertEquals($expected, $result);
    }

    public function testToJsonWithExpr(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = true;

        $obj = new Zend_Json_ToJsonWithExpr();
        $result = Zend_Json::encode($obj, false, array('enableJsonExprFinder' => true));
        $expected = '{"expr":window.alert("Zend Json Expr"),"int":9,"string":"text"}';
        $this->assertEquals($expected, $result);
    }

    public function testEncodingMultipleNestedSwitchingSameNameKeysWithDifferentJsonExprSettings(): void
    {
        $data = array(
            0 => array(
                'alpha' => new Zend_Json_Expr('function(){}'),
                'beta' => 'gamma',
            ),
            1 => array(
                'alpha' => 'gamma',
                'beta' => new Zend_Json_Expr('function(){}'),
            ),
            2 => array(
                'alpha' => 'gamma',
                'beta' => 'gamma',
            )
        );
        $result = Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));

        $this->assertEquals(
            '[{"alpha":function(){},"beta":"gamma"},{"alpha":"gamma","beta":function(){}},{"alpha":"gamma","beta":"gamma"}]',
            $result
        );
    }

    public function testEncodingMultipleNestedIteratedSameNameKeysWithDifferentJsonExprSettings(): void
    {
        $data = array(
            0 => array(
                'alpha' => 'alpha'
            ),
            1 => array(
                'alpha' => 'beta',
            ),
            2 => array(
                'alpha' => new Zend_Json_Expr('gamma'),
            ),
            3 => array(
                'alpha' => 'delta',
            ),
            4 => array(
                'alpha' => new Zend_Json_Expr('epsilon'),
            )
        );
        $result = Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));

        $this->assertEquals('[{"alpha":"alpha"},{"alpha":"beta"},{"alpha":gamma},{"alpha":"delta"},{"alpha":epsilon}]', $result);
    }

    public function testDisabledJsonExprFinder(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = true;

        $data = array(
            0 => array(
                'alpha' => new Zend_Json_Expr('function(){}'),
                'beta' => 'gamma',
            ),
        );
        $result = Zend_Json::encode($data);

        $this->assertEquals(
            '[{"alpha":{"__className":"Zend_Json_Expr"},"beta":"gamma"}]',
            $result
        );
    }

    public function testEncodeWithUtf8IsTransformedToPackedSyntax(): void
    {
        $data = array("\xD0\x9E\xD1\x82\xD0\xBC\xD0\xB5\xD0\xBD\xD0\xB0");
        $result = Zend_Json_Encoder::encode($data);

        $this->assertEquals('["\u041e\u0442\u043c\u0435\u043d\u0430"]', $result);
    }

    public function testEncodeWithUtf8IsTransformedSolarRegression(): void
    {
        // Double-encoded UTF-8 string: each UTF-8 byte pair re-encoded as UTF-8
        $doubleEncoded = "\x68\xC3\x83\xC2\xA9\x6C\x6C\xC3\x83\xC2\xB6\x20\x77\xC3\x83\xC2\xB8\x72\xC3\x85\xE2\x80\x9A\x64";
        $expect = '"h\u00c3\u00a9ll\u00c3\u00b6 w\u00c3\u00b8r\u00c5\u201ad"';
        $this->assertEquals($expect, Zend_Json_Encoder::encode($doubleEncoded));
        $this->assertEquals($doubleEncoded, Zend_Json_Decoder::decode($expect));

        $expect = '"\u0440\u0443\u0441\u0441\u0438\u0448"';
        $russianStr = "\xD1\x80\xD1\x83\xD1\x81\xD1\x81\xD0\xB8\xD1\x88";
        $this->assertEquals($expect, Zend_Json_Encoder::encode($russianStr));
        $this->assertEquals($russianStr, Zend_Json_Decoder::decode($expect));
    }

    public function testEncodeUnicodeStringSolarRegression(): void
    {
        // Double-encoded UTF-8 string
        $value    = "\x68\xC3\x83\xC2\xA9\x6C\x6C\xC3\x83\xC2\xB6\x20\x77\xC3\x83\xC2\xB8\x72\xC3\x85\xE2\x80\x9A\x64";
        $expected = 'h\u00c3\u00a9ll\u00c3\u00b6 w\u00c3\u00b8r\u00c5\u201ad';
        $this->assertEquals($expected, Zend_Json_Encoder::encodeUnicodeString($value));

        $value    = "\xC3\xA4";
        $expected = '\u00e4';
        $this->assertEquals($expected, Zend_Json_Encoder::encodeUnicodeString($value));

        $value    = "\xE1\x82\xA0\xE1\x82\xA8";
        $expected = '\u10a0\u10a8';
        $this->assertEquals($expected, Zend_Json_Encoder::encodeUnicodeString($value));
    }

    public function testDecodeUnicodeStringSolarRegression(): void
    {
        // Double-encoded UTF-8 string
        $expected = "\x68\xC3\x83\xC2\xA9\x6C\x6C\xC3\x83\xC2\xB6\x20\x77\xC3\x83\xC2\xB8\x72\xC3\x85\xE2\x80\x9A\x64";
        $value    = 'h\u00c3\u00a9ll\u00c3\u00b6 w\u00c3\u00b8r\u00c5\u201ad';
        $this->assertEquals($expected, Zend_Json_Decoder::decodeUnicodeString($value));

        $expected = "\xC3\xA4";
        $value    = '\u00e4';
        $this->assertEquals($expected, Zend_Json_Decoder::decodeUnicodeString($value));

        $value    = '\u10a0';
        $expected = "\xE1\x82\xA0";
        $this->assertEquals($expected, Zend_Json_Decoder::decodeUnicodeString($value));
    }

    public function testEncodeWithUtf8IsTransformedSolarRegressionEqualsJsonExt(): void
    {
        if(function_exists('json_encode') === false) {
            $this->markTestSkipped('Test can only be run, when ext/json is installed.');
        }

        // Double-encoded UTF-8 string
        $doubleEncoded = "\x68\xC3\x83\xC2\xA9\x6C\x6C\xC3\x83\xC2\xB6\x20\x77\xC3\x83\xC2\xB8\x72\xC3\x85\xE2\x80\x9A\x64";
        $this->assertEquals(
            json_encode($doubleEncoded),
            Zend_Json_Encoder::encode($doubleEncoded)
        );

        $russianStr = "\xD1\x80\xD1\x83\xD1\x81\xD1\x81\xD0\xB8\xD1\x88";
        $this->assertEquals(
            json_encode($russianStr),
            Zend_Json_Encoder::encode($russianStr)
        );
    }

    public function testUtf8JsonExprFinder(): void
    {
        $data = array("\xD0\x9E\xD1\x82\xD0\xBC\xD0\xB5\xD0\xBD\xD0\xB0" => new Zend_Json_Expr('foo'));

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $result = Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));
        $this->assertEquals('{"\u041e\u0442\u043c\u0435\u043d\u0430":foo}', $result);
        Zend_Json::$useBuiltinEncoderDecoder = false;

        $result = Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));
        $this->assertEquals('{"\u041e\u0442\u043c\u0435\u043d\u0430":foo}', $result);
    }

    public function testKommaDecimalIsConvertedToCorrectJsonWithDot(): void
    {
        $localeInfo = localeconv();
        if($localeInfo['decimal_point'] != ",") {
            $this->markTestSkipped('This test only works for platforms where , is the decimal point separator.');
        }

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $this->assertEquals('[1.20, 1.68]', Zend_Json_Encoder::encode(array(
            (float)'1,20', (float)'1,68'
        )));
    }

    public function testEncodeObjectImplementingIterator(): void
    {
        $iterator = new ArrayIterator(array(
            'foo' => 'bar',
            'baz' => 5
        ));
        $target = '{"__className":"ArrayIterator","foo":"bar","baz":5}';

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $this->assertEquals($target, Zend_Json::encode($iterator));
    }

    public function testEncodeObjectImplementingIteratorAggregate(): void
    {
        $iterator = new ZF12347_IteratorAggregate();
        $target = '{"__className":"ZF12347_IteratorAggregate","foo":"bar","baz":5}';

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $this->assertEquals($target, Zend_Json::encode($iterator));
    }

    public function testNativeJsonEncoderWillProperlyEncodeSolidusInStringValues(): void
    {
        $source = '</foo><foo>bar</foo>';
        $target = '"<\\/foo><foo>bar<\\/foo>"';

        Zend_Json::$useBuiltinEncoderDecoder = false;
        $this->assertEquals($target, Zend_Json::encode($source));
    }

    public function testBuiltinJsonEncoderWillProperlyEncodeSolidusInStringValues(): void
    {
        $source = '</foo><foo>bar</foo>';
        $target = '"<\\/foo><foo>bar<\\/foo>"';

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $this->assertEquals($target, Zend_Json::encode($source));
    }

    public function testDecodingInvalidJsonShouldRaiseAnException(): void
    {
        $this->expectException(Zend_Json_Exception::class);
        Zend_Json::decode(' some string ');
    }

    public function testIteratorWithoutDefinedKey(): void
    {
        $inputValue = new ArrayIterator(array('foo'));
        $encoded = Zend_Json_Encoder::encode($inputValue);
        $expectedDecoding = '{"__className":"ArrayIterator","0":"foo"}';
        $this->assertEquals($encoded, $expectedDecoding);
    }

    public function testEncoderEscapesNamespacedClassNamesProperly(): void
    {
        require_once __DIR__ . '/_files/ZF11356-NamespacedClass.php';
        $className = '\Zend\JsonTest\ZF11356\NamespacedClass';
        $inputValue = new $className(array('foo'));

        $encoded = Zend_Json_Encoder::encode($inputValue);
        $this->assertEquals(
            '{"__className":"Zend\\\\JsonTest\\\\ZF11356\\\\NamespacedClass","0":"foo"}',
            $encoded
        );
    }

    public function testJsonPrettyPrintWorksWithTxtOutputFormat(): void
    {
        $o = new stdClass;
        $o->four = 4;
        $o->foo = array(1,2,3);

        $jsonstr = Zend_Json::encode($o);

        $targetTxtOutput = "{\n\t\"four\":4,\n\t\"foo\":[\n\t\t1,\n\t\t2,\n\t\t3\n\t]\n}";
        $this->assertEquals($targetTxtOutput, Zend_Json::prettyPrint($jsonstr));
    }

    public function testJsonPrettyPrintWorksWithHtmlOutputFormat(): void
    {
        $o = new stdClass;
        $o->four = 4;
        $o->foo = array(1,2,3);

        $jsonstr = Zend_Json::encode($o);
        $targetHtmlOutput = '{<br />&nbsp;&nbsp;&nbsp;&nbsp;"four":4,<br />&nbsp;&nbsp;&nbsp;&nbsp;"foo":[<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;1,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;2,<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;3<br />&nbsp;&nbsp;&nbsp;&nbsp;]<br />}';
        $this->assertEquals($targetHtmlOutput, Zend_Json::prettyPrint($jsonstr, array('format' => 'html')));
    }

    public function testEncodeWillUseToArrayMethodWhenAvailable(): void
    {
        $o = new ZF11167_ToArrayClass();
        $objJson = Zend_Json::encode($o);
        $arrJson = Zend_Json::encode($o->toArray());
        $this->assertSame($arrJson, $objJson);
    }

    public function testEncodeWillUseToJsonWhenBothToJsonAndToArrayMethodsAreAvailable(): void
    {
        $o = new ZF11167_ToArrayToJsonClass();
        $objJson = Zend_Json::encode($o);
        $this->assertEquals('"bogus"', $objJson);
        $arrJson = Zend_Json::encode($o->toArray());
        $this->assertNotSame($objJson, $arrJson);
    }

    public function testWillEncodeArrayOfObjectsEachWithToJsonMethod(): void
    {
        $array = array('one'=>new ToJsonClass());
        $expected = '{"one":{"__className":"ToJsonClass","firstName":"John","lastName":"Doe","email":"john@doe.com"}}';

        Zend_Json::$useBuiltinEncoderDecoder = true;
        $json = Zend_Json::encode($array);
        $this->assertEquals($expected, $json);
    }

    public function testWillDecodeStructureWithEmptyKeyToObjectProperly(): void
    {
        Zend_Json::$useBuiltinEncoderDecoder = true;

        $json = '{"":"test"}';
        $object = Zend_Json::decode($json, Zend_Json::TYPE_OBJECT);
        $this->assertTrue(isset($object->_empty_));
        $this->assertEquals('test', $object->_empty_);
    }
}

class Zend_JsonTest_Item
{
}

class Zend_JsonTest_Object
{
    const FOO = 'bar';

    public $foo = 'bar';
    public $bar = 'baz';

    protected $_foo = 'fooled you';

    public function foo($bar, $baz)
    {
    }

    public function bar($baz)
    {
    }

    protected function baz()
    {
    }
}

class ToJsonClass
{
    private $_firstName = 'John';

    private $_lastName = 'Doe';

    private $_email = 'john@doe.com';

    public function toJson()
    {
        $data = array(
            'firstName' => $this->_firstName,
            'lastName'  => $this->_lastName,
            'email'     => $this->_email
        );

        return Zend_Json::encode($data);
    }
}

class ZF11167_ToArrayClass
{
    private $_firstName = 'John';

    private $_lastName = 'Doe';

    private $_email = 'john@doe.com';

    public function toArray()
    {
        $data = array(
            'firstName' => $this->_firstName,
            'lastName'  => $this->_lastName,
            'email'     => $this->_email
        );
        return $data;
    }
}

class ZF11167_ToArrayToJsonClass extends ZF11167_ToArrayClass
{
    public function toJson()
    {
        return Zend_Json::encode('bogus');
    }
}

class Zend_Json_ToJsonWithExpr
{
    private $_string = 'text';
    private $_int = 9;
    private $_expr = 'window.alert("Zend Json Expr")';

    public function toJson()
    {
        $data = array(
            'expr'   => new Zend_Json_Expr($this->_expr),
            'int'    => $this->_int,
            'string' => $this->_string
        );

        return Zend_Json::encode($data, false, array('enableJsonExprFinder' => true));
    }
}

class ZF12347_IteratorAggregate implements IteratorAggregate
{
    protected $array = array(
        'foo' => 'bar',
        'baz' => 5
    );

    #[ReturnTypeWillChange]
    public function getIterator() {
        return new ArrayIterator($this->array);
    }
}
