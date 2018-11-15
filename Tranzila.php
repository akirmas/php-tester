<?php
class Tranzila {
  private static $gateway = 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi';
  private static $supplier;
  public static $currencies = array('NIS' => 1, 'USD' => 2, 'EUR' => 978, 'GBP' => 826); 

  function __construct($supplier) {
    $this->supplier = $supplier;
  }

  public function execute($transaction) {
    $transaction['supplier'] = $this->supplier;
    $request = http_build_query($transaction);
    $response = file_get_contents("$this->gateway?$request");
    return $response;
  }
}

