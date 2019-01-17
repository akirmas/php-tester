<?php
require '../vendor/autoload.php';

use Opis\JsonSchema\{
    Validator, ValidationResult, ValidationError, 
    Schema, IFilter, FilterContainer 
};

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

$filters = new FilterContainer();
$filters->add("object", "match", new MatchFilter());

$schemaFileName = "instance_index_validation_schema.json";
$indexFilesCollection = [
	"./instances_index_files/netpay_index.json",
	//"./instances_index_files/tranzilla_index.json",
	//"./instances_index_files/isracard_index.json" 
	];

try {
	foreach ($indexFilesCollection as $fileName) {
		
		$currentResult = validateSingleIndex($fileName, $schemaFileName, $filters);
		if ( !$currentResult['isValid'] ){
			$jsonValidationException = new JsonValidationException($currentResult['errorData']['errorMessage']);
			$jsonValidationException->setValidationErrorData($currentResult['errorData']);
			throw $jsonValidationException;
		} else {
			throw new JsonValidationException('JSON is valid.');
		}
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

	echo htmlentities($message);
	exit;
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


?>