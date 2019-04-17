<?php
require_once(__DIR__.'/../utils/assoc.php');

function redsysSignature($key, $data, $baseRoot = 8, $cryptLength = 8) {
  $orderId = \assoc\getValue($data, 'DS_MERCHANT_ORDER', urldecode(\assoc\getValue($data, 'Ds_Order')));
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