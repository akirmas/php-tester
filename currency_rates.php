<?php

$currenciesPair = strtoupper($_GET['q']);
/*
$currenciesPairArray = explode('_', $currenciesPair);
$currencyFrom = $currenciesPairArray[0];
$currencyTo = $currenciesPairArray[1];
*/
$ch = curl_init(
    "https://free.currencyconverterapi.com/api/v5/convert?q=$currenciesPair&compact=y"
    );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
$resp = curl_exec($ch);
if ($resp !== false) {
	$resp = json_decode($resp, true);
	if ($resp !== null && array_key_exists($currenciesPair, $resp) && array_key_exists('val', $resp[$currenciesPair])) {
		$rate = $resp[$currenciesPair]['val'];
   	}
} else $rate = false;
$responseArray = array("$currenciesPair" => array("val" => $rate));
$jsonStr = json_encode($responseArray);
echo $jsonStr;