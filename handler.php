<?php
require_once(__DIR__.'/CycleHandler.php');
require_once(__DIR__.'/utils.php');
class CommonHandler extends CycleHandler {
  static function onRequestFilled(object $env, object $input): object {
    //<Date>
    $date = (property_exists($input, 'cc:expire:date'))
    ? $input->{'cc:expire:date'}
    : (property_exists($input, 'cc:expire:month') && property_exists($input, 'cc:expire:year')
    ? substr('00'.$input->{'cc:expire:month'}, -2)
    . substr('00'.$input->{'cc:expire:year'}, -2)
    : ''
    );
    //</Date>
    //<Names>
    $name_full = (property_exists($input, 'name:full'))
    ? $input->{'name:full'}
    : trim(
      (!(property_exists($input, 'name:first')) ? '' : $input->{'name:first'})
      .' '
      .(!(property_exists($input, 'name:last')) ? '' : $input->{'name:last'})
    );
    $names = explode(' ', $name_full);
    $name_last = array_pop($names);
    $name_first = count($names) > 0 ? array_shift($names) : '';
    $name_last = join(' ', array_merge($names, [$name_last]));
    //</Names>
    //<Amount and currency>
    $amount = 0;
    $currencyFinal = '';
    if (property_exists($input, 'amount')) {
      $currency = $input->currency;
      $currencyFinal = $currency;
      $fee = !property_exists($input, 'fee') ? 0 : (float) $input->fee;
      $amount = (1 + $fee) * (float) $input->amount;
      if (
        property_exists($input, 'currency:exchange')
        && ($currency != $input->{'currency:exchange'})
      ) {
        $pair = $currency.'_'.$input->{'currency:exchange'};
        //TODO: Other information sources, maybe cache
        //NB! TODO: This sync request could drop script
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
            $currencyFinal = $input->{'currency:exchange'};
          }
        }
      }
    }
    //</Amount and currency>
    //<Absolute identifier>
    $idAbsolute = join('/', [
      $input->_account,
      !property_exists($input, 'id') ? '' : $input->id
    ]);
    //</Absolute identifier>
    return (object) [
      'cc:expire:date' => $date,
      'name:full' => $name_full,
      'name:first' => $name_first,
      'name:last' => $name_last,
      'amount:final' => $amount,
      'amountInt:final' => 100 * (float) $amount,
      'currency:final' => $currencyFinal,
      'fee:final' => 0,
      'id:absolute' => $idAbsolute
    ];
  }
  
  static function onResponseFormed(object $env, object $output, object $input) : object {
    $success = property_exists($output, 'success:ing')
    ? (
      gettype($output->{'success:ing'}) !== 'integer'
      || abs($output->{'success:ing'}) > 1
      ? 0
      : $output->{'success:ing'}
    ) : (
      property_exists($output, 'return:code') 
      ? (
        // intermediate action - therefore for await (-1) shoud be good (0)
        (int) $output->{'return:code'} === 0
        ? -1
        : 0
      ) : (
        // NB! strange and danger
        // It is like inheritance
        //TODO: something another
        property_exists($input, 'success') 
        ? (int) $input->success
        : 0
      )
    );
    return (object) [
      'id' => $input->id,
      'success' => $success
    ];
  }
}
