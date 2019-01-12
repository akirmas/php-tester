<?php
require_once(__DIR__.'/Collector.php');

if (array_key_exists(Collector::fireField, $_REQUEST)) {
  $fireId = $_REQUEST[Collector::fireField];
  $eventContent = fopen(Collector::contentPath($fireId), 'w');
  fwrite($eventContent, json_encode($_REQUEST));
  fclose($eventContent);
  $nodeJS = new SyncEvent($fireId);
  $nodeJS->fire();
} else {
  $request = array_merge($_REQUEST, ['ip' => $_SERVER['REMOTE_ADDR']]);

  $instance = !array_key_exists('instance', $request) ? 'unknown' : $request['instance'];
  $instanceDir = __DIR__."/collected/$instance";
  if (!file_exists($instanceDir)) mkdir($instanceDir);

  $eventId = $instance === 'Netpay' ? $request['reference'] : 'unknown-'.date('Ymd-His');
  $eventDir = "$instanceDir/$eventId";
  if (!file_exists($eventDir)) mkdir($eventDir); 

  file_put_contents("$eventDir/index.json", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  file_put_contents("$eventDir/".rand().".json", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $event = new SyncEvent("$instance/$eventId");
  $event->fire();
}
