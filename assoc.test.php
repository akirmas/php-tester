<?php
require_once(__DIR__."/assoc.php");

$json = <<<JSON
[
  {"x": {"engine" : {"method" : "GET", "gateway" : "https://"}}},
  {"x": {"engine" : {"gateway" : "", "x": "a"}}},
  {"x": {"engine" : {"method" : "GET", "gateway" : "", "x": "a"}}} 
]
JSON;
$test = json_decode($json, true);
$result = (array) \assoc\merge(0, 1, $test[0], $test[1]);
$output = $result == $test[2];
echo "$output";
//.json_encode($result, JSON_UNESCAPED_SLASHES)."\n".json_encode($test[2], JSON_UNESCAPED_SLASHES);

