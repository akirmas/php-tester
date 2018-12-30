<?php
declare(strict_types=1);

require_once(__DIR__.'/../PSP/Assoc.php');

class PSP {
  static protected function querify(array $args, object $env) :array {
    $fields = Assoc::filterEmpty($env->fields);
    $values = Assoc::filterEmpty($env->values);

    $arrs = array_merge(...array_map(function($el) {
      return Assoc::toAssoc(Assoc::filterEmpty($el));
    }, $args));
    return array_merge(
      ...array_map(
        function($el) use ($fields, $values) {
          return !in_array(gettype($el), ['array', 'object'])
            ? []
            : Assoc::mapKeyValues(
              Assoc::toAssoc(Assoc::filterEmpty($el)),
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

