<?php
require_once(__DIR__.'../../vendor/autoload.php');

use Opis\JsonSchema\{
	Validator, ValidationResult, ValidationError, 
	Schema, IFilter, FilterContainer, Loaders
};

class MatchFilter implements IFilter {
	public function validate($value, array $args): bool {
		return true;
	}
}

class JsonValidationException extends Exception {
	private $_validationErrorData = null;
	
	public function setValidationErrorData($errorData) {
		$this->_validationErrorData = $errorData;
	}

	public function getValidationErrorData() {
		return $this->_validationErrorData;
	}
}

class JsonValidator {
	private $_validationResults = [];
	/*
	* The directory where we will get JSONs to validate.
	* Relative to script
	*/
	private $_dataSourceDirPrefix = '';
	
	/*
	* JSONs mapped to the schemas that will be used to validate
	* these JSONs.
	* TODO: should be only directory - 'instance' is this case.
	* Schema is "$dir/schema.json", validate *.json in $dir
 	*/
	private $_jsonsToSchemasMapping = [
	"instances/schema.json" => [
		"instances/Netpay/index.json",
		"instances/Tranzila/index.json",
		"instances/Isracard/index.json"
		]
	];

	/*
	* Filters used in validation schemas.
	*/
	private $_schemasFilters = [
		"instances/schema.json" => [
			"filterTarget" => "object",
			"filterName" => "match"
		]
	];

	public function init($dir = __DIR__) {
		$this->_dataSourceDirPrefix = $dir;
	}

	public function getValidationResults() {
		return $this->_validationResults;
	}

	public function validate() {
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
				$filters->add($filterTarget, $filterName, new $filterClassName());	
			}

			foreach($jsonFilesCollection as $fileName) {
				try {
					$currentResult = $this->_validateSingleIndex($fileName, $schemaFileName, $filters);
					if ( !$currentResult['isValid'] ){
						$jsonValidationException = new JsonValidationException($currentResult['errorData']['errorMessage']);
						$jsonValidationException->setValidationErrorData($currentResult['errorData']);
						throw $jsonValidationException;
					} else
						throw new JsonValidationException('JSON is valid.');
				} catch(Exception $e) {
					$exceptionFullClassName = get_class($e);
					if(strpos($exceptionFullClassName, "\\") !== false)
						$exceptionClassName = trim(strrchr($exceptionFullClassName, "\\"), "\\");
					else 
						$exceptionClassName = $exceptionFullClassName;
					$message = $e->getMessage();
					
					switch($exceptionClassName){
						case 'InvalidJsonPointerException':
							$keyPresentInValuesButMissingInFields = trim(strrchr($message, '/'), '/');
							$message = "This key is present in 'values' but is missing in 'fields': $keyPresentInValuesButMissingInFields";
						break;
						case 'JsonValidationException':
							$errorData = $e->getValidationErrorData();
							if ($errorData !== null)
								$message = json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
								. ' in: '
								. implode("/", $errorData['pathToTheDataThatCausedTheError']);
						break;
						default:
						break;
					}

					$validationResults[$schemaFileName][$fileName] = $message;
				}
			}
		}

		$this->_validationResults = $validationResults;
	}

	private function _validateSingleIndex($indexFileName, $schemaFileName, $filters = false) {
		$data = file_get_contents(__DIR__."/$indexFileName");
		$data = json_decode($data);

		$schema = Schema::fromJsonString(file_get_contents(__DIR__."/$schemaFileName"));

		$validator = new Validator(null,  new \Opis\JsonSchema\Loaders\File('childSchema:', ['.']));
		if ($filters !== false)
			$validator->setFilters($filters);

		$result = $validator->schemaValidation($data, $schema);

		if ($result->isValid())
		  return ["isValid" => true];
		else {
			$error = $result->getFirstError();
			return [
				"isValid" => false,
				"errorData" => [
					"errorMessage" => $error->keyword(), 
					"dataThatCausedTheError" => $error->data(),
					"pathToTheDataThatCausedTheError" => $error->dataPointer()
				]
			];
		}
	}
}

$jsonValidator = new JsonValidator();
$jsonValidator->init();
$jsonValidator->validate();
print_r($jsonValidator->getValidationResults());
exit;
