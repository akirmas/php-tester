<?php
declare(strict_types=1);

abstract class Assoc {
  private $_filterEmpty;
  function __construct(array $assoc, bool $filterEmpty = true, object $who = null) {
    if ($who === null) $who = $this;
    $who->_filterEmpty = $filterEmpty;
    forEach(array_keys(get_object_vars($who)) as $field) {
      if (
        array_key_exists($field, $assoc)
        && !$who->_filterEmpty or !empty($assoc[$field])
      )
        $who->{$field} = $assoc[$field];
    }
  }
  function toAssoc() {
    return array_filter(
      get_object_vars($this),
      function($v, $k) {
        return substr($k, 0, 1) !== '_'
        && (!$this->_filterEmpty || !empty($v));
      },
      ARRAY_FILTER_USE_BOTH 
    );
  }
}