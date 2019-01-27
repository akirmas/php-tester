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
    }

    protected function _after()
    {
    }

    // Test if each instance's index is valid.
    public function testRealIndexes()
    {
        $jsonValidator = new JsonValidator();
        $jsonValidator->init();
        $pathToSchema = 'instances/schema.json';
        $realIndexesMapping = [
            $pathToSchema => [
                "instances/Netpay/index.json",
                "instances/Tranzila/index.json",
                "instances/Isracard/index.json"
                ]
        ];
        $jsonValidator->setJsonsToSchemasMapping($realIndexesMapping);
        $jsonValidator->validate();
        $validationResults = $jsonValidator->getValidationResults();
        foreach ($validationResults[$pathToSchema] as $indexName => $indexResult) {
            $this->assertEquals($indexResult, "JSON is valid.");
        }
    }
}