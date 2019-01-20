<?php
require_once(__DIR__.'/../../../CycleHandler.php');

class Tranzila extends CycleHandler {
  static function onResponseRaw(object $env, object $response, object $request): object{
    return (object) array('ResponseClone' => $response->Response);
  }
}

