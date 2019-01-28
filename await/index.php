<?php
set_time_limit(3000);

require_once(__DIR__.'/../utils.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$request = $_REQUEST;
$eventName = $request['event'];

$eventsFolder = mkdir2(__DIR__, "..", "..", "events");
$eventFolder = mkdir2($eventsFolder, $eventName);

// check is it injection to path for data grab
if (!inFolder($eventsFolder, $eventFolder)) exit;

$eventFile = __DIR__."/../events/$eventName/index.json";
if (!file_exists($eventFile)) {
  $event = new SyncEvent($eventName);
  $event->wait();
}

echo file_get_contents($eventFile);
