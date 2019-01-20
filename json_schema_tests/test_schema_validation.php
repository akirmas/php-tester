<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Opis\JsonSchema\{
    Validator, ValidationResult, ValidationError, Schema
};

$data = file_get_contents(__DIR__.'/json_data_to_validate.json');

$data = json_decode($data);

$schema = Schema::fromJsonString(file_get_contents(__DIR__.'/test_schema.json'));

$validator = new Validator();

/** @var ValidationResult $result */
$result = $validator->schemaValidation($data, $schema);

if ($result->isValid()) {
    echo '$data is valid', PHP_EOL;
} else {
    /** @var ValidationError $error */
    $error = $result->getFirstError();
    echo '$data is invalid', PHP_EOL;
    echo "Error: ", $error->keyword(), PHP_EOL;
    echo json_encode($error->keywordArgs(), JSON_PRETTY_PRINT), PHP_EOL;
}
