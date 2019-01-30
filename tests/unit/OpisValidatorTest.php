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
        $validator = new Opis\JsonSchema\Validator();
        $data = json_decode('{}');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{}');
        $result = $validator->schemaValidation($data, $schema);
        $this->assertInstanceOf('Opis\JsonSchema\ValidationResult', $result);
    }

    public function testSchemaValidationMethodReturnsNotIsValidResult()
    {
        $validator = new Opis\JsonSchema\Validator();
        $data = json_decode('{ "name": [] }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $validator->schemaValidation($data, $schema);
        $this->assertFalse($result->isValid());
    }


    public function testSchemaValidationMethodReturnsIsValidResult()
    {
        $validator = new Opis\JsonSchema\Validator();
        $data = json_decode('{ "name": "Andrii" }');
        $schema = \Opis\JsonSchema\Schema::fromJsonString('{ "type": "object", "properties": { "name": { "type": "string" } } }');
        $result = $validator->schemaValidation($data, $schema);
        $this->assertTrue($result->isValid());
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