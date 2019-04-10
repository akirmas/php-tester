<?php
abstract class CycleHandler {
  static function onRequestFilled($env, $request) {
    return new \stdClass;
  }
  static function onResponseRaw($env, $response, $request) {
    return new \stdClass;
  }
  static function onResponseFormed($env, $output, $input) {
    return new \stdClass;
  }
}