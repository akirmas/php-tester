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

function merge(...$objects) {
  $base = (array) array_shift($objects);
  forEach($objects as $obj)
    forEach((array) $obj as $key => $value) {
      $base[$key] = (
        !array_key_exists($key, $base)
        || !isESObject($value)
        || !isESObject($base[$key])
      )
      ? $value
      : merge($base[$key], $value);
    }
  return $base;
}

function flip($obj) :object {
  return (object) array_flip((array) $obj);
}

function isESObject($var) {
  return in_array(gettype($var), ['array', 'object']);
}
