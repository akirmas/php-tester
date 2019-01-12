<?php
$request = $_REQUEST;
$eventName = $request['event'];

$eventsDir = __DIR__."../collector/collected";
$eventFile = "$eventsDir/$eventName/index.json";
if (!file_exists($eventFile)) {
  $event = new SyncEvent($eventName);
  $event->wait();
}
echo file_get_contents($eventFile);
