<?php
require_once(__DIR__.'/import.php');
import('./fetch', __DIR__);

$failedProject = false;
$testPattern = '.test.json';
$report = [];
$opts = getopt('', ['url:', 'script:', 'name:', 'run-all::', 'assert:', 'path:', 'config:']);
$config = !array_key_exists('config', $opts) 
? getcwd() . '/test.config.json'
: $opts['config'];
$opts['run-all'] = array_key_exists('run-all', $opts) && $opts['run-all'];
$opts['script'] = array_key_exists('script', $opts) ? $opts['script'] : null;
$opts['url'] = array_key_exists('url', $opts) ? $opts['url'] : null;
$opts['path'] = array_key_exists('path', $opts) ? $opts['path'] : '.';
$opts['assert'] = array_key_exists('assert', $opts) ? $opts['assert'] : 'assert/index.php';
$opts['name'] = array_key_exists('name', $opts) ? $opts['name'] : null;

$config = !file_exists($config)
? null
: json_decode($config, true);
if (is_null($config))
  $config = [];
$opts += $config;
import($opts['assert'], __DIR__);

$scriptPaths = [];
if (!empty($opts['script']))
  $scriptPaths = [$opts['script']];
else {
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($opts['path']));
  foreach($it as $file) {
    if (substr($file, -strlen($testPattern)) === $testPattern)
      array_push($scriptPaths, substr(
        $file->getPathname(),
        strlen('./'),
        -strlen($testPattern)
      ));
  }
}

foreach($scriptPaths as $scriptPath) {
  $testPath = "{$scriptPath}{$testPattern}";
  $scriptPath = "{$scriptPath}.php";
  if (!file_exists($testPath))
    exit("Test '$testPath' not exists");
  if (!file_exists($scriptPath))
    exit("Script '$scriptPath' not exists");

  $tests = json_decode(file_get_contents($testPath), true);
  unset($tests['$schema']);
  $failedScript = false;
  $testNames = is_null($opts['name']) ? array_keys($tests) : [$opts['name']];
  $report[$scriptPath] = array_map(
    function($name) use ($scriptPath, $tests, &$failedScript, $opts) {
      return runTest($name, $scriptPath, $tests, $failedScript, $opts);
    },
    $testNames
  );
  $failedProject = $failedProject || $failedScript;
}

exiting($failedProject, $report);

function runTest($name, $scriptPath, $tests, &$failedScript, $opts) {
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
    $fetchOpts = !array_key_exists('fetch', $tests[$name])
    ? []
    : $tests[$name]['fetch'];

    $url = @$fetchOpts['url'];
    unset($fetchOpts['url']);
    $url = is_null($url) ? $opts['url'] : $url;
    $responseText = is_null($url)
    ? callTest($scriptPath, $params)[0]
    // TODO: HTTP/POST
    : fetch("{$url}{$scriptPath}", $fetchOpts + [
      'data' => $params
    ])['body'];
    $response = json_decode($responseText, true);
  }

  if (is_null($response))
    $response = $responseText;

  $expected = $tests[$name]['out'];
  $failedTest = !call_user_func('\\asserts\\'.$tests[$name]['assert'], $response, $expected);
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
    exiting($failedTest, [
      $scriptPath => $output
    ]);
  return $output;
}

function exiting($failed, $report = []) {
  if ($failed)
    exit(json_encode($report, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES));
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
