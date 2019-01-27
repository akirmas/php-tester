<?php 
class IndexJsonTest extends \Codeception\Test\Unit
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

        $testPath = 'index.test.json';
        $tests = json_decode(file_get_contents($testPath), true);
        $names = array_keys($tests);
        foreach ($names as $name) {
            $response = json_decode(file_get_contents("http://psps/index.php/?".http_build_query($tests[$name][0])), true);
            $expected = $tests[$name][1];
            $this->assertEquals(array_intersect_assoc($response, $expected), $expected);        
        }
        
    }
}