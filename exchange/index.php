<?php

require_once(__DIR__.'/../utils.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$post = file_get_contents('php://input');
$method = sizeof($_GET) !== 0
  ? 'GET' : (
  strlen($post) !==  0
  ? 'POST' 
  : ''
);

if ($method === '') exit('unknown method');

$input = $method === 'GET' ? $_GET : json_decode($post, true);

$dir = mkdir2(__DIR__, '..', '..', 'exchange-content');
$procDir = mkdir2($dir, $input['account']);
$nodeDir = mkdir2($procDir, $input['id']);
if (inFolder($dir, $nodeDir) === false) exit;

$nodePath = "$nodeDir/index.json";

switch ($method) {
  case 'GET':
    echo file_get_contents($nodePath);
    break;
  case 'POST':
    $content = json_encode($input, JSON_UNESCAPED_SLASHES);
    echo file_put_contents($nodePath, $content);
    file_put_contents($nodeDir.'/'.tmstmp().'.json', $content);
    break;
  default:
    exit('not implemented http method');
}
exit;
