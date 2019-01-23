<?php
/**
 * https://github.com/gobemarketing/psps/issues/11
 */

$input = (object) (sizeof($_REQUEST) !== 0
? $_REQUEST
: (array_key_exists('argv', $_SERVER)
? json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]))
:  json_decode(file_get_contents('php://input'))
));

if ($input->secret !== '2rzcxfgGQENj7TQ0') {
  header("HTTP/1.0 404 Not Found");
  exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once(__DIR__.'/../utils.php');

// processes/%process%/%id%/(index | %tmstmp%)
$processDir = realpath(__DIR__.'/../processes');
// events/%instance%/%transaction.id%===%event.id%
$eventsDir = realpath(__DIR__.'/../events');

$process = "$processDir/$input->process";
if (!inFolder($processDir, $process)) exit;

$output = [];

foreach (scandir2($process) as $id) {
  foreach(scandir2("$process/$id") as $processName) {
    foreach(scandir2("$process/$id/$processName") as $tmstmp) {
      foreach(scandir2("$process/$id/$processName/$tmstmp") as $instance) {
        foreach(scandir2("$process/$id/$processName/$tmstmp/$instance") as $phaseFile) {
          $phase = json_decode(
            file_get_contents("$process/$id/$processName/$tmstmp/$instance/$phaseFile")
          )[0];
          $output = array_replace_recursive($output, [
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
          ]);
          if ($tmstmp !== 'index' || $phaseFile !== 'index.json')
            continue;
          $success = !property_exists($phase, 'success') ? -1 : $phase->success;
          $output[$input->process][$id][$processName]['success'] = $success;
          $eventFile = "$eventsDir/$phase->event/index.json";
          if ($success !== -1 || !file_exists($eventFile)) continue;
          $eventContent = json_decode(file_get_contents($eventFile));
          $output[$input->process][$id][$processName]['success'] = $eventContent->success;
          $output[$input->process][$id][$processName]['return:message'] = $eventContent->{'return:message'};
        }
      }
    }
  }
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
