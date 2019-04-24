<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');

class Paypal extends CycleHandler {
  static function onResponseRaw($env, $response, $request) {
    $inputs = array_filter(
        $response,
        function ($key) {
          return !in_array($key, [
            'form:action',
            'form:method',
            'form:target'
          ]);
        },
        ARRAY_FILTER_USE_KEY
      );
      return [
        'form:inputs' => json_encode($inputs, JSON_UNESCAPED_SLASHES)
      ];
  }
}

