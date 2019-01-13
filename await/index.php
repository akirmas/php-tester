<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$request = $_REQUEST;
$eventName = $request['event'];

$eventFile = __DIR__."../collected/$eventName/index.json";
if (!file_exists($eventFile)) {
  $event = new SyncEvent($eventName);
  $event->wait();
}
echo file_get_contents($eventFile);
