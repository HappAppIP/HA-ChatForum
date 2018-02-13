<?php
namespace Test;
use Lib\Validate;
use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{


    /**
     * returns the value of a protected or private property.
     *
     * @param $property
     * @param string $object
     * @return mixed
     * @throws ReflectionException
     */
    public function getProtectedPropertyValue($property, $object)
    {
        $property = new ReflectionProperty(get_class($object), $property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }


    /**
     * @param $method
     * @param $object
     * @param array $arguments
     * @return mixed
     * @throws ReflectionException
     *
     *
     * @todo This throws an exception when method contains reference arguments.
     */
    public function invokeProtectedMethod($method, $object, array $arguments = [])
    {
        $method = new ReflectionMethod(get_class($object), $method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $arguments);
    }


    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedErrors
     * @param $expectedParameters
     * @throws \Exception
     *
     * @dataProvider requiredData
     */
    public function testRequired($key, $validators, $parameters, $expectedStatus, $expectedErrors, $expectedParameters)
    {
        $validate = new Mock_Validate();
        try {
            $status = $validate->validateRequired($key, $validators, $parameters);
            $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
        } catch (\UnexpectedValueException $e) {
            $this->assertFalse($expectedStatus, 'Validation triggered an exception');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match (failed validation)');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    public function requiredData()
    {
        return [
            'simple valid' => ['key' => 'valid', 'validators' => [], 'parameters' => ['valid' => 'check!'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['valid' => 'check!']],
            'simple invalid' => ['key' => 'valid', 'validators' => [], 'parameters' => ['invalid' => 'check!'], 'expectedStatus' => false, 'expectedErrors' => ['valid' => VALIDATE_REQUIRED], 'expectedParameters' => ['invalid' => 'check!']],
            'empty valid' => ['key' => 'empty_valid', 'validators' => ['required' => true, 'allow_empty' => true], 'parameters' => ['empty_valid' => ''], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['empty_valid' => '']],
            'empty invalid' => ['key' => 'empty_valid', 'validators' => ['required' => true, 'allow_empty' => false], 'parameters' => ['empty_valid' => ''], 'expectedStatus' => false, 'expectedErrors' => ['empty_valid' => VALIDATE_REQUIRED_EMPTY], 'expectedParameters' => []],
            'required false no default' => ['key' => 'empty_valid', 'validators' => ['required' => false, 'allow_empty' => false], 'parameters' => [], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => []],
            'required false with default' => ['key' => 'set_default', 'validators' => ['required' => false, 'allow_empty' => false, 'default' => 'koekjes'], 'parameters' => [], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['set_default' => 'koekjes']],
            'required false with not empty' => ['key' => 'oops', 'validators' => ['required' => false, 'allow_empty' => false], 'parameters' => ['oops' => ''], 'expectedStatus' => false, 'expectedErrors' => ['oops' => VALIDATE_REQUIRED_EMPTY], 'expectedParameters' => []],
            'required false with not empty given 0' => ['key' => 'oops', 'validators' => ['required' => true, 'allow_empty' => false], 'parameters' => ['oops' => 0], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['oops'=>0]],
            'required false with not empty given "0"' => ['key' => 'oops', 'validators' => ['required' => true, 'allow_empty' => false], 'parameters' => ['oops' => '0'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['oops'=>'0']],
        ];
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedErrors
     * @param $expectedParameters
     * @throws \Exception
     *
     * @dataProvider typeIntData
     */

    public function testTypeInt($key, $validators, $parameters, $expectedStatus, $expectedErrors, $expectedParameters)
    {
        $validate = new Mock_Validate();
        try {
            $status = $validate->validateType($key, $validators, $parameters);
            $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
        } catch (\UnexpectedValueException $e) {
            $this->assertFalse($expectedStatus, 'Validation triggered an exception');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match (failed validation)');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    /**
     * @return array
     */
    public function typeIntData(){
        return [
            'int valid' => ['key' => 'key', 'validators' => ['type' => 'int'], 'parameters' => ['key' => 1], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 1]],
            'int valid' => ['key' => 'key', 'validators' => ['type' => 'int'], 'parameters' => ['key' => '12'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => "12"]],
            'int range valid' => ['key' => 'key', 'validators' => ['type' => 'int', 'min' => 10, 'max' => 20], 'parameters' => ['key' => '15'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 15]],
            'int range valid low' => ['key' => 'key', 'validators' => ['type' => 'int', 'min' => 10, 'max' => 20], 'parameters' => ['key' => '10'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 10]],
            'int range valid high' => ['key' => 'key', 'validators' => ['type' => 'int', 'min' => 10, 'max' => 20], 'parameters' => ['key' => '20'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 20]],
            'int range invalid low' => ['key' => 'key', 'validators' => ['type' => 'int', 'min' => 10, 'max' => 20], 'parameters' => ['key' => '9'], 'expectedStatus' => false, 'expectedErrors' => ['key' => VALIDATE_TYPE_INT_MIN . '10'], 'expectedParameters' => []],
            'int range invalid high' => ['key' => 'key', 'validators' => ['type' => 'int', 'min' => 10, 'max' => 20], 'parameters' => ['key' => '21'], 'expectedStatus' => false, 'expectedErrors' => ['key' => VALIDATE_TYPE_INT_MAX . '20'], 'expectedParameters' => []],
            'int invalid' => ['key' => 'key', 'validators' => ['type' => 'int'], 'parameters' => ['key' => 'twaalf'], 'expectedStatus' => false, 'expectedErrors' => ['key' => VALIDATE_TYPE_INT], 'expectedParameters' => []],
            'int invalid' => ['key' => 'key', 'validators' => ['type' => 'int'], 'parameters' => ['key' => false], 'expectedStatus' => false, 'expectedErrors' => ['key' => VALIDATE_TYPE_INT], 'expectedParameters' => []],
        ];
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedErrors
     * @param $expectedParameters
     * @throws \Exception
     *
     * @dataProvider typeTextData
     */
    public function testTypeText($key, $validators, $parameters, $expectedStatus, $expectedErrors, $expectedParameters)
    {
        $validate = new Mock_Validate();
        try {
            $status = $validate->validateType($key, $validators, $parameters);
            $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
        } catch (\UnexpectedValueException $e) {
            $this->assertFalse($expectedStatus, 'Validation triggered an exception');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match (failed validation)');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    /**
     * @return array
     */
    public function typeTextData(){
        return [
            'text valid' => ['key' => 'key', 'validators' => ['type' => 'text'], 'parameters' => ['key' => 'kaas'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 'kaas']],
            'text invalid' => ['key' => 'key', 'validators' => ['type' => 'text'], 'parameters' => ['key' => false], 'expectedStatus' => false, 'expectedErrors' => ['key' => 'Property must be a string'], 'expectedParameters' => []],
            'text entities default' => ['key' => 'key', 'validators' => ['type' => 'text'], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => [ 'key' => '&lt;script&gt;alert(&quot;exploited&quot;)&lt;/script&gt;']],
            'text entities true' => ['key' => 'key', 'validators' => ['type' => 'text', 'htmlentities' => true], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' =>  '&lt;script&gt;alert(&quot;exploited&quot;)&lt;/script&gt;']],
            'text entities false' => ['key' => 'key', 'validators' => ['type' => 'text', 'htmlentities' => false], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => '<script>alert("exploited")</script>']],

        ];
    }

    public function testTypeUnknown(){
        $validate = new Mock_Validate();
        try {
            $values = ['key' => 'p. utser'];
            $validate->validateType('key', ['type'=> 'unknown_type'], $values);
            $this->assertTrue(false,'Call should trigger exception');
        }catch(\UnexpectedValueException $e) {
            $this->assertCount(0, $values, 'Values should not be returned');
            $this->assertEquals(['key' => VALIDATE_TYPE_UNKNOWN . 'unknown_type'], $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedErrors
     * @param $expectedParameters
     * @throws \Exception
     *
     * @dataProvider typeVarCharData
     */
    public function testTypeVarChar($key, $validators, $parameters, $expectedStatus, $expectedErrors, $expectedParameters)
    {
        $validate = new Mock_Validate();
        try {
            $status = $validate->validateType($key, $validators, $parameters);
            $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
        } catch (\UnexpectedValueException $e) {
            $this->assertFalse($expectedStatus, 'Validation triggered an exception');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match (failed validation)');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    /**
     * @return array
     */
    public function typeVarCharData(){
        return [
            'char valid' => ['key' => 'key', 'validators' => ['type' => 'varchar'], 'parameters' => ['key' => 'kaas'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 'kaas']],
            'char invalid' => ['key' => 'key', 'validators' => ['type' => 'varchar'], 'parameters' => ['key' => false], 'expectedStatus' => false, 'expectedErrors' => ['key' => 'Property must be a string'], 'expectedParameters' => []],
            'char range valid low' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'min_length' => 10, 'max_length' => 20], 'parameters' => ['key' => '1234567890'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => '1234567890']],
            'char range valid high' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'min_length' => 10, 'max_length' => 20], 'parameters' => ['key' => '12345678901234567890'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => '12345678901234567890']],
            'char range invalid low' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'min_length' => 10, 'max_length' => 20], 'parameters' => ['key' => '123456789'], 'expectedStatus' => false, 'expectedErrors' => ['key' => 'Minimum length of varchar is 10'], 'expectedParameters' => []],
            'char range invalid high' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'min_length' => 10, 'max_length' => 20], 'parameters' => ['key' => '123456789012345678901'], 'expectedStatus' => false, 'expectedErrors' => ['key' => 'Maximum length of varchar is 20'], 'expectedParameters' => []],
            'char entities default' => ['key' => 'key', 'validators' => ['type' => 'varchar'], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => [ 'key' => '&lt;script&gt;alert(&quot;exploited&quot;)&lt;/script&gt;']],
            'char entities true' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'htmlentities' => true], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' =>  '&lt;script&gt;alert(&quot;exploited&quot;)&lt;/script&gt;']],
            'char entities false' => ['key' => 'key', 'validators' => ['type' => 'varchar', 'htmlentities' => false], 'parameters' => ['key' => '<script>alert("exploited")</script>'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => '<script>alert("exploited")</script>']],

        ];
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedErrors
     * @param $expectedParameters
     * @throws \Exception
     *
     * @dataProvider typeEnumData
     */

    public function testTypeEnum($key, $validators, $parameters, $expectedStatus, $expectedErrors, $expectedParameters)
    {
        $validate = new Mock_Validate();
        try {
            $status = $validate->validateType($key, $validators, $parameters);
            $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
        } catch (\UnexpectedValueException $e) {
            $this->assertFalse($expectedStatus, 'Validation triggered an exception');
            $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match (failed validation)');
            $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match (failed validation)');
        }
    }

    /**
     * @return array
     */
    public function typeEnumData(){
        return [
            'enum valid' => ['key' => 'key', 'validators' => ['type' => 'enum', 'enum' => ['valid', 'perfect']], 'parameters' => ['key' => 'valid'], 'expectedStatus' => true, 'expectedErrors' => [], 'expectedParameters' => ['key' => 'valid']],
            'enum invalid' => ['key' => 'key', 'validators' => ['type' => 'enum', 'enum' => ['valid', 'perfect']], 'parameters' => ['key' => 'invalid'], 'expectedStatus' => false, 'expectedErrors' => ['key' => 'Enum value should be one of: valid, perfect'], 'expectedParameters' => []],
        ];
    }

    public function testTypeNotSet(){
        $validate = new Mock_Validate();
        try {
            $values = [];
            $value = $validate->validateType('key', ['type'=> 'unknown_type'], $values);
            $this->assertTrue($value);
            $this->assertCount(0, $values);

        }catch(\UnexpectedValueException $e) {
            $this->assertFalse(true, 'Method call should not throw an exception');
        }
    }

    /**
     * @param $ruleset
     * @param $parameters
     * @param $expectedStatus
     * @param $expectedParameters
     * @param $expectedErrors
     * @throws \Exception
     *
     * @dataProvider validateData
     */
    public function testValidate($ruleset, $parameters, $expectedStatus, $expectedParameters, $expectedErrors){
        $validate = new Mock_Validate();
        $status=$validate->validate($ruleset, $parameters);
        $this->assertEquals($expectedStatus, $status, 'Expected status does not match');
        $this->assertEquals($expectedParameters, $parameters, 'Expected parameters do not match');
        $this->assertEquals($expectedErrors, $validate->getErrors(), 'Expected errors do not match');
    }

    public function validateData(){
        return [
            'validate nothing' => ['ruleset' => [], 'parameters' => ['key' => 'value'], 'expectedStatus' => true, 'expectedParameters' => [], 'expectedErrors' => []],
            'validate string invalid, int valid' => ['ruleset' => ['int' => ['type' => 'int'], 'char' => ['type' => 'varchar']], 'parameters' => ['int' => '3', 'char' => ['bla']], 'expectedStatus' => false, 'expectedParameters' => ['int' => 3], 'expectedErrors' => ['char' => 'Property must be a string']],
            'validate string invalid, int valid enum both' => ['ruleset' => ['int' => ['type' => 'int'], 'char' => ['type' => 'varchar'], 'enum_1' => ['type' => 'enum', 'enum' => ['a', 'b']], 'enum_2' => ['type' => 'enum', 'enum' => ['a', 'b']]], 'parameters' => ['int' => '3', 'char' => ['bla'], 'enum_1' => 'a'], 'expectedStatus' => false, 'expectedParameters' => ['int' => 3, 'enum_1' => 'a'], 'expectedErrors' => ['char' => 'Property must be a string',     'enum_2' => 'Property is required'
            ]],

        ];
    }

}


// References don't work well in reflection... so lets use a old fashioned mock object.
class Mock_Validate extends Validate
{

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function validateRequired($key, $validators, &$parameters)
    {
        return $this->_validateRequired($key, $validators, $parameters);
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function validateType($key, $validators, &$parameters)
    {
        return $this->_validateType($key, $validators, $parameters);
    }
}
