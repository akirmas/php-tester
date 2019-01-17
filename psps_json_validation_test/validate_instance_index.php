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
		echo '<pre>';
		echo "<h6>$fileName</h6>";
		var_dump($currentResult);
		echo '</pre>';

	}
} catch(Exception $e){
	$message = $e->getMessage();
	$keyPresentInValuesButMissingInFields = trim(strrchr($message, '/'), '/');
	echo "This key is present in 'values' but is missing in 'fields': " . $keyPresentInValuesButMissingInFields;
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