<?php
declare(strict_types=1);

abstract class CycleHandler {
  static function onRequestRaw(object $input) : object {
    return new \stdClass;
  }
  static function onRequestFormed(object $request) : object {
    return new \stdClass;
  }
  static function onResponseRaw(object $response, object $request) : object {
    return new \stdClass;
  }
  static function onResponseFormed(object $output, object $input) : object {
    return new \stdClass;
  }
}