<?php
$failedProject = false;
$report = [];
$opts = getopt('', ['url:', 'script:', 'all']);
$opts['all'] = array_key_exists('all', $opts);
$opts['script'] = array_key_exists('script', $opts) ? $opts['script'] : 'index';
$opts['url'] = array_key_exists('url', $opts) ? $opts['url'] : null;

$scriptPaths = [$opts['script']];
forEach($scriptPaths as $scriptPath) {
  $testPath = preg_replace('/\.php$/i', '', $scriptPath).'.test.json';
  $tests = json_decode(file_get_contents($testPath), true);
  $failedScript = false;
  $testNames = array_keys($tests);
  $report[$scriptPath] = array_map(
    function($name) use ($scriptPath, $tests, &$failedScript, $opts) {
      //TODO: set up 'style' of test - CLI, HTTP/GET, HTTP/POST

      $responseText = is_null($opts['url'])
      ? callTest($scriptPath, $tests[$name][0])[0]
      : file_get_contents($opts['url']."$scriptPath.php?".http_build_query($tests[$name][0]));

      $response = json_decode($responseText, true);
      if (is_null($response))
        $response = $responseText;

      $expected = $tests[$name][1];
      $failedTest = !isSubset($response, $expected);
      $failedScript = $failedScript || $failedTest;
      $output = [$name =>
        !$failedTest
        ? true
        : array(
          "response" => $response,
          "expected" => $expected
        )
      ];
      if (!$opts['all'] && $failedTest)
        exiting($failedTest, $output);
      return $output;
    },
    $testNames
  );
  $failedProject = $failedProject || $failedScript;
}

exiting($failedProject, $report);

function exiting($failed, $report = []) {
  if ($failed)
    exit("1\n".json_encode($report, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES)."\n");
  echo 0;
  exit;
}

function callTest($php, $params) {
  $params = '"'.preg_replace('/["]/', '\\\\$0', json_encode($params)).'"';
  //$params = escapeshellarg($params);
  $php = preg_replace('/\.php/', '', $php).'.php';
  $output = null;
  exec("php $php $params", $output);
  return $output;
}

function isSubset($set, $sub) {
  return is_array($set) && is_array($sub)
  ? $sub === array_intersect_assoc($sub, $set)
  : $set === $sub;
}