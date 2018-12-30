<?php
require_once(__DIR__.'/Tranzila/index.php');

$request = sizeof($_REQUEST) !== 0 ? $_REQUEST
: json_decode(file_get_contents('php://input'), true);

$request = array(
  'email' => 'x@gbm',
  //'cc_number' => '4319462718879596',
  'cc_number' => '12312312',
  'cc_expire' => '0820',
  'cc_cvv' => '662',
  'amount' => '149',
  'currency' => 'NIS',
  'description' => 'Marketscap.+Single+Page+App.+Payment.+149+pounds.+Nov+2018+DF0F31AA2988C0ABD3CC34E249477438',
  'verify' => 0,
  'full_name' => 'Gbm Test',
  'id' => '100500',
  'account' => 'x'
);

$tr = new Tranzila;
$contact = new Contact($request);
$creditCard = new CreditCard($request);
$deal = new Deal($request);
$transaction = new Transaction($request);

echo "\n---\n";
print_r($tr->instant('test', $transaction, $deal, $contact, $creditCard));
