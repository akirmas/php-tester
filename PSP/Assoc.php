<?php
declare(strict_types=1);

abstract class Assoc {
  protected /*static*/ $prefix;

  static function mapKeys(
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

  static function mapValues(
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

  static function mapKeyValues(
    array $assoc,
    array $keysMap,
    array $valuesMap,
    bool $keepUnmetKeys = false,
    bool $keepUnmetValues = false
  ) :array {
    return self::mapKeys(
      self::mapValues($assoc, $valuesMap, $keepUnmetValues),
      $keysMap,
      $keepUnmetKeys
    );
  }

  static function filterEmpty(&$arg) {
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

  static function toAssoc($who) :array {
    if (gettype($who) === 'array')
      return $who;
    
    if (gettype($who) !== 'object')
      return array($who => true);
      
    $assoc = get_object_vars($who);
    $mangled = array_keys((array) $who);
    if (!property_exists($who, 'prefix') || empty($who->prefix))
      return $assoc;

    $result = [];
    forEach($assoc as $key => $value)
      if (in_array($key, $mangled))
        $result[$who->prefix."$key"] = $value;
    return $result;
  }

  function __construct(array $assoc = [], object $who = null) {
    if ($who === null) $who = $this;
    $prefix = (!property_exists($who, 'prefix') || empty($who->prefix))
    ? '' : $who->prefix;
    forEach(
      array_keys(get_object_vars($who)) as $field
    ) {
      if (array_key_exists("$prefix$field", $assoc))
        $who->{$field} = $assoc["$prefix$field"];
    }
  }

  function myMapKeys(array $keysMap, bool $keepUnmet = false) :array {  
    return self::mapKeys($this->toAssoc(), $keysMap, $keepUnmet);}
  function myMapValues(array $valuesMap) {
    return self::mapValues($this->toAssoc(), $valuesMap);}
  function myMapKeyValues(
    array $keysMap,
    array $valuesMap,
    bool $keepUnmetKeys = false,
    bool $keepUnmetValues = false)
  :array {
    return self::mapKeyValues($this->toAssoc(),
    $keysMap,
    $valuesMap,
    $keepUnmetKeys,
    $keepUnmetValues
  );}

  function myFilterEmpty() { return self::filterEmpty($this); }
  function assoc() :array { return self::toAssoc($this); }
}
