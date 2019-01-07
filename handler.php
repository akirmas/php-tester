<?php
require_once(__DIR__.'/CycleHandler.php');

class CommonHandler extends CycleHandler {
  static function onRequestRaw(object $input): object {
    $date = (property_exists($input, 'cc:expire:date'))
    ? $input->{'cc:expire:date'}
    : substr('00'.$input->{'cc:expire:month'}, -2)
    . substr('00'.$input->{'cc:expire:year'}, -2);

    $amount = $input->amount;
    if (property_exists($input, 'currency:final') && ($input->currency != $input->{'currency:final'})) {
      $pair = $input->currency.'_'.$input->{'currency:final'};
      $rate = json_decode(file_get_contents(
        "https://free.currencyconverterapi.com/api/v5/convert?q=$pair&compact=y"
      ))->pair->val;
      $amount = $input->amount * $rate;
    }

    $name_full = (property_exists($input, 'name:full'))
    ? $input->{'name:full'}
    : trim(
      (!(property_exists($input, 'name:first')) ? '' : $input->{'name:first'})
      .' '
      .(!(property_exists($input, 'name:last')) ? '' : $input->{'name:last'})
    );
    
    return (object) array(
      'cc:expire:date' => $date,
      'amount:final' => $amount,
      'amountInt' => 100 * (float) $amount,
      'name:full' => $name_full
    );
  }
}

