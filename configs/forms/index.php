<?php
$json = __DIR__.'/'.$_GET['json'].'.json';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
echo file_get_contents($json);
