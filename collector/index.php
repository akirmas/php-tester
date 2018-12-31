<?php
require_once(__DIR__.'/Collector.php');

if (array_key_exists(Collector::fireField, $_REQUEST)) {
  $fireId = $_REQUEST[Collector::fireField];
  $eventContent = fopen(Collector::contentPath($fireId), 'w');
  fwrite($eventContent, json_encode($_REQUEST, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
  fclose($eventContent);
  $nodeJS = new SyncEvent($fireId);
  $nodeJS->fire();
}
