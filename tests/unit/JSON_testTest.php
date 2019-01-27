<?php 
class JSON_testTest extends \Codeception\Test\Unit
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
    public function testIndexForGoodAndBadRequests()
    {

        $testPath = '/local_web/psps/index.test.json';
        $tests = json_decode(file_get_contents($testPath), true);
        $names = array_keys($tests);
        //$names = ['isra_frame_good', 'isra_frame_bad', 'tranz_instant', 'netpay'];
        foreach ($names as $name) {
            $response = json_decode(file_get_contents("http://psps/index.php/?".http_build_query($tests[$name][0])), true);
            $expected = $tests[$name][1];
            $this->assertEquals(array_intersect_assoc($response, $expected), $expected);        
        }
        
    }
}