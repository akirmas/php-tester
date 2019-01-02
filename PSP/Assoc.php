<?php
declare(strict_types=1);
require_once(__DIR__.'/../PSP/assoc_functions.php');
abstract class Assoc {
  protected /*static*/ $prefix;

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
    return \assoc\mapKeys($this->toAssoc(), $keysMap, $keepUnmet);}
  function myMapValues(array $valuesMap) {
    return \assoc\mapValues($this->toAssoc(), $valuesMap);}
  function myMapKeyValues(
    array $keysMap,
    array $valuesMap,
    bool $keepUnmetKeys = false,
    bool $keepUnmetValues = false)
  :array {
    return \assoc\mapKeyValues($this->toAssoc(),
    $keysMap,
    $valuesMap,
    $keepUnmetKeys,
    $keepUnmetValues
  );}

  function myFilterEmpty() { return \assoc\filterEmpty($this); }
  function assoc() :array { return \assoc\toAssoc($this); }
}
