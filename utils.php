<?php
class CreditCard {
  public $number;
  public $cvv;
  public $month;
  public $year;

  function __construct($number, $month, $year, $cvv) {
    $this->number = $number;
    $this->month = $month;
    $this->year = $year;
    $this->cvv = $cvv;
  }

}

class Transaction {
  public $amount;
  public $currency;
  public $creditCard;

  function __construct(int $amount, string $currency, CreditCard $creditCard) { 
    $this->amount = $amount;
    $this->currency = $currency;
    $this->creditCard = $creditCard;
  }
}

function obj2assoc($object, $spacename = '') {
  $assoc = array();
  $prefix = $spacename . ($spacename === '' ? '' : '/');
  foreach(get_object_vars($object) as $key => $value) {
    if (empty($value)) break;
    if (gettype($value) == 'object') {
      $assoc = array_merge($assoc, obj2assoc($value, $prefix.get_class($value)));
    } else {
      $assoc[$prefix.$key] = $value;
    }
  }
  return $assoc;
}


// or array_walk...
function mapKeys($assoc, $map, $strict = false) {
  $output = array();
  foreach($assoc as $key => $value) {
    $newKey = array_key_exists($key, $map)
      ? $map[$key]
      : (!$strict
        ? $key
        : null
      )
    ;
  
    if (!empty($newKey))
      $output[$newKey] = $value;
  }
  return $output;
}

$tr = new Transaction(1, "GBP", new CreditCard(123,2345, 123, 234));

$assoc = obj2assoc($tr);
$map =   array('CreditCard/number' => 'ccno');

print_r(mapKeys(
  $assoc,
  $map
));
print_r(mapKeys(
  $assoc,
  $map,
  true
));