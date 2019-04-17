<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');
require_once(__DIR__.'/../functions.php');

class Redsys extends CycleHandler {
  static function onResponseRaw($env, $response, $request) {
    return [
      'signature' => redsysSignature($response),
      'data' => base64_encode(json_encode($response))
    ];
  }
}