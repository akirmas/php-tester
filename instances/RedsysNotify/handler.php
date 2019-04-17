<?php
require_once(__DIR__.'/../../CycleHandler.php');
require_once(__DIR__.'/../../utils/assoc.php');
require_once(__DIR__.'/../functions.php');

class RedsysNotify extends CycleHandler {
  static function onRequestFilled($env, $request) {
    $data = json_decode(base64_decode($request['Ds_MerchantParameters']), true);
    $key = $request['account:key'];
    /*if (
      str_replace(
        ['-', '_'],
        ['+', '/'],
        $request['Ds_Signature']
      ) !== redsysSignature($key, $data)
    ) 
      exit;*/
    return $data;
  }
}