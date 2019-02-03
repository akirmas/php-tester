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

    // tests
    public function testGetRateForUSD_USD()
    {
        $expectedRate = 1;
        $receivedRate = $this->_sendCurlRequestToApi('usd_usd')['rate'];
        $this->assertEquals($expectedRate, $receivedRate, 'Not expected rate received for USD to USD request.'); 
    }

    public function testGetRateNotEqualsToOne()
    {
        $receivedRate = $this->_sendCurlRequestToApi('usd_uah')['rate'];
        $this->assertNotEquals(1, $receivedRate, 'Not expected rate for USD to UAH request.');
    }

    public function testEmptyCurrenciesPair()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('');
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertEquals($receivedErrorMessage, 'Empty currencies pair provided!');
    }

    public function testNotValidCurrenciesPairWithNotValidCurrencyCodes()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('aaa_aaa');
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    public function testNotValidCurrenciesPairWithNotValidDelimiter()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('aaa%zzz');
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    public function testNotValidCurrenciesPairWithEmptyCurrencyTo()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('usd_');
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }

    public function testNotValidCurrenciesPairWithEmptyCurrencyFrom()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('_usd');
        $receivedRate = $receivedResponse['rate'];
        $receivedErrorMessage = $receivedResponse['errorMessage'];
        $this->assertFalse($receivedRate);
        $this->assertEquals($receivedErrorMessage, 'Not a valid currency pair provided!');
    }


    public function testRateReceivedIsNumeric()
    {
        $receivedResponse = $this->_sendCurlRequestToApi('usd_uah');
        $receivedRate = $receivedResponse['rate'];
        if(!is_numeric($receivedRate)){
            $this->_logFailedTestMessage("Rate is not numeric:\n" . json_encode($receivedRate));
            $this->fail('Rate is not numeric!');
        }
    }

    public function testRateWhenRequestToExternalApiIsPerformedAndCacheIsUsed()
    {
        $testId = time() . '_' . mt_rand(1000, 20000);
        $receivedResponse = $this->_sendCurlRequestToApi('usd_uah', $testId);
        $receivedRate = $receivedResponse['rate'];
        if(!is_numeric($receivedRate) && !isset($receivedResponse['errorMessage'])){
            $this->fail('Rate is not numeric!');
        }
        $loggedMessageRequest = $this->_getLoggedMessageByTestId($testId, 'request');
        $loggedMessageResponse = $this->_getLoggedMessageByTestId($testId, 'response');
        $loggedMessageIsCached = $this->_getLoggedMessageByTestId($testId, 'rate_is_cached');

        $this->assertEquals($loggedMessageRequest, 'USD_UAH request to external API sending...');
        $this->assertEquals($loggedMessageResponse, 'USD_UAH response from external API received.');
        $this->assertEquals($loggedMessageIsCached, 'USD_UAH response from external API is saved to the local cache.',
            'Rate has not been put into the cache!');
        $this->assertTrue($this->_checkResponseFromTheCacheByTestIdForUSD_UAH($testId, $receivedRate, 'Did not manage to receive the rate from the cache!'));
    }

    /*
    * The method checks if the previously requested rate present in the cache.
    */
    private function _checkResponseFromTheCacheByTestIdForUSD_UAH($testId, $receivedRate)
    {
        $responseFromTheCache = $this->_sendCurlRequestToApi('usd_uah', $testId);
        if($responseFromTheCache['rate'] === $receivedRate){
            if($this->_getLoggedMessageByTestId($testId, 'response_received_from_cache') === 'USD_UAH response received from the local cache.'){
                    return true;
            }
        }
        return false;
    }

    private function _getLoggedMessageByTestId($testId, $messageType)
    {
        $testId = $testId . '_' . $messageType;
        $logFileName = $this->_currencyConverterDir . '/logged_test_messages.log';
        $messagesArray = json_decode(file_get_contents($logFileName), true);
        if(array_key_exists($testId, $messagesArray)){
            return $messagesArray[$testId];
        }
        return false;
    }

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
            /*
            $fp = fopen('some_log.txt', 'a');
            fwrite($fp, "Single resp:");
            fwrite($fp, $resp);
            fwrite($fp, "\n\n");
            fwrite($fp, "==============");
            fwrite($fp, "\n\n");
            fclose($fp);
            */
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

    private function _logFailedTestMessage($message)
    {
        $logName = 'failed_tests_logs/failed_test_for_currency_converter.log';
        file_put_contents($logName, $message);
        /*
        $fp = fopen($logName, 'a');
        fwrite($fp, date() . ":" . $message . "\n");
        fclose($fp);
        */
    }

}