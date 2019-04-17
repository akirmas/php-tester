<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: origin, x-requested-with, Content-Type, Date, Request-Date');

require_once(__DIR__.'/utils/assoc.php');
require_once(__DIR__.'/handler.php');

$commonHandler = 'CommonHandler';

$system = [
  'tmstmp' => tmstmp(),
  'scriptPath' => \assoc\getValue($_SERVER, 'SCRIPT_FILENAME', ''),
  'referer:ip' => getClientIp(),
  'referer:port' => \assoc\getValue($_SERVER, 'SERVER_PORT', ''),
  'referer:method' => \assoc\getValue($_SERVER, 'REQUEST_METHOD', 'CLI'),
  'referer:host' => \assoc\getValue($_SERVER, 'HTTP_HOST', ''),
  'referer:url' => \assoc\getValue($_SERVER, 'HTTP_REFERER', '')
];

if ($system['referer:method'] === 'OPTIONS')
  closeAndExit();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input))
  $input = [];
$input = (
  count($_REQUEST) === 0 ? [] : $_REQUEST
) + (
  \assoc\keyExists($_SERVER, 'argv') && count($_SERVER['argv']) > 1
  ? (json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]), true))
  : []
) + $input;
//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'), true)['redsys_dev'][0];

//NB! HARDCODE
if (\assoc\keyExists($input, 'cc:number'))
  $input['cc:number'] = preg_replace('/[^0-9]+/', '', $input['cc:number']);

$processPath = preg_replace('%(^/+|/+$)%', '',
  \assoc\getValue($_SERVER, 'PATH_INFO',
    \assoc\join2('/',
     \assoc\getValues($input, ['account', 'process'])
    )
  )
);
if (empty($processPath)) {
  echo 1;
  exit;
}
// The only field to be hardcoded - key 'account' will be used as it in 3rd parties, avoid ambiguity
unset($input['account']);
$system['_account'] = $processPath;
$system['process'] = $processPath;

$input = $system + $input;

$event = 'Request';
$phase = 'Raw';

if (!\assoc\keyExists($input, 'id')) $input['id'] = $input['tmstmp'];

$processDir = mkdir2(__DIR__, '..', 'processes', $processPath, $input['id']);
$logDir = mkdir2($processDir, $input['tmstmp']);
$processDir = mkDir2($processDir, 'index');

