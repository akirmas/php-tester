<?php
require_once(__DIR__.'/../../utils.php');

$json = !array_key_exists('json', $_GET)
  // Trick to cause error - 'out of folder' in this case
  ? '..'
  : $_GET['json'].'.json';
fileDelivery(__DIR__, $json, 'application/json');
exit;
