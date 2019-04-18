<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');
require_once(__DIR__.'/../functions.php');
require_once(__DIR__.'/../../utils/stringmath.php');

class Redsys extends CycleHandler {
  static function onRequestFilled($env, $request) {
    $orderId = baseChangeString(
      //TODO: pick from env
      '()*+,./-0123456789:;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz{|}~',
      '0123456789',
      \assoc\getValue($request, 'autoincrement')
    );

    return [
      'orderId' => baseChangeString(
        //TODO: pick from env
        '()*+,./0123456789:;?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_abcdefghijklmnopqrstuvwxyz{|}~',
        '0123456789',
        \assoc\getValue($request, 'autoincrement')
      )
    ];
  }
  static function onResponseRaw($env, $response, $request) {
    $key = $response['account:key'];
    //TODO: pick from env
    foreach(['account:key', 'version', '$output', 'gateway'] as $redudantKey)
      unset($response[$redudantKey]);
    return [
      'signature' => redsysSignature($key, $response),
      'data' => base64_encode(json_encode($response))
    ];
  }
}