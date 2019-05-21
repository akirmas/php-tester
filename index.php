<?php
$failedProject = false;
$report = [];
$opts = getopt('', ['url:', 'script:', 'name:', 'run-all']);
$opts['run-all'] = array_key_exists('run-all', $opts);
$opts['script'] = array_key_exists('script', $opts) ? $opts['script'] : 'index';
$opts['url'] = array_key_exists('url', $opts) ? $opts['url'] : null;
$opts['name'] = array_key_exists('name', $opts) ? $opts['name'] : null;

$scriptPaths = [$opts['script']];
forEach($scriptPaths as $scriptPath) {
  $testPath = preg_replace('/\.php$/i', '', $scriptPath).'.test.json';
  $scriptPath = preg_replace('/\.php/', '', $scriptPath).'.php';
  if (!file_exists($testPath))
    exit("Test '$testPath' not exists");
  if (!file_exists($scriptPath))
    exit("Script '$scriptPath' not exists");

  $tests = json_decode(file_get_contents($testPath), true);
  $failedScript = false;
  $testNames = is_null($opts['name']) ? array_keys($tests) : [$opts['name']];
  $report[$scriptPath] = array_map(
    function($name) use ($scriptPath, $tests, &$failedScript, $opts) {
      $haveParams = array_key_exists('in', $tests[$name]);
      $params = @$tests[$name]['in'];

      if (!empty($tests[$name]['fn'])) {
        require_once($scriptPath);
        $fn = $tests[$name]['fn'];
        $response = !$haveParams
        ? call_user_func($fn)
        : call_user_func_array($fn,
          is_array($params)
          ? $params
          : [$params]
        );
      } else {
        $responseText = is_null($opts['url'])
        ? callTest($scriptPath, $params)[0]
        // TODO: HTTP/POST
        : curlGet($opts['url']."$scriptPath.php",  $params);
        $response = json_decode($responseText, true);
      }

      if (is_null($response))
        $response = $responseText;

      $expected = $tests[$name]['out'];
      $failedTest = !call_user_func($tests[$name]['assert'], $response, $expected);
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

function callTest($module, $params) {
  $params = empty($params)
  ? ''
  : '"'.preg_replace('/["]/', '\\\\$0', json_encode($params)).'"';
  //$params = escapeshellarg($params);
  $output = null;
  exec("php $module $params", $output);
  return $output;
}

function curlGet($url, $options = null) {
  $ch = curl_init($url
    .(
      empty($options)
      ? ''
      : '?'.http_build_query($options)
    )
  );
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true
  ]);
  $response = curl_exec($ch);
  if ($response === false)
    $response = curl_error($ch);
  curl_close($ch);
  return $response;
}

function equalStrict($a, $b) {
  return $a === $b;
}