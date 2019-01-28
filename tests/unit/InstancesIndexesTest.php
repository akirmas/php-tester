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
        $this->_pathToFieldsChildSchema = 'instances/fields_schema.json';
        $this->_instance = 'Netpay';

        //Let's access to schema array:
        $schema = Opis\JsonSchema\Schema::fromJsonString(file_get_contents('configs/' . $this->_pathToSchema));
        $reflectedSchema = new ReflectionClass($schema);
        $internalSchema = $reflectedSchema->getProperty('internal');
        $internalSchema->setAccessible(true);
        $this->_schemaArray = $internalSchema->getValue($schema);

        $this->_setupFieldsChildSchema();
    }

    protected function _setupFieldsChildSchema()
    {
        $schema = Opis\JsonSchema\Schema::fromJsonString(file_get_contents('configs/' . $this->_pathToFieldsChildSchema));
        $reflectedSchema = new ReflectionClass($schema);
        $internalSchema = $reflectedSchema->getProperty('internal');
        $internalSchema->setAccessible(true);
        $this->_fieldsChildSchemaArray = $internalSchema->getValue($schema);
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

    public function testPropertyPresentInValuesButMissingInFields()
    {
        $pathToBrokenIndex = 'tests/instances/'
            . $this->_instance . '/index_property_present_in_values_but_missing_in_fields.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($pathToBrokenIndex);
        $validationResults = $validationResultAndErrorData['validationResults'];
        foreach ($validationResults[$this->_pathToSchema] as $indexName => $indexResult) {
            $this->assertContains("This key is present in 'values' but is missing in 'fields'", $indexResult);
        }
    }

    public function testNotAValidUriInGatewayInEngine()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/'
            . $this->_instance . '/index_not_a_valid_uri_in_gateway_in_engine.json');
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'format');
                $this->assertEquals($indexResult['pathToTheDataThatCausedTheError'], ['request', 'engine', 'gateway']);
            }
        } else {
            $this->fail('Test failed for not a valid URI in request/engine/gateway');
        }
    }

    public function testNotAValidMethodInEngine()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/'
            . $this->_instance . '/index_not_a_valid_method_in_engine.json');
        $errorData = $validationResultAndErrorData['errorData'];
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], 'enum');
                $this->assertEquals($indexResult['pathToTheDataThatCausedTheError'], ['request', 'engine', 'method']);
            }
        } else {
            $this->fail('Test failed for not a valid method in request/engine/method');
        }
    }

    public function testAdditionalPropertyInRootObject()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/'
            . $this->_instance . '/index_additional_property_in_root_object.json');
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing('some additional property', 'root object', $errorData, 'additionalProperties');
    }

    public function testAdditionalPropertyInEngine()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/'
            . $this->_instance . '/index_additional_property_in_engine.json');
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing('some additional property', 'request/engine', $errorData, 'additionalProperties');
    }

    public function testAdditionalPropertyInFields()
    {
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData('tests/instances/'
            . $this->_instance . '/index_additional_property_in_fields.json');
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing('some additional property', 'request/fields', $errorData, 'additionalProperties');
    }

    public function testRootMandatoryPropertiesMissing()
    {
        $rootProperties = $this->_schemaArray['/instances_schema.json#']->required;
        foreach($rootProperties as $property){
            $this->_testSingleMandatoryRootPropertyMissing($property);
        }
    }

    public function testRequestMandatoryPropertiesMissing()
    {
        $requestMandatoryProperties = $this->_schemaArray['/instances_schema.json#']->properties->request->required;
        foreach($requestMandatoryProperties as $property){
            $this->_testSingleRequestPropertyMissing($property);
        }
    }

    /*
     * Test when one of mandatory properties in request/fields is missing.
    */
    public function testOneOfMandatoryPropertiesInFieldsMissing()
    {
        $mandatoryPropertiesInFields = $this->_fieldsChildSchemaArray['/fields_schema.json#']->required;
        foreach ($mandatoryPropertiesInFields as $property) {
            $this->_testSingleMandatoryPropertyInFieldsMissing($property);
        }
    }

    public function testOneOfMandatoryPropertiesInEngineMissing()
    {
        $mandatoryProperties = $this->_schemaArray['/instances_schema.json#']->properties->request->properties->engine->required;
        foreach($mandatoryProperties as $property){
            $this->_testSingleEnginePropertyMissing($property);
        }
    }

    private function _commonCodeForErrorDataProcessing($propertyName, $propertyHolder, $errorData, $errorType)
    {
        if (!empty($errorData)){
            foreach ($errorData[$this->_pathToSchema] as $indexName => $indexResult) {
                $this->assertEquals($indexResult['errorMessage'], $errorType);
            }
        } else {
            switch($errorType){
                case 'required':
                    $this->fail('Test failed for this property missing in ' . $propertyHolder
                        . ': ' . $propertyName);
                break;
                case 'additionalProperties':
                    $this->fail('Test failed for additional properties in ' . $propertyHolder);
                break;
            }
        }
    }

    private function _testSingleEnginePropertyMissing($propertyName)
    {
        $testFileName = 'tests/instances/' . $this->_instance . '/index_mandatory_' . preg_replace('/:/', '_', $propertyName)
            . '_in_engine_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing($propertyName, 'engine', $errorData, 'required');
    }

    private function _testSingleRequestPropertyMissing($propertyName)
    {
        $testFileName = 'tests/instances/' . $this->_instance . '/index_mandatory_' . preg_replace('/:/', '_', $propertyName)
            . '_in_request_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing($propertyName, 'request', $errorData, 'required');
    }

    private function _testSingleMandatoryPropertyInFieldsMissing($propertyName)
    {
        $testFileName = 'tests/instances/' . $this->_instance . '/index_mandatory_' . preg_replace('/:/', '_', $propertyName)
            . '_in_fields_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing($propertyName, 'request/fields', $errorData, 'required');
    }

    private function _testSingleMandatoryRootPropertyMissing($propertyName)
    {
        $testFileName = 'tests/instances/' . $this->_instance . '/index_' . $propertyName . '_missing.json';
        $validationResultAndErrorData = $this->_getBrokenIndexValidationResultAndErrorData($testFileName);
        $errorData = $validationResultAndErrorData['errorData'];
        $this->_commonCodeForErrorDataProcessing($propertyName, 'root object', $errorData, 'required');
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