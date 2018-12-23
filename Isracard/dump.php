<?php 

$creds = json_decode(file_get_contents(__DIR__.'/credentials/test.json'), true);
$method = 'generate-sale';

$contact = array(
  'first_name' =>	"First",
  'last_name' => "Last",
  'phone' => "972972972972",
  'email' => "andrii@gobe-mark.com"/*,
  'social_id' => 1111*/
);
$deal = array(
  'product_name' => 'product_name',
  'sale_price' => '100',
  'currency' => 'USD',
  'transaction_id' => 'f1111',

  'sale_callback_url' => 'https://payment.gobemark.info/php/temp/collector.php?s=b&',
  //'sale_return_url' => '',

  /*'sale_email' => 'andrii@gobe-mark.com',
  'sale_mobile' => '12312312',
  'sale_name' => 'sale',*/
);

$data = json_encode(array_merge(
  $creds['query'],
  $deal
));
echo "$data\n";
$ch = curl_init($creds['gateway'] . $method);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(   
  'Content-Type: application/json',
  'Content-Length: ' . strlen($data)                                                              
));
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$responseStr = curl_exec($ch);
try {
  $response = json_decode($responseStr, true);
  
  if (!array_key_exists('status_code', $response))
    throw new Exception("No status code: $responseStr", -1);
  $statusCode = $response['status_code'];
  if ($statusCode != 0) 
    throw new Exception($responseStr, $statusCode);

  $result = array(
    'url' => $response['sale_url'] . '?' . http_build_query($contact)
  );
}
catch (Exception $e) {
  $result = array(
    'error' => 1,
    'message' => $responseStr
  );
}
print_r($result);
