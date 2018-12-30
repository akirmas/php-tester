<?php 
declare(strict_types=1);
/**
 * https://www1.isracard-global.com/system/documentation#/introduction/api-capabilities-and-structure
 */
require_once(__DIR__.'/../PSP/Contact.php');
require_once(__DIR__.'/../PSP/Deal.php');
require_once(__DIR__.'/../PSP/Transaction.php');
define('ISRACARD_ENV', json_decode(file_get_contents(__DIR__.'/index.json'), true));

class Isracard extends PSP {
  private const env = ISRACARD_ENV;

/*  function __construct(string $acc) {
    $this->creds = json_decode(file_get_contents(__DIR__."credentials/$acc.json"), true);
  }*/

  function iframe(
    string $envName,
    Transaction $transaction,
    Deal $deal,
    string $nextUrl = '',
    string $callbackUrl = '',
    Contact $contact = null
  ) {
    $method = 'generate-sale';
    $env = (object) array_merge_recursive(
      self::env,
      json_decode(file_get_contents(__DIR__."/envs/$envName.json"), true)
    );
    $query = self::querify(
      [$transaction, $deal],
      $env
    );    
    array_merge(
      $query,
      empty($nextUrl) ? [] : array('sale_return_url' => $nextUrl),
      empty($callBackUrl) ? [] : array('sale_callback_url' => $callBackUrl)
    );
    print_r(array('query' => $query));
    $data  = json_encode($query);

    $ch = curl_init("$env->gateway$method");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data)                                                              
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    $response = json_decode($response, true);
    $env->defaults = [];
    $env->overrides = [];
    $result = $response['sale_url']
      . (
        empty($contact)
        ? ''
        : (
          '?' . http_build_query(
            self::querify([$contact], $env)
          )
        )
      );
    return $result;
  }
}
