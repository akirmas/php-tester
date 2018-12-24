<?php
ini_set("implicit_flush", 1);
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('max_execution_time', 0);
while (@ob_end_flush());
ob_implicit_flush(true);
require_once('index.php');

$psp = new Isracard('test');

$deal = new Deal($_REQUEST);
$contact = new Contact($_REQUEST);

$dealId = 'IC' . time();
$deal->id = $dealId;

$url = $psp->payUrl(
  $deal,
  "https://payment.gobemark.info/php/psps/collector.php?fireid=$dealId&",
  $contact
);
header('HTTP/1.1 206 Partial Content');
echo json_encode(array('url' => $url));
flush();
ob_flush();
$nodeJS = new SyncEvent($dealId);
$nodeJS->wait();
echo ','.file_get_contents("../responses/$dealId.json");
