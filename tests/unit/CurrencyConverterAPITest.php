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
        $receivedRate = $this->_sendCurlRequestToApi('usd_usd');
        $this->assertEquals($expectedRate, $receivedRate, 'Not expected rate received for USD to USD request.'); 
    }

    public function testGetRateNotEqualsToOne()
    {
        $receivedRate = $this->_sendCurlRequestToApi('usd_uah');
        $this->assertNotEquals(1, $receivedRate, 'Not expected rate for USD to UAH request.');
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
            if ($resp !== null && array_key_exists($currenciesPair, $resp) && array_key_exists('val', $resp[$currenciesPair])) {
                $rate = $resp[$currenciesPair]['val'];
                return $rate;
            }
        } else return false;
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