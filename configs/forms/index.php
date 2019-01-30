<?php
require_once(__DIR__.'/../../utils.php');

$json = !array_key_exists('json', $_GET)
  // Trick to cause error - 'out of folder' in this case
  ? '..'
  : $_GET['json'].'.json';

try {
  $content = readNestedFile(__DIR__, $json);
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Content-Type: application/json');
  echo $content;  
} catch (Exception $err) {
  http_response_code(404);
}

exit;
