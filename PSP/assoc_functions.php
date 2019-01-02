<?php
declare(strict_types=1);
namespace assoc;

function mapKeys(
  array $assoc,
  array $keyMap,
  bool $keepUnmet = false
) :array {
  $result = [];
  forEach($assoc as $key => $value)
    if (array_key_exists($key, $keyMap)
      // if already mapped
      /*|| in_array($key, $keyMap)*/
    )
      $result[$keyMap[$key]] = $value;
    elseif ($keepUnmet)
      $result[$key] = $value;
  return $result;
}

// VV means Vice Versa
function mapKeysVV(
  array $assoc,
  array $keyMap,
  bool $keepUnmet = false
) :array {
  return mapKeys($assoc, array_flip($keyMap), $keepUnmet);
}

function mapValues(
  array $assoc,
  array $valuesMap,
  $keepUnmet = false
) :array {
  $result = [];
  forEach($assoc as $key => $value)
    if (
      array_key_exists($key, $valuesMap)
      && (gettype($valuesMap[$key]) !== 'array')
    )
      $result[$key] = $valuesMap[$key];
    elseif (
        array_key_exists($key, $valuesMap)
        && array_key_exists($value, $valuesMap[$key])
      )
        $result[$key] = $valuesMap[$key][$value];
      elseif ($keepUnmet)
        $result[$key] = $value;
  return $result;
}


function mapKeyValues(
  array $assoc,
  array $keysMap,
  array $valuesMap,
  bool $keepUnmetKeys = false,
  bool $keepUnmetValues = false
) :array {
  return mapKeys(
    mapValues($assoc, $valuesMap, $keepUnmetValues),
    $keysMap,
    $keepUnmetKeys
  );
}

function filterEmpty(&$arg) {
  $isObj = false;
  switch(gettype($arg)) {
    case 'array': break;
    case 'object':
      $isObj = true;
      break;
    default:
      if (empty($arg)) return;
      else return $arg;
  }
  forEach(
    ($isObj ? get_object_vars($arg) : $arg)
    as $key => $value
  )
    if (empty($value) && !in_array($value, [0, 0.0, '0'], true)) {
      if ($isObj) unset($arg->{$key});
      else unset($arg[$key]);
    }
  return $arg;
}

function toAssoc($who) :array {
  if (gettype($who) === 'array')
    return $who;
  if (gettype($who) !== 'object')
    return array($who => true);
    
  $assoc = get_object_vars($who);
  $mangled = array_keys((array) $who);
  // TODO without mangling?
  if (!property_exists($who, 'prefix') || empty($who->prefix))
    return $assoc;

  $result = [];
  forEach($assoc as $key => $value)
    if (in_array($key, $mangled))
      $result[$who->prefix."$key"] = $value;
  return $result;
}

function assoc2table($assoc, &$output = [], $prefix = []) {
  if (gettype($assoc) !== 'array') {
    $node = array_merge($prefix, [$assoc]);
    array_push($output, $node);
    return $node;
  }
  forEach($assoc as $key => $value) {
    assoc2table($value, $output, array_merge($prefix, [$key]));
  }
  return $output;
}

function table2assoc($table, $output = null) {
  if (sizeof($table) === 0)
    return $output;
  $trajectory = trajectory2assoc(array_pop($table));
  return table2assoc(
    $table,
    $output === null
    ? $trajectory
    : array_merge_recursive(
      $output,
      $trajectory
    )
  );
}

function trajectory2assoc($trajectory) {
  $result = null;
  forEach(array_reverse($trajectory) as $key)
    $result = $result === null ? $key : array($key => $result);
  return $result;
}


// from k0-v0-v1 and k0-k1 build k1-v1-v0
function valuesMapVV($valuesMap, $keyMap, $keepUnmet = false) {
  return table2assoc(array_reduce(
    assoc2table($valuesMap),
    function ($accum, $row) use ($keyMap, $keepUnmet) {
      $k0 = $row[0];
      $k1 = array_key_exists($k0, $keyMap)
      ? $keyMap[$k0]
      : ($keepUnmet
      ? $k0
      : null
      );
      return $k1 === null ? $accum : array_merge($accum, [[$k1, $row[2], $row[1]]]);
    },
    []
  ));
}

function mapKeyValuesVV(
  array $assoc,
  array $keysMap,
  array $valuesMap,
  bool $keepUnmetKeys = false,
  bool $keepUnmetValues = false
) :array {
  return mapKeyValues(
    $assoc,
    array_flip($keysMap),
    valuesMapVV($valuesMap, $keysMap, $keepUnmetKeys),
    $keepUnmetKeys,
    $keepUnmetValues
  );
}

?>