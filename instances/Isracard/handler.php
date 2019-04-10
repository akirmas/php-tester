<?php
require_once(__DIR__.'/../../CycleHandler.php');

class Isracard extends CycleHandler {
  static function onResponseFormed($env, $r, $i) {
    return (
      $r['return:code'] != 0 ? []
      : array(
        'quizUrl' => $r['quizUrl:raw'].'?'.http_build_query(
          array_reduce(
            //TODO: should be picked up from Isracard/index.json
            [
              ['name:first' => 'first_name'],
              ['name:last' => 'last_name'],
              ['email' => 'email'],
              ['phone' => 'phone']
            ],
            function($acc, $fields) use ($i) {
              $commonKey = \assoc\keys($fields)[0];
              $instanceKey = $fields[$commonKey];
              if (\assoc\keyExists($i, $commonKey) && !empty($i[$commonKey]))
                $acc[$instanceKey] = $i[$commonKey];
              return $acc;
            },
            []
          )
        )
      )
    );
  }
}

