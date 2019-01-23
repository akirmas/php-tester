<?php
/**
 * https://devcenter.netpay-intl.com/website/SilentPost_Cc.aspx
 */

require_once(__DIR__.'/../../../CycleHandler.php');

class Netpay extends CycleHandler {
  static function onRequestFilled(object $env, object $request): object {  
    return (object) ['Signature' => base64_encode(hash("sha256",
      join('', [
        $request->account,
        $request->verify,
        $request->installments,
        $request->amount,
        $request->{'currency:final'},
        $request->{'cc:number'},
        $request->salt
      ]),
      true
    ))];
  }
}
