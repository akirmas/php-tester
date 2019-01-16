<?php
require_once(__DIR__.'/CycleHandler.php');

class CommonHandler extends CycleHandler {
  static function onRequestRaw(object $env, object $input): object {
    $date = (property_exists($input, 'cc:expire:date'))
    ? $input->{'cc:expire:date'}
    : substr('00'.$input->{'cc:expire:month'}, -2)
    . substr('00'.$input->{'cc:expire:year'}, -2);

    // TODO: Fee 
    $amount = $input->amount;
    $currency = $input->currency;
    if (property_exists($input, 'currency:final') && ($currency != $input->{'currency:final'})) {
      $pair = $currency.'_'.$input->{'currency:final'};
      $rate = json_decode(file_get_contents(
        "https://free.currencyconverterapi.com/api/v5/convert?q=$pair&compact=y"
      ))->pair->val;
      $amount = $input->amount * $rate;
      $currency = $input->{'currency:final'};
    }

    $name_full = (property_exists($input, 'name:full'))
    ? $input->{'name:full'}
    : trim(
      (!(property_exists($input, 'name:first')) ? '' : $input->{'name:first'})
      .' '
      .(!(property_exists($input, 'name:last')) ? '' : $input->{'name:last'})
    );
    
    return (object) [
      'cc:expire:date' => $date,
      'amount:final' => $amount,
      'amountInt' => 100 * (float) $amount,
      'currency:final' => $currency,
      'name:full' => $name_full
    ];
  }
  static function onResponseFormed(object $env, object $output, object $input) : object {
    if (!property_exists($output, 'transaction:id')) return new \stdClass;
    return (object) [
      'event' => "$env->instance/$output->{'transaction:id'}"
    ];
  }
}

