<?php
ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once(__DIR__.'/Tranzila/index.php');
require_once(__DIR__.'/Isracard/index.php');
require_once(__DIR__.'/collector/Collector.php');

$request = sizeof($_REQUEST) !== 0 ? $_REQUEST
: json_decode(file_get_contents('php://input'), true);

$request = array(
  'email' => 'x@gbm',
  //'cc_number' => '4319462718879596',
  'cc_number' => '12312312',
  'cc_expire' => '0820',
  'cc_cvv' => '662',
  'amount' => '149',
  'currency' => 'USD',
  'description' => 'Marketscap.+Single+Page+App.+Payment.+149+pounds.+Nov+2018+DF0F31AA2988C0ABD3CC34E249477438',
  'verify' => 1,
  'full_name' => 'Gbm Test',
  'id' => date('Ymd_His'),
  'account' => 'x'
);

$tr = new Tranzila;
$ic = new Isracard;
$contact = new Contact($request);
$creditCard = new CreditCard($request);
$transaction = new Transaction($request);
$dealUSD = new Deal($request);
$dealNIS = new Deal($request);
$dealNIS->currency = 'NIS';
$dealUSD->currency = 'USD';

$collector = new Collector("tric$transaction->id");
/*print_r([
  $_SERVER['HTTP_HOST'], $_SERVER['SERVER_NAME'],
  "---TR---",
  $tr->instant('test', $transaction, $dealNIS, $contact, $creditCard),
  "---IC---",
  $ic->iframe('test', $transaction, $dealNIS, '', '', $contact),
  "---IC---",
  $ic->iframe('test', $transaction, $dealUSD, '', '', $contact)
]);*/
$result = $ic->iframe('test', $transaction, $dealUSD, $collector->callbackUrl, $collector->callbackUrl, $contact);
if ($result['success']) {
  echo json_encode(array('cbUrl' => $collector->callbackUrl, 'iframe' => $result['iframe']));
  $content = $collector->wait();
  echo ',';
  echo json_encode(array('result' => $content));
  exit;
}


