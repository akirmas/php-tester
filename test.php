<?php
$failedProject = false;
$report = [];
$scriptPaths = sizeof($_SERVER['argv']) > 1
? [$_SERVER['argv'][1]]
: json_decode(file_get_contents(preg_replace('/\.php/i', '.json', __FILE__)), true);
forEach($scriptPaths as $scriptPath) {
  $testPath = __DIR__.'/'.preg_replace('/\.php$/i', '', $scriptPath).'.test.json';
  $tests = json_decode(file_get_contents($testPath), true);
  $failedScript = false;
  $testNames = sizeof($_SERVER['argv']) > 2
  ? [$_SERVER['argv'][2]]
  : array_keys($tests);
  $report[$scriptPath] = array_map(
    function($name) use ($scriptPath, $tests, &$failedScript) {
      //TODO: set up 'style' of test - CLI, HTTP/GET, HTTP/POST
      //$response = json_decode(callTest($scriptPath, $tests[$name][0])[0], true);
      $response = json_decode(file_get_contents("http://localhost/psps/$scriptPath.php?".http_build_query($tests[$name][0])), true);
      //$response = json_decode(file_get_contents("https://payment.gobemark.info/apis/master/$scriptPath.php?".http_build_query($tests[$name][0])), true);
      $expected = $tests[$name][1];
      $failedTest = $expected != array_intersect_assoc($response, $expected);
      $failedScript = $failedScript || $failedTest;
      return array($name =>
        !$failedTest ? true
        : array(
          "response" => $response,
          "expected" => $expected
        )
      );
    },
    $testNames
  );
  $failedProject = $failedProject || $failedScript;
}

if ($failedProject)
  exit("0\n".json_encode($report, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES)."\n");
else echo 0;
exit;

function callTest($php, $params) {
  $params = preg_replace('/(")/', '\\\\$0', json_encode($params));
  $php = preg_replace('/\.php/', '', $php).'.php';
  $output;
  exec("php $php ".escapeshellarg($params), $output);
  return $output;
}
