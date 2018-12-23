<?php
$log = fopen('./responses/'.date('ymd-His-').rand().'.json', 'w');
fwrite($log, json_encode($_REQUEST, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
fclose($log);
echo 'firing';
$nodeJS = new SyncEvent("Response_Deal$dealId");
$nodeJS->fire();
echo 'fired';