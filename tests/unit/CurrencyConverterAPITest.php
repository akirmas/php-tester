<?php 
class CurrencyConverterAPITest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
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

    private function _sendCurlRequestToApi($currenciesPair)
    {
        $this->_apiRequestCounterIncrement();
        $currenciesPair = strtoupper($currenciesPair);
        $ch = curl_init(
            "http://psps/currency_rates.php?q=$currenciesPair&compact=y"
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        $resp = curl_exec($ch);
        if ($resp !== false) {
            $resp = json_decode($resp, true);
            if(array_key_exists('errorMessage', $resp)){
                return ['rate' => false, 'errorMessage' => $resp['errorMessage']];
            }
            if ($resp !== null && array_key_exists($currenciesPair, $resp) && array_key_exists('val', $resp[$currenciesPair])) {
                return ['rate' => $resp[$currenciesPair]['val']];
            }
        }
        return ['rate' => false];
    }

    private function _apiRequestCounterIncrement()
    {
        $counterFileName = 'api_request_counter.txt';
        if(!file_exists($counterFileName)){
            $fp = fopen($counterFileName, 'w');
            fwrite($fp, 0);
            fclose($fp);
            return $this->_apiRequestCounterIncrement();
        }
        $current = file_get_contents($counterFileName);
        file_put_contents($counterFileName, ++$current);
    }

}