$ConfigDir = __DIR__.'/../configs';
$steps = json_decode(file_get_contents(join('/', [
  $ConfigDir, 'processes', $processPath.'.json'
])), true);
$strategy = \assoc\getValue(\assoc\keys($steps), 0, "anyOf");
$steps = \assoc\getValue($steps, $strategy, [$steps]);
$output = [];
$filled = [];
$requestData = [];
forEach($steps as $step) {
  $processor = is_array($step)
  ? join('/', [$step['instance'], 'accounts', $step['account']])
  : $step;
  $handler = \assoc\getValue($step, 'instance', explode('/', $processor)[0]);
    
  //$handlerPath = $ConfigDir."/instances/$handler/handler.php";
  $handlerPath2 = __DIR__."/instances/$handler/handler.php";
  /*if (file_exists($handlerPath))
    require_once($handlerPath);
  else*/if (file_exists($handlerPath2))
    require_once($handlerPath2);
  else {
    $handler = 'CycleHandler';
    require_once(__DIR__."/$handler.php");
  }

  $instance = \assoc\mergeJsonPaths("{$ConfigDir}/instances", $processor);

  $request = \assoc\getValue($instance, 'request', []);
  $response = \assoc\getValue($instance, 'response', []);
  
  $filled = \assoc\merge(
    \assoc\getValue($request, 'defaults', []),
    $input,
    \assoc\getValue($request, 'overrides', [])
  );

  $event = 'Request';
  $phase = 'Filled';
  
  
  $filled = fireEvent(
    (\assoc\getValue($request, ['engine', 'history'], false))
    ? \assoc\merge($requestData, $output, $filled)
    : $filled
  );
  
  $filled = fillValues(
    $filled,
    $filled
  );

  $event = 'Request';
  $phase = 'Calced';

  //Keep unified vocabulary in schema keys
  $requestData = (\assoc\getValue($request, ['engine', 'sourceIsAPI'], false))
  ? \assoc\mapValues(
    \assoc\mapKeys(
      $filled,
      \assoc\flip(\assoc\getValue($request, 'fields', [])),
      false
    ),
    \assoc\getValue($request, 'values', []),
    true
  ) : \assoc\mapKeys(
    \assoc\mapValues(
      $filled,
      \assoc\getValue($request, 'values', []),
      true
    ),
    \assoc\getValue($request, 'fields', []),
    false
  );

  $event = 'Request';
  $phase = 'Formed';

  $request['engine']['gateway'] = formatString(formatString(
    \assoc\getValue($request, ['engine', 'gateway'], ''),
    $requestData), $filled
  );

  $cachePath = '';
  if (\assoc\getValue($request, ['engine', 'cache'], false)) {
    $cacheDir = mkdir2(__DIR__, '..', 'cached', $processPath);
    $cachePath = "{$cacheDir}/"
    .hash("md5",
      json_encode(\assoc\getValue($request, ['engine'], []))
      .json_encode($requestData)
    );
    if (file_exists($cachePath))
      $request['engine']['method'] = 'useCached';
  }
  switch(\assoc\getValue($request, ['engine', 'method'], '')) {
    case 'PATCH':
    case 'POST':
      $ch = curl_init(\assoc\getValue($request, ['engine', 'gateway'], ''));
      curl_setopt_array($ch,
        [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FRESH_CONNECT => true,
          CURLOPT_CUSTOMREQUEST => \assoc\getValue($request, ['engine', 'method'], ''),
          CURLOPT_HEADER => 1,
          //CURLOPT_VERBOSE => 1,
          CURLOPT_POSTFIELDS => json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
          CURLOPT_HTTPHEADER => array_merge(
            [
              'Request-Date: '. gmdate('D, d M Y H:i:s T'),
              'Date: '. gmdate('D, d M Y H:i:s T')
            ],
            \assoc\getValue($request, ['engine', 'headers'], [])
          )
        ]
      );
      $responseText = curl_exec($ch);
      if ($responseText === false) 
        throw new Exception(curl_error($ch), curl_errno($ch));
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      curl_close($ch);
      
      //Despite declared content-type - APIs love to return .html pages on errors
      $htmlResp = '<!doctype html>';
      if ($htmlResp === strtolower(substr($responseText, 0, strlen($htmlResp))))
        file_put_contents("$processDir/error.html", $responseText);

      $header = substr($responseText, 0, $header_size);
      $responseText = substr($responseText, $header_size);
      break;
    case 'GET':
      $responseText = file_get_contents(
        \assoc\getValue($request, ['engine', 'gateway'], '')
        .'?'
        .http_build_query($requestData)
      );      
      break;
    case 'useCached':
      $responseText = file_get_contents($cachePath);      
      break;
    case 'no_curl':
      $response['engine']['contentType'] = $request['engine']['method'];
      break;
    default: {
      http_response_code(501);
      closeAndExit('not impelemented');
    }
  }

  if ($cachePath !== '' && \assoc\getValue($request, ['engine', 'method'], '') !== 'useCached')
    file_put_contents($cachePath, $responseText);
  
  switch($response['engine']['contentType']) {
    case 'application/x-www-form-urlencoded':
      parse_str(
        $responseText,
        $responseData
      );
      break;
    case 'no_curl':
      $responseData = $requestData;
      break;
    case 'application/json':
      $responseData = json_decode($responseText, true);
      if ($responseData !== null && \assoc\isESObject($responseData)) 
        break;
    case 'text/plain':
    default:
      $responseData = ['response' => $responseText];
  }

  $event = 'Response';
  $phase = 'Raw';
  
  $output = \assoc\merge(
    \assoc\getValue($response, 'defaults', []),
    $responseData,
    \assoc\getValue($response, 'overrides', [])
  );

  $output = fireEvent($output, $output);

  $output = \assoc\mapValues(
    \assoc\mapKeys(
      $output,    
      \assoc\flip(
        \assoc\getValue($response, 'fields', [])
      ),
      false
    ),
    \assoc\getValue($response, 'values', []),
    true
  );

  $output = $system
  + ['processor' => $processor]
  + $output;
  $output = fillValues(
    $output,
    \assoc\merge($output, $filled)
  );

  $event = 'Response';
  $phase = 'Formed';
  $output = fireEvent($output, $filled);
  if (
    // until first TRUE
    in_array($strategy, ['anyOf', 'oneOf'])
    && \assoc\getValue($output, 'success') === 1
    // until first FALSE
    || $strategy === 'allOf'
    && \assoc\getValue($output, 'success') === 0
    // 'just do it' is 'anyOf' - and any value of $strategy (even 'Nike')
  )
    break;
}

$responseContentType = \assoc\getValue($response, ['output', 'contentType'], 'application/json');
header("Content-Type: {$responseContentType}; charset=utf-8");

switch($responseContentType) {
  case 'text/html': 
    echo \assoc\getValue($output, \assoc\getValue($response, ['output', 'contentFrom']));
    break;
  case 'application/json': 
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    break;
  default:
}


function fireEvent(...$data) {
  global $event, $phase, $handler, $logDir, $processDir, $commonHandler, $step;
  $data[0] = \assoc\merge(
    call_user_func(
      ["\\$commonHandler", "on$event$phase"],
      ...array_merge([$step], $data)
    ),
    $data[0]
  );
  $data[0] = \assoc\merge(
    call_user_func(
      ["\\$handler", "on$event$phase"],
      ...array_merge([$step], $data)
    ),
    $data[0]
  );
  $dirs = [$logDir, $processDir];
  $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  foreach ($dirs as $dir) {
    file_put_contents(
      mkdir2($dir, $handler)."/$event$phase.json",
      $dataJson
    );
  }
  if ($event === 'Response' && $phase = 'Formed') {
    file_put_contents(
      "$processDir/$handler/index.json",
      $dataJson
    );
  }
  return $data[0];
}
