<?php

class CurrencyRate {

	/** @var array|null Should contain allowed currency codes('USD', 'UAH', etc) */
	protected $_allowedCurrencies = null;
	/** @var string|null
	* ID of each request to this class. We need this to
	* separate each record in the logs and for testing purposes.
	* Will be generated in __construct() on each class
	* instantiation. Also can be set manually with
	* setRequestId() method.
	*/
	protected $_requestId = null;
	/** @var string
	* A name for the log where we will record
	* requests to the external API(to get currency rates).
	* Also actions like storing currency rates to the local cache,
	* retrieving rates from the local cache, etc are recorded.
	*/
	protected $_logFileName = 'logged_test_messages.log';
	/** @var integer
	* The amount of time we will keep currency rates
	* in our local cache.
	*/
	protected $_timeToStoreInCache = 86400;
	
	/** @var string
	* A name for the file where cached rates are stored.
	*/
	protected $_cacheFileName = 'rates_cache.txt';

	/**
	* Initializes allowed currency codes from the external storage
	* and generates current requestId.
	*
	* @param string $currenciesPair The pair of currencies for which we
	* need to get the exchange rate.
	*
	* @throws Exception if we did not manage to set the list of
	* allowed currency codes.
	*
	* @return void
	*/
	public function __construct($currenciesPair)
	{
		$allowedCurrencies = json_decode(file_get_contents('common_currencies.json'), true);
		if(is_array($allowedCurrencies)) $this->_allowedCurrencies = $allowedCurrencies;
		else throw new Exception('Can not initialize CurrencyRate object!');
		$this->_requestId = $currenciesPair . '_' . time() . '_' . mt_rand(1000, 20000);
	}

	/**
	* Sets ID for current request to the class.
	*
	* @param string $requestId This ID will be stored in the requests log
	* to uniquely identify each request. By default it is generated in __construct()
	* on each class instantiation, but may be set manually from outside(mainly for
	* testing purposes).
	*
	* @return void
	*/
	public function setRequestId($requestId){
		$this->_requestId = $requestId;
	}

	/**
	* Returns the list of allowed currency codes('USD', 'UAH', etc).
	*
	* @return array
	*/
	public function getAllowedCurrencies()
	{
		return $this->_allowedCurrencies;
	}

