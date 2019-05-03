<?php
require_once(__DIR__.'/../utils0/assoc.php');
use function \assoc\getValue;

function redsysSignature($key, $data, $baseRoot = 8, $cryptLength = 8) {
  $orderId = getValue($data, 'DS_MERCHANT_ORDER', urldecode(getValue($data, 'Ds_Order')));
  $len = strlen($orderId);
  $l = ceil($len / $baseRoot) * $baseRoot;
  return base64_encode(
    hash_hmac(
      'sha256',
      base64_encode(json_encode($data)),
      substr(
        openssl_encrypt(
          $orderId
          . str_repeat("\0", $l - $len),
          'des-ede3-cbc',
          base64_decode($key),
          OPENSSL_RAW_DATA,
          str_repeat("\0", $baseRoot)
        ),
        0,
        $cryptLength
      ),
      true
    )
  );
}

function autoincrement($key) {
  $incrementorDir = mkdir2(__DIR__, '..', '..', 'incrementors');
  $incrementorFile = "{$incrementorDir}/{$key}";
  mkdir2(...array_slice(
    explode('/', $incrementorFile),
    0,
    -1
  ));
  $value = !file_exists($incrementorFile) 
  ? 1
  : 1 + intval(file_get_contents($incrementorFile));
  file_put_contents($incrementorFile, $value, LOCK_EX);
  return $value;
}
