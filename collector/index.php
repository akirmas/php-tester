<?php
require_once(__DIR__.'/Collector.php');

if (array_key_exists(Collector::fireField, $_REQUEST)) {
  $fireId = $_REQUEST[Collector::fireField];
  $eventContent = fopen(Collector::contentPath($fireId), 'w');
  fwrite($eventContent, json_encode($_REQUEST));
  fclose($eventContent);
  $nodeJS = new SyncEvent($fireId);
  $nodeJS->fire();
}
