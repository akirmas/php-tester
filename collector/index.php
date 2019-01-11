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
  $unsortedDir = __DIR__.'/unsorted';
  if (!file_exists($unsortedDir)) mkdir($unsortedDir);

  file_put_contents(
    "$unsortedDir/".date('YmdHis').'.json',
    json_encode([
      'from' => [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'name' => !array_key_exists('REMOTE_HOST', $_SERVER) ? null : $_SERVER['REMOTE_HOST'],
        'referer' => !array_key_exists('HTTP_REFERER', $_SERVER) ? null : $_SERVER['HTTP_REFERER'] 
      ],
      'data' => (sizeof($_REQUEST) !== 0)
        ? $_REQUEST
        : (array_key_exists('argv', $_SERVER)
        ? $_SERVER['argv']
        : file_get_contents('php://input'))
    ],
    JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
  );
}
