<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');
require_once(__DIR__.'/../functions.php');
require_once(__DIR__.'/../../utils/stringmath.php');

class Redsys extends CycleHandler {
  static function onRequestFilled($env, $request) {
    $orderId = \assoc\getValue($request, 'orderId',
      baseChangeString(
        \assoc\getValue($env, ['engine', 'alphabet', 'output']),
        \assoc\getValue($env, ['engine', 'alphabet', 'input']),
        \assoc\getValue($request, 'autoincrement')
      )
    );

    return [
      'orderId' => $orderId
    ];
  }
  static function onResponseRaw($env, $response, $request) {
    //TODO: pick from env
    foreach(['$output', 'iframe:HTML'] as $redudantKey)
      unset($response[$redudantKey]);
    return [
      'signature' => redsysSignature(\assoc\getValue($env, ['engine', 'account:key']), $response),
      'data' => base64_encode(json_encode($response))
    ];
  }
}