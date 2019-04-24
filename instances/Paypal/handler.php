<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');

class Isracard extends CycleHandler {
  static function onResponseRaw($env,$response, $request) {
    return !\assoc\keyExists($env, ['engine', 'gateway'])
    ? []
    : [
      'quizUrl' => \assoc\getValue($response, ['engine', 'gateway'])
      .'?'.http_build_query($response)
    ];
  }
}

