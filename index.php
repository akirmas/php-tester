<?php
$failedProject = false;
$report = [];
$opts = getopt('', ['url:', 'script:', 'name:', 'all']);
$opts['run-all'] = array_key_exists('run-all', $opts);
$opts['script'] = array_key_exists('script', $opts) ? $opts['script'] : 'index';
$opts['url'] = array_key_exists('url', $opts) ? $opts['url'] : null;
$opts['name'] = array_key_exists('name', $opts) ? $opts['name'] : null;

$scriptPaths = [$opts['script']];
forEach($scriptPaths as $scriptPath) {
  $testPath = preg_replace('/\.php$/i', '', $scriptPath).'.test.json';
  $tests = json_decode(file_get_contents($testPath), true);
  $failedScript = false;
  $testNames = is_null($opts['name']) ? array_keys($tests) : [$opts['name']];
  $report[$scriptPath] = array_map(
    function($name) use ($scriptPath, $tests, &$failedScript, $opts) {
      //TODO: set up 'style' of test - CLI, HTTP/GET, HTTP/POST

      $responseText = is_null($opts['url'])
      ? callTest($scriptPath, $tests[$name][0])[0]
      : curlGet($opts['url']."$scriptPath.php", $tests[$name][0]);

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
      if (!$opts['run-all'] && $failedTest)
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
    exit("0\n".json_encode($report, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES)."\n");
  echo 1;
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

function curlGet($url, $options = []) {
  $ch = curl_init("{$url}?".http_build_query($options));
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true
  ]);
  $response = curl_exec($ch);
  if ($response === false)
    $response = curl_error($ch);
  curl_close($ch);
  return $response;
}