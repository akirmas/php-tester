<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: origin, x-requested-with, Content-Type, Date, Request-Date');

require_once(__DIR__.'/utils/assoc.php');
$ConfigDir = __DIR__.'/../configs';
require_once($ConfigDir.'/handler.php');

$commonHandler = 'CommonHandler';

$system = [
  'tmstmp' => tmstmp(),
  'scriptPath' => tryGet($_SERVER, 'SCRIPT_FILENAME', ''),
  'referer:ip' => getClientIp(),
  'referer:port' => tryGet($_SERVER, 'SERVER_PORT', ''),
  'referer:method' => tryGet($_SERVER, 'REQUEST_METHOD', 'CLI'),
  'referer:host' => tryGet($_SERVER, 'HTTP_HOST', ''),
  'referer:url' => tryGet($_SERVER, 'HTTP_REFERER', '')
];

if ($system['referer:method'] === 'OPTIONS')
  closeAndExit();

$input = json_decode(file_get_contents('php://input'), true);
if (gettype($input) !== 'array')
  $input = [];

$input = (
  count($_REQUEST) === 0 ? [] : $_REQUEST
) + (
  array_key_exists('argv', $_SERVER) && count($_SERVER['argv']) > 1
  ? ((array) json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]), true))
  : []
) + $input;
//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'),true)['immi_cascade'][0];

//NB! HARDCODE
if (array_key_exists('cc:number', $input))
  $input['cc:number'] = preg_replace('/[^0-9]+/', '', $input['cc:number']);

// The only field to be hardcoded - key 'account' will be used as it in 3rd parties, avoid ambiguity
$system['_account'] = $input['account'];
unset($input['account']);
$system['process'] = $input['process'];

$input = $system + $input;

$input = (object) $input;

$event = 'Request';
$phase = 'Raw';

if (!property_exists($input, 'id')) $input->id = $input->tmstmp;

$processDir = mkdir2(__DIR__, '..', 'processes', $input->_account,  $input->process, $input->id);
$logDir = mkdir2($processDir, $input->tmstmp);
$processDir = mkDir2($processDir, 'index');

