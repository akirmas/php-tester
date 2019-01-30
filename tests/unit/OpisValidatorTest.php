<?php
require_once 'configs/validation_classes.php';

class OpisValidatorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $_validator = null;
    
    protected function _before()
    {
        $this->_validator = new Opis\JsonSchema\Validator();
    }

    protected function _after()
    {
    }

    // tests
    public function testValidatorObjectInstantiation()
    {
        $validator = new Opis\JsonSchema\Validator();
        $this->assertInstanceOf('Opis\JsonSchema\Validator', $validator);
    }

    public function testSchemaValidationMethodReturnsInstanceOfValidationResultClass()
    {
        $data = json_decode('{}');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{}');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertInstanceOf('Opis\JsonSchema\ValidationResult', $result);
    }

    public function testSchemaValidationMethodReturnsNotIsValidResult()
    {
        $data = json_decode('{ "name": [] }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testSchemaValidationMethodReturnsIsValidResult()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testAdditionalPropertiesForbiddenButPresent()
    {
        $data = json_decode('{ "name": "Andrii", "surname": "Shykov" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "additionalProperties": false
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    public function testAdditionalPropertiesForbiddenAndNotPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "additionalProperties": false
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testRequiredPropertiesNotPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "required": ["surname"]
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    public function testRequiredPropertiesPresent()
    {
        $data = json_decode('{ "name": "Andrii", "surname": "Shykov" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "type": "string" }
                               },
                               "required": ["surname"]
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testEnumPropertiesPresent()
    {
        $data = json_decode('{ "name": "Andrii" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "name": { "enum": ["Andrii", "Alex"] }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testEnumPropertiesIsNotPresent()
    {
        $data = json_decode('{ "name": "Sasha" }');
        $schemaString = '{ "type": "object",
                            "properties": {
                                    "name": { "enum": ["Andrii", "Alex"] }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testFormatUriForValidUri()
    {
        $data = json_decode('{ "gateway": "http://google.com" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "gateway": { "type": "string", "format": "uri" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testFormatUriForNotValidUri()
    {
        $data = json_decode('{ "gateway": "~http://google.com" }');
        $schemaString = '{ "type": "object",
                               "properties": {
                                    "gateway": { "type": "string", "format": "uri" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testPropertyNamesForValidPropertyNames()
    {
        $data = json_decode('{ "gateway": "http://google.com" }');
        $schemaString = '{ "type": "object",
                           "propertyNames": {
                                "type": "string",
                                "minLength": 4
                           },
                               "properties": {
                                    "gateway": { "type": "string" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
    }

    public function testPropertyNamesForInvalidPropertyNames()
    {
        $data = json_decode('{ "url": "http://google.com" }');
        $schemaString = '{ "type": "object",
                           "propertyNames": {
                                "type": "string",
                                "minLength": 4
                           },
                               "properties": {
                                    "gateway": { "type": "string" }
                               }
                            }';
        $schema = \Opis\JsonSchema\Schema::fromJsonString($schemaString);
        $result = $this->_validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }

    protected function _writeObjectToLog($object, $logName = 'some_log.txt')
    {
        ob_start();
        echo "\nCurrent object:\n";
        echo json_encode($object, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
        file_put_contents($logName, ob_get_contents());
        ob_end_clean();
    }


}