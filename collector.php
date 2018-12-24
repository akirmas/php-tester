<?php
$fireId = $_REQUEST['fireid'];
$log = fopen("./responses/$fireId.json", 'w');
fwrite($log, json_encode($_REQUEST, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
fclose($log);
$nodeJS = new SyncEvent($fireId);
$nodeJS->fire();
