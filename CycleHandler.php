<?php
declare(strict_types=1);

abstract class CycleHandler {
  static function onRequestRaw(object $env, object $input) : object {
    return new \stdClass;
  }
  static function onRequestFormed(object $env, object $request) : object {
    return new \stdClass;
  }
  static function onResponseRaw(object $env, object $response, object $request) : object {
    return new \stdClass;
  }
  static function onResponseFormed(object $env, object $output, object $input) : object {
    return new \stdClass;
  }
}