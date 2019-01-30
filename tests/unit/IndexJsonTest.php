<?php 
class IndexJsonTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $_failedTestsLogsDir = 'failed_tests_logs/';
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testIndexTestForGoodAndBadRequests()
    {
        $testPath = 'index.test.json';
        $tests = json_decode(file_get_contents($testPath), true);
        $names = array_keys($tests);
        $totalPassed = true;
        foreach ($names as $name) {
            $response = json_decode(file_get_contents("http://psps/index.php/?".http_build_query($tests[$name][0])), true);
            $expected = $tests[$name][1];
            $this->_testNames[$name]['response'] = $response;
            $this->_testNames[$name]['expected'] = $expected;
            if(array_intersect_assoc($response, $expected) != $expected)
                $isPassed = false;
            else
                $isPassed = true;
            $this->_testNames[$name]['is_passed'] = $isPassed;
            $totalPassed = $totalPassed && $isPassed;
        }
        if($totalPassed)
            $this->assertTrue(true);
        else {
            $this->_failedIndexTestForGoodAndBadRequests();
        }

    }

    protected function _failedIndexTestForGoodAndBadRequests()
    {
        foreach($this->_testNames as $name => $singleTest){
            if($singleTest['is_passed'] === false){
                ob_start();
                echo "\nTest failed for $name:\n";
                echo "\nResponse received:\n";
                echo json_encode($singleTest['response'], JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
                echo "\nWhile expected is:\n";
                echo json_encode($singleTest['expected'], JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES);
                echo "\n";
                file_put_contents($this->_failedTestsLogsDir . 'fails_log_for_index_test_json.log', ob_get_contents());
                ob_end_clean();
            }
        }
        $this->fail("Failed tests present. Look at 'fails_log_for_index_test_json.log'");
    }

}