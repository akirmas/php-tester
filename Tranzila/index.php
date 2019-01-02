<?php
declare(strict_types=1);
/**
 * http://doctr.interspace.net/?type=1
 */

require_once(__DIR__.'/../PSP/Contact.php');
require_once(__DIR__.'/../PSP/CreditCard.php');
require_once(__DIR__.'/../PSP/Deal.php');
require_once(__DIR__.'/../PSP/Transaction.php');

require_once(__DIR__.'/../PSP/PSP.php');

require_once(__DIR__.'/../PSP/assoc_functions.php');

define('TRANZILA_ENV', json_decode(file_get_contents(__DIR__.'/index.json'), true));
define('TRANZILA_RESPONSES', json_decode(file_get_contents(__DIR__.'/responses.json'), true));

class Tranzila extends PSP {
  private const gateway = TRANZILA_ENV['gateway'];
  private const env = TRANZILA_ENV;
  private const responses = TRANZILA_RESPONSES;

  public function instant(
    string $envName,
    Transaction $transaction,
    Deal $deal,
    Contact $contact,
    CreditCard $creditCard
  ) :array {
    $env = (object) array_merge_recursive(
      self::env,
      json_decode(file_get_contents(__DIR__."/envs/$envName.json"), true)
    );
    $query = self::querify(
      [$transaction, $deal, $contact, $creditCard],
      $env
    );
    $url = self::gateway;
    $data = http_build_query($query);
    $request = self::gateway.'?'.http_build_query($query);
    parse_str(
      file_get_contents($request),
      $response
    );

    $codeField = 'Response';
    $code = gettype($response) !== 'array'
    ? -1
    : (
      !array_key_exists($codeField, $response) 
      ? -2
      : $response[$codeField]
    );

    return array_merge(
      array(
        'query' => $query,
        'request' => "$url?$data",
        'success' => $code === '000',
        'response' => \assoc\mapKeyValuesVV(
          $response,
          $env['fields'],
          $env['values'],
          true,
          true
        )
      ),
      !array_key_exists($code, self::responses)
      ? []
      : array('message' => self::responses[$code])
    );
  }
}

