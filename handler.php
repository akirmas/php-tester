<?php
require_once(__DIR__.'/CycleHandler.php');

class CommonHandler extends CycleHandler {
  static function onRequestRaw(object $env, object $input): object {
    $date = (property_exists($input, 'cc:expire:date'))
    ? $input->{'cc:expire:date'}
    : (property_exists($input, 'cc:expire:month') && property_exists($input, 'cc:expire:year')
    ? substr('00'.$input->{'cc:expire:month'}, -2)
    . substr('00'.$input->{'cc:expire:year'}, -2)
    : ''
    );

    $name_full = (property_exists($input, 'name:full'))
    ? $input->{'name:full'}
    : trim(
      (!(property_exists($input, 'name:first')) ? '' : $input->{'name:first'})
      .' '
      .(!(property_exists($input, 'name:last')) ? '' : $input->{'name:last'})
    );
    
    $names = explode(' ', $name_full);
    $name_last = array_pop($names);
    $name_first = sizeof($names) > 0 ? array_shift($names) : '';
    $name_last = join(' ', array_merge($names, [$name_last]));
    return (object) [
      'cc:expire:date' => $date,
      'name:full' => $name_full,
      'name:first' => $name_first,
      'name:last' => $name_last
    ];
  }
  static function onRequestFormed(object $env, object $request): object {
    $amount = $request->amount;
    $currency = $request->currency;
    $fee = !property_exists($request, 'fee') ? 0 : (float) $request->fee;
    if (
      property_exists($request, 'currency:final')
      && ($currency != $request->{'currency:final'})
    ) {
      $pair = $currency.'_'.$request->{'currency:final'};
      $rate = json_decode(file_get_contents(
        "https://free.currencyconverterapi.com/api/v5/convert?q=$pair&compact=y"
      ))->{$pair}->val;
      $amount = $request->amount * $rate * (1 + $fee);
      $currency = $request->{'currency:final'};
    }
    return (object) [
      'amount:final' => $amount,
      'amountInt:final' => 100 * (float) $amount,
      'currency:final' => $currency,
      'fee:final' => 0
    ];
  }
  static function onResponseFormed(object $env, object $output, object $input) : object {
    if (!property_exists($output, 'event:id')) return new \stdClass;
    return (object) [
      'event' => "$env->instance/".$output->{'event:id'}
    ];
  }
}

