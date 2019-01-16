<?php
$gateway = "https://uiservices.pay-finder.com/hosted/?";
$hash = "YINM2ZOTGC";

$phoneNumber = "1111111111111111111111112";

$params = array(
  "merchantID" => "5200906",
  "trans_amount" => 553,
  "trans_currency" => "USD",
  "trans_type" => 0, // ["Debit Transaction", "Authorization only"]
  "trans_installments" => 1,
  "disp_paymentType" => "CC", // List of payment types that are available to the client. Available values are can be found here: Payment Methods. this list is use the abbreviation field from the list. If more than one, use comma to separate the values. (example: CC,ED)
  "trans_refNum" => "ph.{$phoneNumber}", // Unique text used to defer one transaction from another
  "disp_payFor" => "Green Card Service", // Text shown to buyer in payment window, Usually description of purchase (Cart description, Product name)
  "trans_comment" => "some comment", // Optional text used mainly to describe the transaction
  "url_notify" => "https://payment.gobemark.info/php/psps/collector/?instance=Payfinder&",
  "url_redirect" => "",
  "client_email" => "test@gbm.com",
  "client_fullName"	=> "andy test",
  "client_phoneNum"	=> $phoneNumber,
  'client_billAddress1' => "address0", // client_billAddress2
  'client_billCity' => 'Tel-Aviv', // not picked up
  'client_billCountry' => 'IL',
  'client_billZipcode' => 'zip'
);

$params['signature'] = base64_encode(hash("sha256", join(array_values($params)).$hash, true));
$url = $gateway.http_build_query($params);
?>

<div>
<iframe src="<?=$url?>" width="400px" height="800px" ></iframe>
</div>
