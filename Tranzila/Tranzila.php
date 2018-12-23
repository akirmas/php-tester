<?php
/**
 * http://doctr.interspace.net/?type=1
 */
declare(strict_types=1);
require_once('./PSP.php');

define('tranz_currencies', json_decode(file_get_contents('./currencies.json'), true));
define('tranz_responses', json_decode(file_get_contents('./responses.json'), true));

class Tranzila implements PSP {
  private static $gateway = 'https://secure5.tranzila.com/cgi-bin/tranzila71u.cgi';
  private static $currencies = tranz_currencies;
  private static $responses = tranz_responses;
  private $supplier;
  public $log;

  function __construct(string $supplier = '') {
    $this->supplier = empty($supplier) ? '' : $supplier;
    $this->log = function() {};
  }

  public function execute(Transaction $transaction, callable $onAction) :array {
    $creditCard = $transaction->creditCard;
    $transaction = array(
      'supplier' => empty($transaction->supplier) ? $this->supplier : $transaction->supplier,
      'tranmode' => 1,

      'ccno' => $creditCard->number,
      'expdate' => substr("00" . (string)$creditCard->month, -2)
        . (string) ($creditCard->year % 100),
      //  This field is to be sent only if you are required to do so by your credit card company
      'mycvv' => $creditCard->cvv,

      'sum' => $transaction->amount,
      'currency' => tranz_currencies[$transaction->currency],

      'pdesc' => $transaction->product,
      'email' => $transaction->contact,

      'tranmode' => $transaction->check ? 'V' : 'A'
    );
    $request = http_build_query($transaction);

    $reqUrl = self::$gateway."?$request";
    $resStr = file_get_contents($reqUrl);
    try {
      call_user_func($this->log, $reqUrl, $resStr);
    } catch (Exception $e) {}

    try {
      call_user_func($onAction,  $reqUrl, $resStr);
    } catch (Exception $e) {}

    parse_str($resStr, $response);
    $code = $response['Response'];
    if ($code === '000')
      return array(
          'index' => $response['index'],
          'confirmationCode' => $response['ConfirmationCode'],
      );
    else
      throw new Exception((string) tranz_responses[$code], (int) $code);
  }
}

