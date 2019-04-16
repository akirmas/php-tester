<?php
function redsysSignature($data) {
  $baseRoot = 8;

  $key = $data['key'];
  unset($data['key']);
  $orderId = $data['orderId'];
  unset($data['orderId']);
  
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
        $baseRoot
      ),
      true
    )
  );
}