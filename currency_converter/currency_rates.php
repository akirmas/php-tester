<?php

class CurrencyRate {

	protected $_allowedCurrencies = null;
	protected $_testId = null;
	
	public function __construct()
	{
		$allowedCurrencies = json_decode(file_get_contents('common_currencies.json'), true);
		if(is_array($allowedCurrencies)) $this->_allowedCurrencies = $allowedCurrencies;
		else throw new Exception('Can not initialize CurrencyRate object!');
	}

	public function setTestId($testId){
		$this->_testId = $testId;
	}

	public function getAllowedCurrencies()
	{
		return $this->_allowedCurrencies;
	}

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
				if(!is_null($this->_testId)){
					//At first let's look at the cache:
					$rate = $this->_getRateFromCache($currenciesPair, $this->_testId);
					if($rate === false){
						$this->_logActionMessage($this->_testId,
							'USD_UAH request to external API sending...', 'request');
						$rate = $this->_getRateFromExternalApi($currenciesPair);
						if($rate === false){
							$this->_logActionMessage($this->_testId, 'USD_UAH response was not received from the external API.', 'response_failure');
							throw new Exception('Failed to receive rate from external API!');
						}
						$this->_putReceivedResponseToCache($currenciesPair, $rate, $this->_testId);
						$this->_logActionMessage($this->_testId, 'USD_UAH response from external API received.', 'response');
						$this->_logActionMessage($this->_testId, 'USD_UAH response from external API is saved to the local cache.', 'rate_is_cached');
					} else {
						$this->_logActionMessage($this->_testId, 'USD_UAH response received from the local cache.', 'response_received_from_cache');
					}
				} else {
					$rate = 200500;
				}
			return $rate;
			break;
			case 'USD_USD':
				return 1;
			break;
		}
	}

	protected function _putReceivedResponseToCache($currenciesPair, $rate, $testId)
	{
		$cachedDataArray = json_decode(file_get_contents('rates_cache.txt'), true);
		if(!is_array($cachedDataArray)){
			$cachedDataArray = [];
		}
		$cachedDataArray[$currenciesPair]['time'] = time();
		$cachedDataArray[$currenciesPair]['rate'] = $rate;
		$cachedDataArray[$currenciesPair]['testId'] = $testId;
		file_put_contents('rates_cache.txt', json_encode($cachedDataArray));
	}

	protected function _getRateFromCache($currenciesPair, $testId)
	{
		$cachedDataArray = json_decode(file_get_contents('rates_cache.txt'), true);
		if(!is_array($cachedDataArray)){
			return false;
		}
		if(!array_key_exists($currenciesPair, $cachedDataArray)){
			return false;
		}
		$timeOfTheRecord = $cachedDataArray[$currenciesPair]['time'];
		if(time() - $timeOfTheRecord > 86400){
			return false;
		}
		if(isset($testId)){
			if(isset($cachedDataArray[$currenciesPair]['testId'])
				&& ($cachedDataArray[$currenciesPair]['testId'] === $testId))
				return $cachedDataArray[$currenciesPair]['rate'];
			return false;
		} else return $cachedDataArray[$currenciesPair]['rate'];
	}

	protected function _logActionMessage($testId, $message, $messageType)
	{
		$testId = $testId . '_' . $messageType;
		$logFileName = 'logged_test_messages.log';
		$currentMessages = json_decode(file_get_contents($logFileName), true);
		if(array_key_exists($testId, $currentMessages)){
			throw new Exception('Duplicate testId!');
		}
		$currentMessages[$testId] = $message;
		file_put_contents($logFileName, json_encode($currentMessages));
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
		if(array_key_exists($currencyFrom, $this->_allowedCurrencies) && array_key_exists($currencyTo, $this->_allowedCurrencies)){
			return true;
		}
		return false;
	}

	protected function _getRateFromExternalApi($currenciesPair)
	{
		return 100500;
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
/*
$_GET['q'] = 'usd_uah';
$_GET['test_id'] = '878787834324';
*/
try {
	$currenciesPair = strtoupper($_GET['q']);
	if(empty(trim($currenciesPair))){
		throw new Exception('Empty currencies pair provided!');
	}
	$currencyRate = new CurrencyRate();
	if(isset($_GET['test_id'])){
		$currencyRate->setTestId($_GET['test_id']);	
	}
	$rate = $currencyRate->getRateByPair($currenciesPair);
	$responseArray = array("$currenciesPair" => array("val" => $rate));
	$jsonStr = json_encode($responseArray);
	echo $jsonStr;
} catch(Exception $e){
	echo json_encode(['errorMessage' => $e->getMessage()]);
}