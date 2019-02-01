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

}