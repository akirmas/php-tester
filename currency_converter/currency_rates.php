<?php

class CurrencyRate {

	protected $_allowedCurrencies = null;
	protected $_requestId = null;
	protected $_logFileName = 'logged_test_messages.log';
	
	public function __construct($currenciesPair)
	{
		$allowedCurrencies = json_decode(file_get_contents('common_currencies.json'), true);
		if(is_array($allowedCurrencies)) $this->_allowedCurrencies = $allowedCurrencies;
		else throw new Exception('Can not initialize CurrencyRate object!');
		$this->_requestId = $currenciesPair . '_' . time() . '_' . mt_rand(1000, 20000);
	}

	public function setRequestId($requestId){
		$this->_requestId = $requestId;
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
		/*
		* For testing purposes we may want to expire the cache:
		*/
		if(strstr($this->_requestId, '_make_the_cache_expired')){
			$this->_makeTheCacheExpired($currenciesPair);
		}
		//At first let's look at the cache:
		$rate = $this->_getRateFromCache($currenciesPair);
		/*
		* If nothing is in the cache or the cache is expired,
		* we have to make a request to the external API:
		*/
		if($rate === false){
			$this->_logActionMessage($this->_requestId,
				$currenciesPair . ' request to external API sending...', 'request');
			$rate = $this->_getRateFromExternalApi($currenciesPair);
			/*
			* We throw an Exception if we do not manage to
			* retrieve the rate from the external API:
			*/
			if($rate === false){
				$this->_logActionMessage($this->_requestId, $currenciesPair . ' response was not received from the external API.', 'response_failure');
				throw new Exception('Failed to receive rate from external API!');
			}
			//Now let's update the cache with the newly received rate:
			$this->_putReceivedResponseToCache($currenciesPair, $rate);
			$this->_logActionMessage($this->_requestId, $currenciesPair . ' response from external API received.', 'response');
			$this->_logActionMessage($this->_requestId, $currenciesPair . ' response from external API is saved to the local cache.', 'rate_is_cached');
		} else {
			//Let's write a message to the log if we managed to get the rate from the cache:
			$this->_logActionMessage($this->_requestId, $currenciesPair . ' response received from the local cache.', 'response_received_from_cache');
		}
		return $rate;
	}

	protected function _logActionMessage($requestId, $message, $messageType)
	{
		$currentMessages = json_decode(file_get_contents($this->_logFileName), true);
		if(!is_array($currentMessages)){
			$currentMessages = [];
		}
		$actionId = $requestId . '_' . $messageType;
		if(array_key_exists($actionId, $currentMessages)){
			throw new Exception('Duplicate actionId!');
		}
		$currentMessages[$actionId] = $message;
		file_put_contents($this->_logFileName, json_encode($currentMessages));
	}

	protected function _makeTheCacheExpired($currenciesPair)
	{
		$cachedDataArray = json_decode(file_get_contents('rates_cache.txt'), true);
		if(!is_array($cachedDataArray)){
			$cachedDataArray = [];
		}
		$cachedDataArray[$currenciesPair]['time'] = $cachedDataArray[$currenciesPair]['time'] - 100000;
		file_put_contents('rates_cache.txt', json_encode($cachedDataArray));
	}

	protected function _putReceivedResponseToCache($currenciesPair, $rate)
	{
		$cachedDataArray = json_decode(file_get_contents('rates_cache.txt'), true);
		if(!is_array($cachedDataArray)){
			$cachedDataArray = [];
		}
		$cachedDataArray[$currenciesPair]['time'] = time();
		$cachedDataArray[$currenciesPair]['rate'] = $rate;
		file_put_contents('rates_cache.txt', json_encode($cachedDataArray));
	}

	protected function _getRateFromCache($currenciesPair)
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
		return $cachedDataArray[$currenciesPair]['rate'];
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
		switch($currenciesPair){
			case 'USD_USD':
				return 1;
			break;
			default:
				return 100500;
			break;
		}
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
	if(empty(trim($currenciesPair))){
		throw new Exception('Empty currencies pair provided!');
	}
	$currencyRate = new CurrencyRate($currenciesPair);
	if(isset($_GET['test_id'])) $currencyRate->setRequestId($_GET['test_id']);
	$rate = $currencyRate->getRateByPair($currenciesPair);
	echo json_encode(array("$currenciesPair" => array("val" => $rate)));
} catch(Exception $e){
	echo json_encode(['errorMessage' => $e->getMessage()]);
}