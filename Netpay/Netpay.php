<?php
/**
 * https://devcenter.netpay-intl.com/website/SilentPost_Cc.aspx
 */
$account = "test";
$credentials = json_decode(file_get_contents("credentials/$account.json"), true);
$query = array_merge(
  $credentials['query'],
  array(
    'TransType' => 0, // check, charge
    'Amount' => 1,
    'Currency' => 0,
    'CardNum' => '91000000',
    'ExpMonth' => '01',
    'ExpYear' => '2020',
    'CVV2' => '123',
    'PhoneNumber' => '123123123',
    'Email' => 'andrii@gobe-mark.com',
    'Member' => 'Andy Test',
    'BillingAddress1' => 'Street Perharps',
    'BillingCity' => 'Some City',
    'BillingZipCode' => '134464',
    "BillingCountry" => "IL"
  )
);

// base64_encode(hash("sha256", "1234567ABCDEFGHIJ", true));
$sign = base64_encode(hash("sha256",
  join("", [
    $query['CompanyNum'],
    $query['TransType'],
    $query['TypeCredit'],
    $query['Amount'],
    $query['Currency'],
    $query['CardNum'],
    $credentials['salt'],
  ]),
  true
));

$query['Signature'] = $sign;

$response = file_get_contents(
  $credentials['gateway']
  .'?'
  .http_build_query($query)
);
$response = explode('&', $response);
$response = array_map(
  function($kv) {
    $kv = explode('=', $kv);
    return array(
      array_shift($kv) => join('=', $kv)
    );
  },
  $response
);
$response = array_reduce($response,
  function($acc, $kv) {
    $values = array_values($kv);
    return (sizeof($values) == 0 or empty($values[0]))
    ? $acc
    : array_merge($acc, $kv);
  },
  array()
);

print_r($response);
