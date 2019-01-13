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
  $request = array_merge($_REQUEST, ['ip' => !array_key_exists('REMOTE_ADDR', $_SERVER) ? '' : $_SERVER['REMOTE_ADDR']]);

  $instance = !array_key_exists('instance', $request) ? 'unknown' : $request['instance'];
  $instanceDir = mkdir2(__DIR__, '..' ,'collected', $instance);

  $eventId;
  switch($instance) {
    case 'Netpay':
      $eventId = $request['reference'];
      break;
    case 'Isracard': 
      $eventId = $request['payme_sale_id'];
      break;
    default: $eventId = 'unknown-'.date('Ymd-His');
  }
  $eventDir = mkdir2($instanceDir, $eventId);

  file_put_contents("$eventDir/index.json", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  file_put_contents("$eventDir/".rand().".json", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  $event = new SyncEvent("$instance/$eventId");
  $event->fire();
}

function mkdir2(...$folders) {
  $dir = join(DIRECTORY_SEPARATOR, $folders);
  if (!file_exists($dir)) mkdir($dir, 0777, true);
  return $dir;
}
