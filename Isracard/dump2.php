<?php
require_once('index.php');

$psp = new Isracard('test');

$deal = new Deal($_REQUEST);
$contact = new Contact($_REQUEST);

$dealId = time();
$deal['id'] = $dealId;

$url = $psp->payUrl(
  $deal,
  "http://payment.gobemark.info/php/psps/collector.php",
  $contact
);

$nodeJS = new SyncEvent("Response_Deal$dealId");
echo $url;
flush() ;
ob_flush();
$nodeJS->wait();
echo "Response+";