<?php
require '../vendor/autoload.php';

use Opis\JsonSchema\{
    Validator, ValidationResult, ValidationError, 
    Schema, IFilter, FilterContainer 
};

define('IS_LOCAL_TEST', true);

class MatchFilter implements IFilter
{
    public function validate($value, array $args): bool {
    	return true;
    }
}

class JsonValidationException extends Exception
{
	protected $_validationErrorData = null;
	
    public function setValidationErrorData($errorData)
    {
    	$this->_validationErrorData = $errorData;
    }

    public function getValidationErrorData()
    {
    	return $this->_validationErrorData;
    }
}

class JsonValidator {


	protected $_isLocalTest = false;

	protected $_validationResults = null;
	/*
	* The directory where we will get JSONs to validate.
	* Is set in init() method. Will be different for "real"
	* usage and local testing(which means we can manually change the 
	* JSONs for test pupropses).
	*/
	protected $_dataSourceDirPrefix = null;
	
	/*
	* JSONs mapped to the schemas that will be used to validate
	* these JSONs.
 	*/
	protected $_jsonsToSchemasMapping = [

		"validation_schemas/instance_index_validation_schema.json" => [
			
			"configs/instances/Netpay/index.json",
			"configs/instances/Tranzila/index.json",
			"configs/instances/Isracard/index.json",
			//"configs/instances/Payfinder/index.json",
			
			],

		];

	/*
	* Filters used in validation schemas.
	*/
	protected $_schemasFilters = [

		"validation_schemas/instance_index_validation_schema.json" => [

			"filterTarget" => "object",
			"filterName" => "match"

			],

		];

	public function init()
	{
		switch($this->_isLocalTest){
			case true:
				$this->_dataSourceDirPrefix = './local_test_data/';
			break;
			case false:
				$this->_dataSourceDirPrefix = '../';
			break;
		}
	}

	public function setIsLocalTest($isLocalTest)
	{
		$this->_isLocalTest = $isLocalTest;
	}

	public function getValidationResults()
	{
		return $this->_validationResults;
	}

	public function validate()
	{

		$validationResults = [];
		/*
		* Let's validate each JSON accordingly to it's corresponding 
		* validation schema.
 		*/
		foreach($this->_jsonsToSchemasMapping as $schemaFileName => $jsonFilesCollection){

			/*
			* Set new filters collection for each next validation schema.
 			*/
			$filters = new FilterContainer();
			if ( isset($this->_schemasFilters[$schemaFileName]) ){
				$filterName = $this->_schemasFilters[$schemaFileName]['filterName'];
				$filterTarget = $this->_schemasFilters[$schemaFileName]['filterTarget'];
				$filterClassName = ucfirst($filterName) . 'Filter';
				//$filters->add("object", "match", new MatchFilter());	
				$filters->add($filterTarget, $filterName, new $filterClassName());	
			}

			foreach($jsonFilesCollection as $fileName){

				try {
			
					$fileName = $this->_dataSourceDirPrefix . $fileName;
					$currentResult = $this->_validateSingleIndex($fileName, $schemaFileName, $filters);
					if ( !$currentResult['isValid'] ){
						$jsonValidationException = new JsonValidationException($currentResult['errorData']['errorMessage']);
						$jsonValidationException->setValidationErrorData($currentResult['errorData']);
						throw $jsonValidationException;
					} else {
						throw new JsonValidationException('JSON is valid.');
					}

				} catch(Exception $e){
		
					$exceptionFullClassName = get_class($e);
					if(strpos($exceptionFullClassName, "\\") !== false){
						$exceptionClassName = trim(strrchr($exceptionFullClassName, "\\"), "\\");
					} else {
						$exceptionClassName = $exceptionFullClassName;
					}
					$message = $e->getMessage();
					
					switch($exceptionClassName){
						case 'InvalidJsonPointerException':
							$keyPresentInValuesButMissingInFields = trim(strrchr($message, '/'), '/');
							$message = "This key is present in 'values' but is missing in 'fields': " . $keyPresentInValuesButMissingInFields;		
						break;
						case 'JsonValidationException':
							$errorData = $e->getValidationErrorData();
							if ($errorData !== null){
								$message = $errorData['errorMessage'] . " in: " . implode("/", $errorData['pathToTheDataThatCausedTheError']);
							}
						break;
						default:
						break;
					}

					$validationResults[$schemaFileName][$fileName] = $message;
				}

			}//foreach($jsonFilesCollection as $jsonFileToValidate){			

		}//foreach($this->_jsonsToSchemasMapping as $schemaFileName => $jsonFilesCollection){

		$this->_validationResults = $validationResults;

	}

