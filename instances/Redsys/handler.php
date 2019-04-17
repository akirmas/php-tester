<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');
require_once(__DIR__.'/../functions.php');

class Redsys extends CycleHandler {
  static function onResponseRaw($env, $response, $request) {
    $key = $response['account:key'];
    foreach(['account:key', 'version', '$output', 'gateway'] as $redudantKey)
      unset($response[$redudantKey]);
    return [
      'signature' => redsysSignature($key, $response),
      'data' => base64_encode(json_encode($response))
    ];
  }
}