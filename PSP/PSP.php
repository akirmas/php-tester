<?php
declare(strict_types=1);
require_once(__DIR__.'/../PSP/assoc_functions.php');
require_once(__DIR__.'/../PSP/Assoc.php');

class PSP {
  static protected function querify(array $args, object $env) :array {
    $fields = \assoc\filterEmpty($env->fields);
    $values = \assoc\filterEmpty($env->values);

    $arrs = array_merge(...array_map(function($el) {
      return \assoc\toAssoc(\assoc\filterEmpty($el));
    }, $args));
    return array_merge(
      ...array_map(
        function($el) use ($fields, $values) {
          return !in_array(gettype($el), ['array', 'object'])
            ? []
            : \assoc\mapKeyValues(
              \assoc\toAssoc(\assoc\filterEmpty($el)),
              $fields,
              $values,
              false,
              true
            );
        },
        array_merge(
          [$env->defaults],
          $args,
          [$env->overrides]
        )          
      )
    );
  }
}

