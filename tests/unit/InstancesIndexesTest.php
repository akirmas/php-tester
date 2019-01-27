<?php 
require_once 'configs/validation_classes.php';

class InstancesIndexesTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        $this->_jsonValidator = new JsonValidator();
        $this->_jsonValidator->init();
        $this->_pathToSchema = 'instances/schema.json';
    }

    protected function _after()
    {
    }

    // Test if each instance's index is valid.
    public function testRealIndexes()
    {
        $mapping = [
            $this->_pathToSchema => [
                'instances/Netpay/index.json',
                'instances/Tranzila/index.json',
                //'instances/Isracard/index.json'
                ]
        ];
        $this->_jsonValidator->setJsonsToSchemasMapping($mapping);
        $this->_jsonValidator->validate();
        $validationResults = $this->_jsonValidator->getValidationResults();
        foreach ($validationResults[$this->_pathToSchema] as $indexName => $indexResult) {
            $this->assertEquals($indexResult, 'JSON is valid.');
        }
    }

    public function testNetpayAdditionalPropertyInRootObject()
    {
        $mapping = [
            $this->_pathToSchema => [
                'tests/instances/Netpay/index_additional_property_in_root_object.json'
                ]
        ];
        $this->_jsonValidator->setJsonsToSchemasMapping($mapping);
        $this->_jsonValidator->validate();
        $validationResults = $this->_jsonValidator->getValidationResults();
        $errorData = $this->_jsonValidator->getErrorDataArray();
        foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
            $this->assertEquals($indexResult['errorMessage'], 'additionalProperties');
        }
    }

    public function testNetpayAdditionalPropertyInFields()
    {
        $mapping = [
            $this->_pathToSchema => [
                'tests/instances/Netpay/index_additional_property_in_fields.json'
                ]
        ];
        $this->_jsonValidator->setJsonsToSchemasMapping($mapping);
        $this->_jsonValidator->validate();
        $validationResults = $this->_jsonValidator->getValidationResults();
        $errorData = $this->_jsonValidator->getErrorDataArray();
        foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
            $this->assertEquals($indexResult['errorMessage'], 'additionalProperties');
        }
    }

}