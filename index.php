<?php
ini_set('error_reporting', 0);

require_once(__DIR__.'/Tranzila/index.php');
require_once(__DIR__.'/Isracard/index.php');
require_once(__DIR__.'/collector/Collector.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$request = sizeof($_REQUEST) !== 0 ? $_REQUEST
: json_decode(file_get_contents('php://input'), true);

if (!array_key_exists('env', $request))
  exitBadData('No env', 1);

$envPath = __DIR__.'/envs/'.$request['env'].'.json';
if (!file_exists($envPath))
  exitBadData('No env config', 2);

$env = json_decode(file_get_contents($envPath), true);
forEach($env as $step) {
  $pspName = $step['psp'];
  switch ($pspName) {
    case 'Tranzila':
      $psp = new Tranzila;
      break;
    case 'Isracard':
      $psp = new Isracard;
      break;
    default:
      exitNotImplemented($pspName, 3);
  }

  $method = $step['method'];
  $env = $step['env'];
  $contact = new Contact($request);
  $creditCard = new CreditCard($request);
  $transaction = new Transaction($request);
  $deal = new Deal($request);
  $result;
  switch($method) {
    case 'iframe':
      $result = $psp->iframe($env, $transaction, $deal, '', '', $contact);
      break;
    case 'iframeContinued':
      header("HTTP/1.0 206 Partial Content", TRUE, 206);
      $collector = new Collector("tr_$transaction->id");
      $result = $psp->iframe($env, $transaction, $deal, '', $collector->callbackUrl, $contact);
      echo json_encode(array_merge(
        !$result['success'] ? [] : array('iframe' => $result['iframe']),
        array(
          'callback' => $collector->callbackUrl,
          'result' => $result
        )
      ));
      $result = $collector->wait();
      echo ',';    
      break;
    case 'instant':
      $transaction->verify = 1;
      $result = $tr->instant($env, $transaction, $dealNIS, $contact, $creditCard);
      if (!$result['success']) {
        echo $result['message'];
        exit();
      }
      $transaction->verify = 0;
      $result = $tr->instant($env, $transaction, $dealNIS, $contact, $creditCard);
      break;
    default:
      exitNotImplemented("$pspName->$method", 4);
  }    
}
echo json_encode(array('result' => $result));

function exitBadData($message = '', $id = -1) {
  $errorFamily = "Not Acceptable";
  header("HTTP/1.0 406 $errorFamily", TRUE, 406);
  exit(json_encode(array(
    'error' => $id,
    'message' => $message
  )));
}

function exitNotImplemented($message = '', $id = -2) {
  $errorFamily = "Not Implemented";
  header("HTTP/1.0 501 $errorFamily", TRUE, 501);
  exit(json_encode(array(
    'error' => $id,
    'message' => $message
  )));
}