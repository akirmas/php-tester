<?php
declare(strict_types=1);
/**
 * http://doctr.interspace.net/?type=1
 */
require_once(__DIR__.'/../PSP/Assoc.php');

require_once(__DIR__.'/../PSP/Contact.php');
require_once(__DIR__.'/../PSP/CreditCard.php');
require_once(__DIR__.'/../PSP/Deal.php');
require_once(__DIR__.'/../PSP/Transaction.php');

define('DEFAULT_SCHEME', json_decode(file_get_contents(__DIR__."/../.json"), true));
define('TRANZILA_ENV', json_decode(file_get_contents(__DIR__.'/index.json'), true));
define('TRANZILA_RESPONSES', json_decode(file_get_contents(__DIR__.'/responses.json'), true));
class Tranzila {
  private const default_scheme = DEFAULT_SCHEME;
  private const gateway = TRANZILA_ENV['gateway'];
  private const fields = TRANZILA_ENV['fields'];
  private const values = TRANZILA_ENV['values'];
  private const defaults = TRANZILA_ENV['defaults'];
  private const responses = TRANZILA_RESPONSES;

  //function __construct() {}
  
  private function querify($args, array $fields = [], array $values = []) :array {
    return array_merge(
      ...array_map(
        function($el) use ($fields, $values) {
          return !in_array(gettype($el), ['array', 'object'])
            ? []
            : Assoc::mapKeyValues(
              Assoc::toAssoc($el),
              Assoc::filterEmpty($fields),
              Assoc::filterEmpty($values),
              false,
              true
            );
        },
        $args
      )
    );
  }

  public function instant(
    string $envName,
    Transaction $transaction,
    Deal $deal,
    Contact $contact,
    CreditCard $creditCard
  ) :array {
    $env = (object) array_merge(
      array(
        'defaults' => [],
        'overrides' => [],
        'values' => [],
        'fields' => []
      ),
      TRANZILA_ENV,
      json_decode(file_get_contents(__DIR__."/envs/$envName.json"), true)
    );
    $query = $this->querify(
      [
        $env->defaults,
        $transaction, $deal, $contact, $creditCard,
        $env->overrides
      ],
      $env->fields, $env->values
    );
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
        'request' => $request,
        'success' => $code === '000',
        'response' => $response
      ),
      !array_key_exists($code, self::responses)
      ? []
      : array('message' => self::responses[$code])
    );
  }
}

