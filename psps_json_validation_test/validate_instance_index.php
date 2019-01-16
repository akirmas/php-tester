<?php
require '../vendor/autoload.php';

use Opis\JsonSchema\{
    Validator, ValidationResult, ValidationError, Schema
};

$schemaFileName = "instance_index_validation_schema.json";
$indexFilesCollection = [
	"./instances_index_files/netpay_index.json",
	"./instances_index_files/tranzilla_index.json",
	"./instances_index_files/isracard_index.json" 
	];

foreach ($indexFilesCollection as $fileName) {
	
	$currentResult = validateSingleIndex($fileName, $schemaFileName);
	echo '<pre>';
	echo "<h6>$fileName</h6>";
	var_dump($currentResult);
	echo '</pre>';

}

function validateSingleIndex($indexFileName, $schemaFileName)
{
	$data = file_get_contents($indexFileName);
	$data = json_decode($data);
	
	$schema = Schema::fromJsonString(file_get_contents($schemaFileName));

	$validator = new Validator();

	/** @var ValidationResult $result */
	$result = $validator->schemaValidation($data, $schema);

	if ($result->isValid()) {
	    return ["isValid" => true];
	} else {
	    /** @var ValidationError $error */
	    $error = $result->getFirstError();
	    return [ "isValid" => false, "errorData" => ["errorMessage" => $error->keyword(), "errorObject" => $error] ];
	    //echo json_encode($error->keywordArgs(), JSON_PRETTY_PRINT), PHP_EOL;
	}
}


?>