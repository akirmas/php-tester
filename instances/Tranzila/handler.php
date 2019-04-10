<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');

class Tranzila extends CycleHandler {
  static function onResponseRaw($env, $response, $request) {
    $responseCode = !\assoc\keyExists($response, 'Response')
    ? 0
    : $response['Response'];
    return [
      'ResponseClone' => $responseCode,
      'ResponseClone2' => $responseCode
    ];
  }
}

