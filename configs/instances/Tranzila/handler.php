<?php
require_once(__DIR__.'/../../../CycleHandler.php');

class Tranzila extends CycleHandler {
  static function onResponseRaw(object $env, object $response, object $request): object{
    $responseCode = !property_exists($response, 'Response')
    ? 0
    : $response->Response;
    return (object) [
      'ResponseClone' => $responseCode,
      'ResponseClone2' => $responseCode
    ];
  }
}

