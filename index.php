<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once(__DIR__.'/assoc.php');
require_once(__DIR__.'/utils.php');
require_once(__DIR__.'/handler.php');
$commonHandler = 'CommonHandler';

//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'))->tranz_instant[0];
$input = (object) (sizeof($_REQUEST) !== 0
? $_REQUEST
: (array_key_exists('argv', $_SERVER)
? json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]))
:  json_decode(file_get_contents('php://input'))
));

if (!property_exists($input, 'id')) $input->id = tmstmp();

$ConfigDir = mkdir2(__DIR__, 'configs');
$step = json_decode(file_get_contents($ConfigDir."/processes/$input->process.json"));
$handler = $step->instance;
$instance = json_decode(file_get_contents($ConfigDir."/instances/$handler/index.json"));

$processDir = mkdir2(__DIR__, 'processes', $input->process, $input->id);
$logDir = mkdir2($processDir, tmstmp());
$processDir = mkDir2($processDir, 'index');

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

$url = ((object) $request->engine)->gateway;

$event = 'Request';
$phase = 'Raw';
$requestData = fireEvent($input);

$requestData = (object) \assoc\merge(
  $request->defaults,
  $requestData,
  $request->overrides
);

$event = 'Request';
$phase = 'Formed';
$requestData = fireEvent($requestData);
$requestData = \assoc\mapKeys(
  \assoc\mapValues(
    $requestData,
    (object) $request->values,
    true
  ),
  $request->fields,
  false
);

$request->engine = (object) $request->engine;
switch($request->engine->method) {
  case 'POST':
    $ch = curl_init($request->engine->gateway);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE,"XDEBUG_SESSION=VSCODE");
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
    $gate = $url.'?'.http_build_query($requestData);
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
    true
  ),
  (object) $response->values,
  true
);

$event = 'Response';
$phase = 'Formed';
$output = fireEvent($output, $input);

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
  foreach ($dirs as $dir) {
    file_put_contents(
      "$dir/$handler-$event$phase.json",
      json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
  }
  return $data[0];
}
