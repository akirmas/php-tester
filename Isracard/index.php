<?php 
require_once(__DIR__.'/../PSP/Contact.php');
require_once(__DIR__.'/../PSP/Deal.php');

/**
 * https://www1.isracard-global.com/system/documentation#/introduction/api-capabilities-and-structure
 */
 
class Isracard {
  private $creds;
  private $fields;
  function __construct(string $acc) {
    $this->creds = json_decode(file_get_contents("credentials/$acc.json"), true);
    $fieldsJson = __DIR__.'/fields.json';
    $this->fields = !file_exists($fieldsJson) ? []
      : json_decode(file_get_contents($fieldsJson), true);
  }

  function payUrl(Deal $deal, $callBackUrl, Contact $contact = null) {
    $method = 'generate-sale';
    $query = $this->creds['query'];
    forEach ($deal->toAssoc() as $k => $v) {
      $query[
        !array_key_exists($k, $this->fields['deal']) ? $k 
        : $this->fields['deal'][$k]
      ] = $v;
    }
    $query['sale_callback_url'] = $callBackUrl;
    $data  = json_encode($query);

    $ch = curl_init($this->creds['gateway'] . $method);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data)                                                              
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $response = curl_exec($ch);
    $response = json_decode($response, true);
    $result = $response['sale_url']
      . (empty($contact) ? ''
        : '?' . http_build_query($contact->toAssoc())
      );
    return $result;
    /*echo $result;
    flush() ;
    ob_flush();*/
  }
}