$steps = json_decode(file_get_contents($ConfigDir."/processes/$input->_account/$input->process.json"));
$strategy = array_keys(get_object_vars($steps))[0];
$steps = $steps->{$strategy};
$output = [];
$filled = [];
$requestData = [];
forEach($steps as $step) {
  $handler = $step->instance;
  //TODO: Move out from code
  $directionSchema = [
      "fields" => new \stdClass,
      "values" => new \stdClass,
      "defaults" => new \stdClass,
      "overrides" => new \stdClass
  ];
  $schema = [
    "engine" => new \stdClass,
    "request" => $directionSchema,
    "response" => $directionSchema
  ];
  $instance = json_decode(file_get_contents($ConfigDir."/instances/$handler/index.json"), true)
    + $schema;
  // Awfull
  $instance = json_decode(json_encode($instance));
  
  $handlerPath = $ConfigDir."/instances/$handler/handler.php";
  if (file_exists($handlerPath))
    require_once($handlerPath);
  else {
    $handler = 'CycleHandler';
    require_once($ConfigDir."/$handler.php");
  }

  $instanceEnv = json_decode(file_get_contents($ConfigDir."/instances/$step->instance/accounts/$step->account.json"));

  $request = (object) \assoc\merge($instance->request, $instanceEnv->request);
  $response = (object) \assoc\merge($instance->response, $instanceEnv->response);
  
  $filled = (object) \assoc\merge(
    $request->defaults,
    $input,
    $request->overrides
  );

  $event = 'Request';
  $phase = 'Filled';
  
  $request->engine = (object) $request->engine;
  $filled = fireEvent(
    (property_exists($request->engine, 'history') && $request->engine->history)
    ? (object) \assoc\merge($requestData, $output, $filled)
    : $filled
  );

  $event = 'Request';
  $phase = 'Calced';

  //Keep unified vocabulary in schema keys
  $requestData = (property_exists($request->engine, 'sourceIsAPI') && $request->engine->sourceIsAPI)
  ? \assoc\mapValues(
    \assoc\mapKeys(
      $filled,
      (object) array_flip((array) $request->fields),
      false
    ),
    (object) $request->values,
    true
  )
  : \assoc\mapKeys(
    \assoc\mapValues(
      $filled,
      (object) $request->values,
      true
    ),
    (object) $request->fields,
    false
  );

  $event = 'Request';
  $phase = 'Formed';

  $request->engine->gateway = formatString(formatString(
    $request->engine->gateway,
    $requestData), $filled
  );

  $cachePath = '';
  if (property_exists($request->engine, 'cache') && $request->engine->cache) {
    $cacheDir = mkdir2(__DIR__, '..', 'cached', $input->_account, $input->process);
    $cachePath = "{$cacheDir}/"
    .hash("md5",
      json_encode($request->engine)
      .json_encode($requestData)
    );
    if (file_exists($cachePath))
      $request->engine->method = 'useCached';
  }
  switch($request->engine->method) {
    case 'PATCH':
    case 'POST':
      $ch = curl_init($request->engine->gateway);
      curl_setopt_array($ch,
        [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FRESH_CONNECT => true,
          CURLOPT_CUSTOMREQUEST => $request->engine->method,
          CURLOPT_HEADER => 1,
          //CURLOPT_VERBOSE => 1,
          CURLOPT_POSTFIELDS => json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
          CURLOPT_HTTPHEADER => array_merge(
            [
              'Request-Date: '. gmdate('D, d M Y H:i:s T'),
              'Date: '. gmdate('D, d M Y H:i:s T')
            ],
            property_exists($request->engine, 'headers')
            ? $request->engine->headers
            : []
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
      $request->engine->gateway = $request->engine->gateway.'?'.http_build_query($requestData);
      $responseText = file_get_contents($request->engine->gateway);      
      break;
    case 'useCached':
      $responseText = file_get_contents($cachePath);      
      break;
    default: {
      http_response_code(501);
      closeAndExit('not impelemented');
    }
  }

  if ($cachePath !== '' && $request->engine->method !== 'useCached')
    file_put_contents($cachePath, $responseText);
  
  switch($response->engine->contentType) {
    case 'application/x-www-form-urlencoded':
      parse_str(
        $responseText,
        $responseData
      );
      break;
    case 'application/json':
      $responseData = json_decode($responseText);
      if ($responseData !== null && gettype($responseData) === 'object') 
        break;
    case 'text/plain':
    default:
      $responseData = ['response' => $responseText];
  }
  $responseData = (object) $responseData; 

  $event = 'Response';
  $phase = 'Raw';
  $output = fireEvent($responseData, $requestData);

  $output = \assoc\mapValues(
    \assoc\mapKeys(
      $output,    
      \assoc\flip($response->fields),
      false
    ),
    (object) $response->values,
    true
  );

  $output = (object) ($system + (array) $output);

  $event = 'Response';
  $phase = 'Formed';
  $output = fireEvent($output, $filled);
  if (
    // until first TRUE
    $strategy === 'oneOf' && $output->success === 1
    // until first FALSE
    || $strategy === 'allOf' && $output->success === 0
    // 'just do it' is 'anyOf' - and any value of $strategy (even 'Nike')
  )
    break;
}

echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function fireEvent(...$data) :object {
  global $event, $phase, $handler, $logDir, $processDir, $commonHandler, $step;
  $data[0] = (object) \assoc\merge(
    call_user_func(
      ["\\$commonHandler", "on$event$phase"],
      ...array_merge([$step], $data)
    ),
    $data[0]
  );
  $data[0] = (object) \assoc\merge(
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
  return (object) $data[0];
}
