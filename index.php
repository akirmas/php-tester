<?php
set_time_limit(0);
ini_set('max_execution_time', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: origin, x-requested-with, Content-Type, Date, Request-Date');

require_once(__DIR__.'/assoc.php');
require_once(__DIR__.'/utils.php');
require_once(__DIR__.'/handler.php');
$commonHandler = 'CommonHandler';

if (!isset($_SERVER['REQUEST_METHOD']))
  $_SERVER['REQUEST_METHOD'] = 'CLI';

$system = [
  'tmstmp' => tmstmp(),
  'http:ip' => getClientIp(),
  'http:method' => $_SERVER['REQUEST_METHOD']
];

if ($system['http:method'] === 'OPTIONS')
  exit;

//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'))->tranz_instant[0];
$input = (sizeof($_REQUEST) !== 0
? $_REQUEST
: (array_key_exists('argv', $_SERVER) && sizeof($_SERVER['argv']) > 1
? json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]), true)
: json_decode(file_get_contents('php://input'), true)
));
$input = $system + $input;

$input = (object) $input;

$event = 'Request';
$phase = 'Raw';

if (!property_exists($input, 'id')) $input->id = $input->tmstmp;

$ConfigDir = mkdir2(__DIR__, 'configs');

$processDir = mkdir2(__DIR__, '..', 'processes', $input->account, $input->id, $input->process);
$logDir = mkdir2($processDir, $input->tmstmp);
$processDir = mkDir2($processDir, 'index');

$steps = json_decode(file_get_contents($ConfigDir."/processes/$input->account/$input->process.json"));
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
    require_once(__DIR__."/$handler.php");
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
  $filled = fireEvent($filled);

  $event = 'Request';
  $phase = 'Calced';

  $requestData = \assoc\mapKeys(
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

  $request->engine = (object) $request->engine;
  $request->engine->gateway = formatString(
    $filled,
    $request->engine->gateway
  );

  switch($request->engine->method) {
    case 'POST':
      $ch = curl_init($request->engine->gateway);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->engine->method);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [   
        'Content-Type: application/json'                                                              
      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
      $responseText = curl_exec($ch);
      if ($responseText === false) 
        throw new Exception(curl_error($ch), curl_errno($ch));
      $responseData = json_decode($responseText);
      $htmlResp = '<!doctype html>';
      if ($htmlResp === strtolower(substr($responseText, 0, strlen($htmlResp))))
        file_put_contents("$processDir/error.html", $responseText);
      curl_close($ch);
      break;
    case 'GET':
      $gate =$request->engine->gateway.'?'.http_build_query($requestData);
      parse_str(
        file_get_contents($gate),
        $responseData
      );
      $responseData = (object) $responseData; 
      $responseData->gate = $gate;
      break;
    default: exit('not impelemented');
  }
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
  if ($output->success === 1)
    break;
}

echo json_encode($output);

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
  $dataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
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
