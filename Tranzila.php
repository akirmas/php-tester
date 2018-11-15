<?php
require_once('./Transaction.php');

define('tranz_gateway',file_get_contents('./gateway.txt'));
define('tranz_curencies', json_decode(file_get_contents('./currencies.json', 1)));
define('tranz_responses', json_decode(file_get_contents('./responses.json', 1)));
define('tranz_transaction', json_decode(file_get_contents('./transaction.json', 1)));

class Tranzila {
  private $gateway = tranz_gateway;
  private $currencies = tranz_currencies;
  private $responses = tranz_responses;
  private $transaction = tranz_transaction;
  private $supplier;

  function __construct($supplier) {
    if (!empty($supplier))
      $this->supplier = $supplier;
  }

  public function execute(Transaction $transaction) {
    $transaction = array_merge(
      $this->transaction,
      array('supplier' => $this->supplier),
      array(
        'ccno' => $creditCard->number,
        'expdate' => $creditCard->month . substr($creditCard->year, -2),
        //  This field is to be sent only if you are required to do so by your credit card company
        'mycvv' => $creditCard->cvv
      )
    );
    $request = http_build_query($transaction);
    $response = file_get_contents("$this->gateway?$request");
    return $response;
  }
}

