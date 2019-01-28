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
            $this->fail('Test failed for additional property in root object.');
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
            $this->fail('Test failed for additional property in request/fields.');
        }
    }

    public function testNetpayRootPropertiesMissing()
    {
        //TODO: Take these properties from schema.json
        $rootProperties = ['request', 'response'];
        foreach($rootProperties as $property){
            $this->_testNetpaySingleRootPropertyMissing($property);
        }
    }

    /*
     * Test when one of mandatory properties in request/fields is missing.
    */
    public function testNetpayOneOfMandatoryPropertiesInFieldsMissing()
    {
        //TODO: Take these properties from schema.json
        $mandatoryPropertiesInFields = ['email', 'currency:final'];
        foreach ($mandatoryPropertiesInFields as $property) {
            $this->_testNetpaySingleMandatoryPropertyInFieldsMissing($property);
        }
    }

    private function _testNetpaySingleMandatoryPropertyInFieldsMissing($propertyName)
    {
        $testFileName = 'tests/instances/Netpay/index_mandatory_' . preg_replace('/:/', '_', $propertyName)
            . '_in_fields_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $validationResults = $validationResultAndErrorData['validationResults'];
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'required');
            }
        } else {
            $this->fail('Test failed for this property missing in request/fields: ' . $propertyName);
        }
    }

    private function _testNetpaySingleRootPropertyMissing($propertyName)
    {
        $testFileName = 'tests/instances/Netpay/index_' . $propertyName . '_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $validationResults = $validationResultAndErrorData['validationResults'];
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'required');
            }
        } else {
            $this->fail('Test failed for this root property missing in schema: ' . $propertyName);
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