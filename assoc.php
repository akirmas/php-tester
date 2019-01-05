<?php
declare(strict_types=1);
namespace assoc;

function mapKeys(
  object $assoc,
  object $keyMap,
  bool $keepUnmet = false
) :object {
  $result = new \stdClass; // class{} or \stdClass 
  forEach((array) $assoc as $key => $value)
    if (property_exists($keyMap, $key))
      $result->{$keyMap->{$key}} = $value;
    elseif ($keepUnmet)
      $result->{$key} = $value;
  return $result;
}
 
function mapValues(
  object $assoc,
  object $valuesMap,
  bool $keepUnmet = false
) :object {
  $result = new \stdClass;
  forEach((array) $assoc as $key => $value) 
    if (
      property_exists($valuesMap, $key)
      && (!in_array(gettype($valuesMap->{$key}), ['array', 'object']))
    )
      $result->{$key} = $valuesMap->{$key};
    elseif (
      property_exists($valuesMap, $key)
      && property_exists($valuesMap->{$key}, (string) $value)
    )
      $result->{$key} = $valuesMap->{$key}->{$value};
    elseif ($keepUnmet)
      $result->{$key} = $value;
  return $result;
}

function merge($doMerge, $doRecursive, ...$objects) :object {
  return (object) call_user_func(
    'array_'
    .['replace', 'merge'][(int) $doMerge]
    .['', '_recursive'][(int) $doRecursive],
    ...array_map(
      function($obj) {return (array) $obj;},
      $objects
    )
  );
}

function flip($obj) :object {
  return (object) array_flip((array) $obj);
}
