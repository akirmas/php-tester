<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once(__DIR__.'/Tranzila/index.php');
require_once(__DIR__.'/Isracard/index.php');
require_once(__DIR__.'/collector/Collector.php');

$request = sizeof($_REQUEST) !== 0 ? $_REQUEST
: json_decode(file_get_contents('php://input'), true);

if (!array_key_exists('env', $request)) {
  echo 'No env';
  exit(1);
}

$envPath = __DIR__.'/envs/'.$request['env'].'.json';
if (!file_exists($envPath)) {
  echo 'No env config';
  exit(2);
}

$env = json_decode(file_get_contents($envPath), "true");
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
      echo "Not implemented: '$pspName'";
      exit(3);
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
      $result = $psp->iframe($env, $transaction, $deal, '', $collector->callbackUrl, $contact);
      break;
    case 'iframeContinued':
      $collector = new Collector("tr_$_$transaction->id");
      $result = $psp->iframe($env, $transaction, $deal, '', $collector->callbackUrl, $contact);
      echo json_encode(array('iframe' => $result['iframe']));
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
      echo "Not implemented: '$pspName->$method'";
      exit(4);
  }    
}
echo json_encode(array('result' => $result));
