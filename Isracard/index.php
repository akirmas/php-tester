<?php 
declare(strict_types=1);
/**
 * https://www1.isracard-global.com/system/documentation#/introduction/api-capabilities-and-structure
 */
require_once(__DIR__.'/../PSP/Contact.php');
require_once(__DIR__.'/../PSP/Deal.php');
require_once(__DIR__.'/../PSP/Transaction.php');

require_once(__DIR__.'/../PSP/PSP.php');

require_once(__DIR__.'/../PSP/assoc_functions.php');

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
    $deal->amount *= 100;

    $query = array_merge(
      self::querify(
        [$transaction, $deal],
        $env
      ),
      empty($nextUrl) ? [] : array('sale_return_url' => $nextUrl),
      empty($callbackUrl) ? [] : array('sale_callback_url' => $callbackUrl)
    );
    $data  = json_encode($query);
    $url = "$env->gateway$method";
    $ch = curl_init($url);
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data)                                                              
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    $response = json_decode($response, true);

    return array_merge(
      array(
        'success' => !$response['status_code'],
        'response' => \assoc\mapKeyValuesVV(
          $response,
          $env->fields,
          $env->values,
          true,
          true
        )
      ),
      !array_key_exists('sale_url', $response)
      ? []
      : array('iframe' =>
        $response['sale_url']
        . (
          empty($contact)
          ? ''
          : (
            '?' . http_build_query(
              self::querify([$contact], (object) array(
                'fields' => $env->fields,
                'defaults' => [], 'overrides' => [], 'values' => []
              ))
            )
          )
        )
      )
    );
  }
}
