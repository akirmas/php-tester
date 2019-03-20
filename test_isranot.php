<?php
//"callbackUrl": "https://payment.gobemark.info/apis/master/?account=IsracardNotify&process=test&"
$gateway = 'http://localhost/psps/?account=IsracardNotify&process=local&';

$headers = [];

$options = (object) [
  'interface' => 'www',
  'method' => 'post',
  'contentType' => 'application/json',
  'date' => time(),
  'outHeaders' => true,
  'outCode' => true
];

$data = [
  "transaction_id" => "test/test1902171400",
  "payme_status" => "error", // success error
  "status_error_code" => "0", // 0 *
  "status_code" => "0", // 0 1
  "payme_sale_status" => "completed", // completed failed
  "sale_status" => "failed", // completed failed
  "id" => "20190213-154602_1152845431"
];

$curlopt = [
    CURLOPT_CUSTOMREQUEST => strtoupper($options->method),
    CURLOPT_HEADER => false,
    CURLOPT_HEADERFUNCTION => headerFunction($headers),
    CURLOPT_HTTPHEADER => [
      'Request-Date: '. gmdate('D, d M Y H:i:s T', (int) $options->date),
      "Content-Type: $options->contentType"
    ],
    CURLOPT_RETURNTRANSFER => true/*,
    CURLOPT_CAINFO => __DIR__.'/cacert.pem',
    CURLOPT_CAPATH => __DIR__.'/cacert.pem'*/
];

$ch = curl_init("$gateway");

switch($options->method) {
  case 'patch':
  case 'post':
    $curlopt[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_SLASHES);
    break;
  default:
}
curl_setopt_array($ch, $curlopt);

$response = curl_exec($ch);
if (!$options->outHeaders && !$options->outCode)
  echo $response;
else echo json_encode(
  (!$options->outHeaders ? [] : ['headers' => $headers])
  + (!$options->outCode ? [] : ['code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)])
  + ['response' => $response]
  + ($response !== false ? [] : ['error' => curl_error($ch)]),
  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);

curl_close($ch);

function headerFunction(&$headers) {
  return function($_, $header) use (&$headers) {
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2) // ignore invalid headers
      return $len;

    $name = strtolower(trim($header[0]));
    if (!array_key_exists($name, $headers))
      $headers[$name] = [trim($header[1])];
    else
      $headers[$name][] = trim($header[1]);

    return $len;
  };
}
