<?php 
class CurrencyConverterAPITest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $_currencyConverterDir = 'currency_converter';
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
    * This method clears the log and the local cache.
    * We need to clear the log and the local cache because test methods
    * are written as a logical consequence of checks what has been
    * written to the logs and what has been stored in the local cache.
    * And each next method assumes and checks that there is specific
    * data present in the logs and the local cache.
    * Please note that we have to run this method first!
    * TODO: Find more elegant way to clear these files
    * before running the tests.
    */
    public function testClearTheOldCacheAndLogBeforeTestingStarts()
    {
        $fp1 = fopen($this->_currencyConverterDir . '/logged_test_messages.log', 'w');
        fclose($fp1);
        $fp2 = fopen($this->_currencyConverterDir . '/rates_cache.txt', 'w');
        fclose($fp2);
    }

    /**
    * Checks that the rate for USD_USD pair is 1.
    */
    public function testGetRateForUSD_USD()
    {
        $expectedRate = 1;
        $receivedRate = $this->_sendCurlRequestToApi('usd_usd', $this->_generateTestId('usd_usd'))['rate'];
        $this->assertEquals($expectedRate, $receivedRate, 'Not expected rate received for USD to USD request.'); 
    }

    /**
    * Checks that the rate for the pair of currencies which is not
    * "USD_USD" will not be equal to 1.
    */
    public function testGetRateNotEqualsToOne()
    {
        $currenciesPair = 'usd_php';
        $receivedRate = $this->_sendCurlRequestToApi($currenciesPair, $this->_generateTestId($currenciesPair))['rate'];
        $this->assertNotEquals(1, $receivedRate);
    }

    /**
    * Checks that a response for an empty currencies pair will contain the corresponding
    * error message.
    */
    public function testEmptyCurrenciesPair()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('', $this->_generateTestId('usd_usd'));
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertEquals($receivedErrorMessage, 'Empty currencies pair provided!');
    }

    /**
    * Checks that a response for the currencies pair concatenated from not valid currency codes will
    * contain corresponding error message.
    */
    public function testNotValidCurrenciesPairWithNotValidCurrencyCodes()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('aaa_aaa', $this->_generateTestId('usd_usd'));
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    /**
    * Checks that a response for the currencies pair with invalid delimiter will
    * contain corresponding error message.
    */
    public function testNotValidCurrenciesPairWithNotValidDelimiter()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('aaa%zzz', $this->_generateTestId('usd_usd'));
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    /**
    * Checks that a response for the currencies pair with empty currencyTo will
    * contain corresponding error message.
    */
    public function testNotValidCurrenciesPairWithEmptyCurrencyTo()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('usd_', $this->_generateTestId('usd_usd'));
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    /**
    * Checks that a response for the currencies pair with empty currencyFrom will
    * contain corresponding error message.
    */
    public function testNotValidCurrenciesPairWithEmptyCurrencyFrom()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('_usd', $this->_generateTestId('usd_usd'));
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }


    /**
    * Checks that if we make a first request for some currencies pair the workflow will
    * be the following:
    * 1)A request to the external API will be issues.
    * 2)A response from the external API will be received and stored in the local cache.
    */
    public function testRateIsNumericAndReceivedFromTheExternalApiFirstThenIsStoredInTheCache()
    {
        $currenciesPair = 'USD_UAH';
        $testId = $this->_generateTestId($currenciesPair);
        $receivedResponse = $this->_sendCurlRequestToApi($currenciesPair, $testId);
        $receivedRate = $receivedResponse['rate'];
        if(!is_numeric($receivedRate)){
            $this->_logFailedTestMessage("Rate is not numeric:\n" . json_encode($receivedRate));
            $this->fail('Rate is not numeric!');
        }
        $loggedMessageRequest = $this->_getLoggedMessageByTestId($testId, 'request');
        $loggedMessageResponse = $this->_getLoggedMessageByTestId($testId, 'response');
        $loggedMessageIsCached = $this->_getLoggedMessageByTestId($testId, 'rate_is_cached');

        $this->assertEquals($loggedMessageRequest, $currenciesPair . ' request to external API sending...');
        $this->assertEquals($loggedMessageResponse, $currenciesPair . ' response from external API received.');
        $this->assertEquals($loggedMessageIsCached, $currenciesPair . ' response from external API is saved to the local cache.');
    }


    /**
    * Checks that if we make a subsequent request for same currencies pair the workflow will
    * be the following:
    * 1)The rate will be taken from the local cache(no requests will be performed to the external API).
    */
    public function testUSD_UAHReceivedFromTheLocalCache()
    {
        $testId = $this->_generateTestId('usd_uah');
        $receivedResponse = $this->_sendCurlRequestToApi('usd_uah', $testId);
        $receivedRate = $receivedResponse['rate'];
        if(!is_numeric($receivedRate) && !isset($receivedResponse['errorMessage'])){
            $this->fail('Rate is not numeric!');
        }
        $loggedMessageRequest = $this->_getLoggedMessageByTestId($testId, 'request');
        $loggedMessageResponse = $this->_getLoggedMessageByTestId($testId, 'response');
        $loggedMessageIsCached = $this->_getLoggedMessageByTestId($testId, 'rate_is_cached');
        $loggedMessageIsReceivedFromCache = $this->_getLoggedMessageByTestId($testId, 'response_received_from_cache');

        $this->assertFalse($loggedMessageRequest);
        $this->assertFalse($loggedMessageResponse);
        $this->assertFalse($loggedMessageIsCached);
        $this->assertEquals($loggedMessageIsReceivedFromCache, 'USD_UAH response received from the local cache.');
    }

    /**
    * Checks that if we manually invalidate the local cache for some particular currencies pair the workflow will
    * be the following:
    * 1)A request to the external API will be issues.
    * 2)The received response will be stored in the local cache.
    */
    public function testCheckThatRateIsTakenFromExternalApiWhenCacheIsExpiredForUSD_UAH()
    {
        $testId = $this->_generateTestId('usd_uah');
        $testId .= '_make_the_cache_expired';
        $receivedResponse = $this->_sendCurlRequestToApi('usd_uah', $testId);
        $receivedRate = $receivedResponse['rate'];
        $loggedMessageRequest = $this->_getLoggedMessageByTestId($testId, 'request');
        $loggedMessageResponse = $this->_getLoggedMessageByTestId($testId, 'response');
        $loggedMessageIsCached = $this->_getLoggedMessageByTestId($testId, 'rate_is_cached');
        $this->assertEquals($loggedMessageRequest, 'USD_UAH request to external API sending...');
        $this->assertEquals($loggedMessageResponse, 'USD_UAH response from external API received.');
        $this->assertEquals($loggedMessageIsCached, 'USD_UAH response from external API is saved to the local cache.',
            'Rate has not been put into the cache!');
    }

    /**
    * Checks whether we will receive the rate if we do not
    * provide a testId for our currency converter API.
    */
    public function testCheckThatWeReceiveTheRateIfWeDoNotProvideATestId()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('usd_uah');
        $receivedRate = $receivedResponse['rate'];
        if(!is_numeric($receivedRate) || isset($receivedResponse['errorMessage'])){
            $this->fail('Did not manage to get the rate when testId is not supplied!');
        }
    }

    /**
    * Generated a unique testId to be able to track some
    * particular testing workflow.
    *
    * @param string $currenciesPair The unique testId will be generated using the specified
    * currencies pair.
    *
    * @return string Returns the generated testId.
    */
    private function _generateTestId($currenciesPair)
    {
        $currenciesPair = strtoupper($currenciesPair);
        return 'testing_' . $currenciesPair . '_' . time() . '_' . mt_rand(1000, 20000);
    }

    /**
    * Returns logged message which tells us all needed information
    * about some particular action(sending a request to the external API,
    * getting a rate from the local cache, etc).
    *
    * @param string $testId The unique testId we need to find specific information
    * inside the log.
    *
    * @param string $messageType The type of a message in the log. This parameter is used
    * together with testId to identify events in the log in a more convenient way.
    *
    * @return bool|string Returns a message(describing particular event like sending
    * a request to the external API, etc) from the log or FALSE if we did not manage
    * to find any message.
    */
    private function _getLoggedMessageByTestId($testId, $messageType)
    {
        $logFileName = $this->_currencyConverterDir . '/logged_test_messages.log';
        $messagesArray = json_decode(file_get_contents($logFileName), true);
        $fullTestId = $testId . '_' . $messageType;
        if(array_key_exists($fullTestId, $messagesArray)) return $messagesArray[$fullTestId];
        return false;
    }

    /**
    * Sends cURL request to our currency converter API.
    *
    * @param string $currenciesPair The pair of currencies for which we want
    * to get an exchange rate.
    *
    * @param string $testId The unique ID of the test to be able to track all
    * the corresponding workflow inside the log.
    *
    * @return array Returns an array with the rate retrieved. Also can contain
    * error message if something goes wrong.
    */
    private function _sendCurlRequestToApi($currenciesPair, $testId = null)
    {
        $this->_apiRequestCounterIncrement();
        $currenciesPair = strtoupper($currenciesPair);
        $url = "http://psps/$this->_currencyConverterDir/currency_rates.php?q=$currenciesPair&compact=y";
        if(!is_null($testId)){
            $url .= "&test_id=$testId";
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        $resp = curl_exec($ch);
        if ($resp !== false && !is_null($resp)){
            $resp = json_decode($resp, true);
            if(array_key_exists('errorMessage', $resp)){
                return ['rate' => false, 'errorMessage' => $resp['errorMessage']];
            }
            if (array_key_exists($currenciesPair, $resp) && array_key_exists('val', $resp[$currenciesPair])) {
                return ['rate' => $resp[$currenciesPair]['val']];
            }
        }
        return ['rate' => false];
    }

    /**
    * Increments a counter on each request to our currency converted
    * API.
    */
    private function _apiRequestCounterIncrement()
    {
        $counterFileName = $this->_currencyConverterDir . '/api_request_counter.txt';
        if(!file_exists($counterFileName)){
            $fp = fopen($counterFileName, 'w');
            fwrite($fp, 0);
            fclose($fp);
            return $this->_apiRequestCounterIncrement();
        }
        $current = file_get_contents($counterFileName);
        file_put_contents($counterFileName, ++$current);
    }

    /**
    * Writes messages to a log.
    */
    private function _logFailedTestMessage($message)
    {
        $logName = 'failed_tests_logs/failed_test_for_currency_converter.log';
        $fp = fopen($logName, 'a');
        fwrite($fp, date("D M j G:i:s T Y", time()) . ":" . $message . "\n");
        fclose($fp);
    }

}