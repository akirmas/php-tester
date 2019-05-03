<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils0/assoc.php');
require_once(__DIR__.'/../functions.php');
require_once(__DIR__.'/../../utils0/stringmath.php');

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
    $redundantKeys = ['$output', 'form:action', 'form:method', 'form:target', "form:inputs", 'success', 'success:ing'];
    $response = array_filter(
      $response,
      function ($key) use ($redundantKeys) {
        return !in_array($key, $redundantKeys);
      },
        ARRAY_FILTER_USE_KEY
    );

    return [
      'signature' => redsysSignature(\assoc\getValue($env, ['engine', 'account:key']), $response),
      'data' => base64_encode(json_encode($response))
    ];
  }
}