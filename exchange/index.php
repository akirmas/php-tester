<?php

require_once(__DIR__.'/../utils.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$post = file_get_contents('php://input');
$method = sizeof($_GET) !== 0
  ? 'GET' : (
  strlen($post) !==  0
  ? 'POST' 
  : ''
);

if ($method === '') exit('unknown method');

$input === 'GET' ? $_GET : json_decode($post, true);

$dir = mkdir2(__DIR__, 'content');
$procDir = mkdir2($dir, $input['process']);
$nodePath = "$procDir/$id.json";
if (inFolder($dir, $nodePath) === false) exit;

echo $method === 'GET' ? file_get_contents($nodePath) : file_put_contents($nodePath);
