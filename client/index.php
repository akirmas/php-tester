<?php
/**
 * https://github.com/gobemarketing/psps/issues/11
 */

/*ini_set('display_errors', 'off');
ini_set("log_errors", 1);*/

$input = (object) (sizeof($_REQUEST) !== 0
? $_REQUEST
: (array_key_exists('argv', $_SERVER)
? json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]))
:  json_decode(file_get_contents('php://input'))
));

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($input->secret !== '2rzcxfgGQENj7TQ0') {
  header("HTTP/1.0 404 Not Found");
  exit;
}

//header('Content-Type: application/json');
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__.'/../utils.php');

// processes/%process%/%id%/(index | %tmstmp%)
$processDir = realpath(__DIR__.'/../../processes');
// events/%instance%/%transaction.id%===%event.id%
$eventsDir = realpath(__DIR__.'/../../events');

$process = "$processDir/$input->process";
if (!inFolder($processDir, $process)) exit;

$output = [];

foreach (scandir2($process) as $id) {
  foreach(scandir2("$process/$id") as $processName) {
    //foreach(scandir2("$process/$id/$processName") as $tmstmp) {
    $tmstmp = 'index';
    if (is_dir("$process/$id/$processName/$tmstmp")) {
      foreach(scandir2("$process/$id/$processName/$tmstmp") as $instance) {
        //foreach(scandir2("$process/$id/$processName/$tmstmp/$instance") as $phaseFile) {
        $phaseFile = "index.json";
        if (file_exists("$process/$id/$processName/$tmstmp/$instance/$phaseFile")) {
          $phase = json_decode(
            file_get_contents("$process/$id/$processName/$tmstmp/$instance/$phaseFile")
          )[0];
          /*$output = array_replace_recursive($output, [
            "_type" => 'account',
            $input->process => [
              "_type" => 'id',
              $id => [
                "_type" => 'processName',
                $processName => [
                  "_type" => 'tmstmp',
                  $tmstmp => [
                    "_type" => 'instance',
                    $instance => [
                      "_type" => 'phase',
                      $phaseFile => $phase
                    ]
                  ]
                ]
              ]
            ]
          ]);*/
          if ($tmstmp !== 'index' || $phaseFile !== 'index.json')
            continue;
          $success = !property_exists($phase, 'success') ? -1 : $phase->success;
          $output[$input->process][$id][$processName][$instance]
          ['success'] = $success;
          foreach(
            ['return:message', 'tmstmp', 'quizUrl']
            as $key
          ) if (property_exists($phase, $key))
              $output[$input->process][$id][$processName][$instance]
                [$key] = $phase->{$key};

          if (!property_exists($phase, 'event'))
            continue;
          $eventFile = "$eventsDir/$phase->event/index.json";
          if (!file_exists($eventFile))
            continue;
          $eventContent = json_decode(file_get_contents($eventFile));
          $output[$input->process][$id][$processName][$instance]['event:content'] = $eventContent;          
        }
      }
    }
  }
}

echo json_encode($output);
