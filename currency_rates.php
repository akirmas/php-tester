<?php

class CurrencyRate {

	protected $_allowedCurrencies = [
		'USD', 'UAH'
		];

	public function getRateByPair($currenciesPair)
	{
		$currenciesPair = strtoupper($currenciesPair);
		if(!$this->_isValidCurrenciesPair($currenciesPair)){
			throw new Exception('Not a valid currency pair provided!');
		}
		return $this->_getRate($currenciesPair);
	}

	protected function _getRate($currenciesPair)
	{
		switch($currenciesPair){
			case 'USD_UAH':
				return 2;
			break;
			case 'USD_USD':
				return 1;
			break;
		}
	}

	protected function _isValidCurrenciesPair($currenciesPair)
	{
		if(strlen($currenciesPair) !== 7){
			return false;
		}
		if(strpos($currenciesPair, '_') !== 3){
			return false;
		}
		$currenciesPairArray = explode('_', $currenciesPair);
		$currencyFrom = $currenciesPairArray[0];
		$currencyTo = $currenciesPairArray[1];
		if(in_array($currencyFrom, $this->_allowedCurrencies) && in_array($currencyTo, $this->_allowedCurrencies)){
			return true;
		}
		return false;
	}

	protected function _getRateFromExternalApi($currenciesPair)
	{
		$rate = false;
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
		}
		return $rate;
	}

}

try {
	$currenciesPair = strtoupper($_GET['q']);
	$currencyRate = new CurrencyRate();
	$rate = $currencyRate->getRateByPair($currenciesPair);
	$responseArray = array("$currenciesPair" => array("val" => $rate));
	$jsonStr = json_encode($responseArray);
	echo $jsonStr;
} catch(Exception $e){
	echo json_encode(['errorMessage' => $e->getMessage()]);
}