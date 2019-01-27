<?php
require_once('validation_classes.php');

$jsonValidator = new JsonValidator();
$jsonValidator->init();
$jsonValidator->validate();
print_r($jsonValidator->getValidationResults());
exit;
