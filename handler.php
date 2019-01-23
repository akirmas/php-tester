<?php
require_once(__DIR__.'/CycleHandler.php');
require_once(__DIR__.'/utils.php');
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
    $currency = $request->currency;
    $currencyFinal = $currency;
    $fee = !property_exists($request, 'fee') ? 0 : (float) $request->fee;
    $amount = (1 + $fee) * (float) $request->amount;
    if (
      property_exists($request, 'currency:exchange')
      && ($currency != $request->{'currency:exchange'})
    ) {
      $pair = $currency.'_'.$request->{'currency:exchange'};
      //TODO: Other information sources, maybe cache
      $ch = curl_init(
        "https://free.currencyconverterapi.com/api/v5/convert?q=$pair&compact=y"
      );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
      $resp = curl_exec($ch);
      if ($resp !== false) {
        $resp = json_decode($resp, true);
        if ($resp !== null && array_key_exists($pair, $resp) && array_key_exists('val', $resp[$pair])) {
          $rate = $resp[$pair]['val'];
          $amount *= $rate;
          $currencyFinal = $request->{'currency:exchange'};
        }
      }
    }
    return (object) [
      'amount:final' => $amount,
      'amountInt:final' => 100 * (float) $amount,
      'currency:final' => $currencyFinal,
      'fee:final' => 0
    ];
  }
  static function onResponseFormed(object $env, object $output, object $input) : object {
    return (object) \assoc\merge(
      ['success' => -1],
      (!property_exists($output, 'event:id'))
      ? []
      : ['event' => "$env->instance/".$output->{'event:id'}]
    );
  }
}