	protected function _validateSingleIndex($indexFileName, $schemaFileName, $filters = false)
	{
		$data = file_get_contents($indexFileName);
		$data = json_decode($data);

		$schema = Schema::fromJsonString(file_get_contents($schemaFileName));

		$validator = new Validator();
		if ($filters !== false){
			$validator->setFilters($filters);
		}

		$result = $validator->schemaValidation($data, $schema);

		if ($result->isValid()) {
		    return ["isValid" => true];
		} else {
		    $error = $result->getFirstError();
		    return [ "isValid" => false, "errorData" => ["errorMessage" => $error->keyword(), 
		    	"dataThatCausedTheError" => $error->data(), "pathToTheDataThatCausedTheError" => $error->dataPointer()] ];
		}
	}

}

$jsonValidator = new JsonValidator();
$jsonValidator->setIsLocalTest(IS_LOCAL_TEST);
$jsonValidator->init();
$jsonValidator->validate();
echo '<pre>';
print_r($jsonValidator->getValidationResults());
echo '</pre>';
exit;

/*
$filters = new FilterContainer();
$filters->add("object", "match", new MatchFilter());

$schemaFileName = "./validation_schemas/instance_index_validation_schema.json";
$indexFilesCollection = [
	"./instances_index_files/netpay_index.json",
	"./instances_index_files/tranzilla_index.json",
	"./instances_index_files/isracard_index.json" 
	];
$validationResults = [];

foreach ($indexFilesCollection as $fileName) {

	try {
			
		$currentResult = validateSingleIndex($fileName, $schemaFileName, $filters);
		if ( !$currentResult['isValid'] ){
			$jsonValidationException = new JsonValidationException($currentResult['errorData']['errorMessage']);
			$jsonValidationException->setValidationErrorData($currentResult['errorData']);
			throw $jsonValidationException;
		} else {
			throw new JsonValidationException('JSON is valid.');
		}

	} catch(Exception $e){
		
		$exceptionFullClassName = get_class($e);
		if(strpos($exceptionFullClassName, "\\") !== false){
			$exceptionClassName = trim(strrchr($exceptionFullClassName, "\\"), "\\");
		} else {
			$exceptionClassName = $exceptionFullClassName;
		}
		$message = $e->getMessage();
		
		switch($exceptionClassName){
			case 'InvalidJsonPointerException':
				$keyPresentInValuesButMissingInFields = trim(strrchr($message, '/'), '/');
				$message = "This key is present in 'values' but is missing in 'fields': " . $keyPresentInValuesButMissingInFields;		
			break;
			case 'JsonValidationException':
				$errorData = $e->getValidationErrorData();
				if ($errorData !== null){
					$message = $errorData['errorMessage'] . " in: " . implode("/", $errorData['pathToTheDataThatCausedTheError']);
				}
			break;
			default:
			break;
		}

		$validationResults[$fileName] = $message;
	}

}//foreach


foreach($validationResults as $fileName => $message){
	$html = $fileName . ' : ' . $message;
	echo htmlentities($html);
	echo '<br />';
}

function validateSingleIndex($indexFileName, $schemaFileName, $filters = false)
{
	$data = file_get_contents($indexFileName);
	$data = json_decode($data);

	$schema = Schema::fromJsonString(file_get_contents($schemaFileName));

	$validator = new Validator();
	if ($filters !== false){
		$validator->setFilters($filters);
	}

	$result = $validator->schemaValidation($data, $schema);

	if ($result->isValid()) {
	    return ["isValid" => true];
	} else {
	    $error = $result->getFirstError();
	    return [ "isValid" => false, "errorData" => ["errorMessage" => $error->keyword(), 
	    	"dataThatCausedTheError" => $error->data(), "pathToTheDataThatCausedTheError" => $error->dataPointer()] ];
	}
}

*/

?>