	/**
	* Returns exchange rate for the specified pair of currencies.
	* Asks the external API about a rate for the specified pair of currencies.
	* Then stores the retrieved rate in the local cache for a specified period
	* of time. If the cache is old asks the external API again.
	*
	* @param string $currenciesPair The pair of currencies for which
	* we want to get an exchange rate in "USD_UAH" or "usd_uah" format.
	*
	* @throws Exception if we provided an invalid currency pair
	* or if we did not managed to get an exchange rate from the
	* external API and the local cache is empty at the same time
	* for the provided pair of currencies.
	*
	* @return bool|float
	*/
	public function getRateByPair($currenciesPair)
	{
		$currenciesPair = strtoupper($currenciesPair);
		if(!$this->_isValidCurrenciesPair($currenciesPair)){
			throw new Exception('Not a valid currency pair provided!');
		}
		/**
		* For testing purposes we may want to expire the cache:
		*/
		if(strstr($this->_requestId, '_make_the_cache_expired')){
			$this->_makeTheCacheExpired($currenciesPair);
		}
		//At first let's look at the cache:
		$rate = $this->_getRateFromCache($currenciesPair);
		/**
		* If nothing is in the cache or the cache is expired,
		* we have to make a request to the external API:
		*/
		if($rate === false){
			$this->_logActionMessage($this->_requestId,
				$currenciesPair . ' request to external API sending...', 'request');
			$rate = $this->_getRateFromExternalApi($currenciesPair);
			/**
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

	/**
	* Writes a message to a log(about sending request to the external API,
	* storing response in the local cache, retrieving the rate from the local cache, etc).
	*
	* @param string $requestId The unique ID of the current request to this class.
	* It is generated automatically in the __construct() on each class instantiation
	* or may be set from outside of the class if we need to do so(mainly for testing purposes).
	*
	* @param string $message The message that will be written to the log file.
	*
	* @param string $messageType A type of the message(can be 'request', 'response', etc). This type is
	* different for different type of actions like: request to the external API, storing received rate
	* in the local cache, etc. It's like a short form of description for the action being logged.
	*
	* @throws Exception if we try to add a duplicate actionId into
	* the log(the actionId has to be unique). Actually this is almost
	* unreal situation because the actionId consists of current time concatenated
	* with a random integer number and the messageType.
	*
	* @return void
	*/
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

	/**
	* Makes invalid a cached rate for the specified
	* pair of currencies. We need it for testing purposes.
	*
	* @param string $currenciesPair The pair of currencies for which we
	* need to make cache invalid.
	*
	* @return void
	*/
	protected function _makeTheCacheExpired($currenciesPair)
	{
		$cachedDataArray = json_decode(file_get_contents($this->_cacheFileName), true);
		if(!is_array($cachedDataArray)){
			$cachedDataArray = [];
		}
		//Let's invalidate cache for the specified pair of currencies:
		$cachedDataArray[$currenciesPair]['time'] = $cachedDataArray[$currenciesPair]['time'] - 100000;
		file_put_contents($this->_cacheFileName, json_encode($cachedDataArray));
	}

	/**
	* Stores the rate retrieved from the external API into the local cache.
	*
	* @param string $currenciesPair The pair of currencies(like "USD_UAH") for which
	* we need to store the rate in our local cache.
	*
	* @param float $rate The exchange rate for the specified pair of currencies.
	*
	* @return void
	*/
	protected function _putReceivedResponseToCache($currenciesPair, $rate)
	{
		$cachedDataArray = json_decode(file_get_contents($this->_cacheFileName), true);
		if(!is_array($cachedDataArray)){
			$cachedDataArray = [];
		}
		$cachedDataArray[$currenciesPair]['time'] = time();
		$cachedDataArray[$currenciesPair]['rate'] = $rate;
		file_put_contents($this->_cacheFileName, json_encode($cachedDataArray));
	}

	/**
	* Get the exchange rate(for the specified pair of currencies)
	* stored in the local cache.
	*
	* @param string $currenciesPair The pair of currencies(like "USD_UAH")
	* for which to retrieve the stored rate.
	*
	* @return bool|float
	*/
	protected function _getRateFromCache($currenciesPair)
	{
		$cachedDataArray = json_decode(file_get_contents($this->_cacheFileName), true);
		if(!is_array($cachedDataArray)){
			return false;
		}
		if(!array_key_exists($currenciesPair, $cachedDataArray)){
			return false;
		}
		$timeOfTheRecord = $cachedDataArray[$currenciesPair]['time'];
		if(time() - $timeOfTheRecord > $this->_timeToStoreInCache){
			return false;
		}
		return $cachedDataArray[$currenciesPair]['rate'];
	}

	/**
	* Checks if provided pair of currencies(like "USD_UAH", "usd_uah")
	* is valid(the string of the pair is formed properly and
	* each currency code belongs to the allowed currency codes).
	*
	* @param string $currenciesPair The pair of currencies(like "USD_UAH", "usd_uah")
	* to validate.
	*
	* @return bool
	*/
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

	/**
	* Retrieves the rate for the specified pair of currencies from
	* the external API.
	*
	* @param string $currenciesPair The pair of currencies(like "USD_UAH", "usd_uah")
	* to retrieve an exchange rate for.
	*
	* @return bool|float
	*/
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
	/**
	* Take the pair of currencies from the GET request and
	* make it upper case.
	*/
	$currenciesPair = strtoupper($_GET['q']);
	if(empty(trim($currenciesPair))){
		throw new Exception('Empty currencies pair provided!');
	}
	/**
	* Init the class for retrieving exchange rates with the provided
	* pair of currencies.
	*/
	$currencyRate = new CurrencyRate($currenciesPair);
	/**
	* If "test_id" GET-parameter is present we need to
	* set current requestId manually with the specified "test_id"
	* to be able to track all the processes accordingly to testing
	* purposes.
	*/
	if(isset($_GET['test_id'])) $currencyRate->setRequestId($_GET['test_id']);
	/**
	* Now let's retrieve the current exchange rate for the specified
	* pair of currencies. The rate may be taken either from the local
	* cache(if we asked it from the external API a specific period of time ago)
	* or by asking the external API if the local cache is invalid.
	*/
	$rate = $currencyRate->getRateByPair($currenciesPair);
	/**
	* Now let's just send a response.
	*/
	echo json_encode(array("$currenciesPair" => array("val" => $rate)));
} catch(Exception $e){
	/**
	* Or let's send a response containing an error message if
	* something goes wrong.
	*/
	echo json_encode(['errorMessage' => $e->getMessage()]);
}