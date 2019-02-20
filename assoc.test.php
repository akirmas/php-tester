<?php
require_once(__DIR__."/assoc.php");

$json
/*= <<<JSON
[
  [
    {"x": {"engine" : {"method" : "GET", "gateway" : "https://"}}},
    {"x": {"engine" : {"gateway" : "", "x": "a"}}}
  ],
  {"x": {"engine" : {"method" : "GET", "gateway" : "", "x": "a"}}} 
]
JSON;*/
='[{"y":0, "x":{"a":1,"b":{"c":1,"d":0}}}, [["y", 0],["x","a",1], ["x","b","c",1], ["x","b","d",0]]]';

$test = json_decode($json, true);
$result
//= (array) \assoc\merge(...$test[0]);
= (array) \assoc\assoc2table($test[0]);
$output = $result == $test[1];
//print_r($output);
echo "$output";
