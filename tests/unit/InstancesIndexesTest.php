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
                'instances/Isracard/index.json'
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
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/Netpay/index_additional_property_in_root_object.json');
        $validationResults = $validationResultAndErrorData['validationResults'];
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'additionalProperties');
            }
        } else {
            $this->assertFalse(true);
        }
    }

    public function testNetpayAdditionalPropertyInFields()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/Netpay/index_additional_property_in_fields.json');
        $validationResults = $validationResultAndErrorData['validationResults'];
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'additionalProperties');
            }
        } else {
            $this->assertFalse(true);
        }
    }

    public function testNetpayResponseMissing()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/Netpay/index_response_missing.json');
        $validationResults = $validationResultAndErrorData['validationResults'];
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'required');
            }
        } else {
            $this->assertFalse(true);
        }
    }

    private function _getBrokenIndexValidationResultAndErrorData($brokenIndexFileName)
    {
        $mapping = [
            $this->_pathToSchema => [
                $brokenIndexFileName
                ]
        ];
        $this->_jsonValidator->setJsonsToSchemasMapping($mapping);
        $this->_jsonValidator->validate();
        $validationResults = $this->_jsonValidator->getValidationResults();
        $errorData = $this->_jsonValidator->getErrorDataArray();
        return ['validationResults' => $validationResults, 'errorData' => $errorData];
    }

}