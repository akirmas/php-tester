<?php
require_once(__DIR__.'/utils/index.php');
require_once(__DIR__.'/utils/assoc.php');
require_once(__DIR__.'/CycleHandler.php');
class CommonHandler extends CycleHandler {
  static function onRequestFilled($env, $input) {
    //<Date>
    $date = (\assoc\keyExists($input, 'cc:expire:date'))
    ? $input['cc:expire:date']
    : (\assoc\keyExists($input, 'cc:expire:month') && \assoc\keyExists($input, 'cc:expire:year')
    ? substr('00'.$input['cc:expire:month'], -2)
    . substr('00'.$input['cc:expire:year'], -2)
    : ''
    );
    //</Date>
    //<Names>
    $name_full = (\assoc\keyExists($input, 'name:full'))
    ? $input['name:full']
    : trim(
      (!(\assoc\getValue($input, 'name:first', '')))
      .' '
      .(!(\assoc\getValue($input, 'name:last', '')))
    );
    $names = explode(' ', $name_full);
    $name_last = array_pop($names);
    $name_first = count($names) > 0 ? array_shift($names) : '';
    $name_last = join(' ', array_merge($names, [$name_last]));
    //</Names>
    //<Amount and currency>
    $amount = 0;
    $currencyFinal = '';
    if (\assoc\keyExists($input, 'amount')) {
      $currency = $input['currency'];
      $currencyFinal = $currency;
      $fee = (float) \assoc\getValue($input, 'fee', 0);
      $amount = (1 + $fee) * (float) $input['amount'];
      if (
        \assoc\keyExists($input, 'currency:exchange')
        && ($currency != $input['currency:exchange'])
      ) {
        $pair = $currency.'_'.$input['currency:exchange'];
        //TODO: Other information sources, maybe cache
        //NB! TODO: This sync request could drop script
        $ch = curl_init(
          "https://free.currencyconverterapi.com/api/v5/convert?q=$pair&compact=y"
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        $resp = curl_exec($ch);
        if ($resp !== false) {
          $resp = json_decode($resp, true);
          if ($resp !== null && \assoc\keyExists($pair, $resp) && assoc\keyExists('val', $resp[$pair])) {
            $rate = $resp[$pair]['val'];
            $amount *= $rate;
            $currencyFinal = $input['currency:exchange'];
          }
        }
      }
    }
    //</Amount and currency>

    //<Absolute identifier>
    $idAbsolute = \assoc\join2('/', [
      $input['_account'],
      \assoc\getValue($input, 'id', '')
    ]);
    //</Absolute identifier>

    return [
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
  
  static function onResponseFormed($env, $output, $input) {
    $successing = \assoc\getValue($output, 'success:ing');

    $success = !is_null($successing)
    ? (
      !is_integer($successing)
      || abs($successing) > 1
      ? 0
      : $successing
    ) : (
      \assoc\keyExists($output, 'return:code') 
      ? (
        // intermediate action - therefore for await (-1) shoud be good (0)
        (int) \assoc\getValue($output, 'return:code') === 0
        ? -1
        : 0
        // NB! strange and danger
        // It is like inheritance
        //TODO: something another
      ) : \assoc\getValue($input, 'success', 0)         
    );
    return [
      'id' => $input['id'],
      'success' => $success
    ];
  }
}

function signatureRedSys($data) {
  $id = \assoc\getValue($data, 'id::compress', '');
  $key = \assoc\getValue($data, 'key', '');
  \assoc\deleteKey($data, 'key');
  $len = strlen($id);
  $baseRoot = 8;
  $l = ceil($len / $baseRoot) * $baseRoot;
  return base64_encode(
    hash_hmac(
      'sha256',
      base64_encode(json_encode($data)),
      substr(
        openssl_encrypt(
          $id
          . str_repeat("\0", $l - $len),
          'des-ede3-cbc',
          base64_decode($key),
          OPENSSL_RAW_DATA,
          str_repeat("\0", $baseRoot)
        ),
        0,
        $baseRoot
      ),
      true
    )
  );
}