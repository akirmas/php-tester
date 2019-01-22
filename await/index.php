<?php
set_time_limit(3000);

require_once(__DIR__.'/../utils.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

$request = $_REQUEST;
$eventName = $request['event'];

$eventsFolder = mkdir2(__DIR__, "..", "events");
$eventFolder = mkdir2($eventsFolder, $eventName);

// check is it injection to path for data grab
if (!inFolder($eventsFolder, $eventFolder)) exit;

$eventFile = __DIR__."/../events/$eventName/index.json";
if (!file_exists($eventFile)) {
  $event = new SyncEvent($eventName);
  $event->wait();
}
$eventContent = json_decode(file_get_contents($eventFile), true);
switch($eventContent['instance']) {
  case 'Isracard':
/*  "payme_status": "success" vs "error"
  "status_error_code": "0" vs $code
  "status_code": "0" vs "1"
  "payme_sale_status": "completed" vs "failed"
  "sale_status": "completed" vs "failed"
  "status_error_details": null vs message
  "notify_type":  "sale-complete"  vs "sale-failure"
*/
    $eventContent['success'] = (int) ($eventContent['payme_status'] === 'success');
    $eventContent['return:code'] = $eventContent['status_error_code'];
  break;
  default:
}
echo json_encode($eventContent);
