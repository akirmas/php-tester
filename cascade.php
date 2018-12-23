<?php
declare(strict_types=1);
ini_set('max_execution_time', 0);
set_time_limit(0);
require_once('./Tranzila.php');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

//echo time()."\n";
$psps = array(
  'tranzila' => new Tranzila()
);


$cascade = json_decode(file_get_contents('./cascade.json'), true);


$test = array(
  'amount' => 1,
  'currency' => 'NIS',
  
  'number' => '12312312',
  'month' => 1,
  'year' => 2020,
  'cvv' => '123',

  'email' => 'email',
  'product' => 'product',
  'contact' => 'contact',

  'check' => 'true'
);

$succeeded = false;
$trans = new Transaction($test);
$i = 0;
$messages = [];
$result;

while (!$succeeded && $i < sizeof($cascade)) {
  $provider = $cascade[$i];
  $trans->supplier = $provider['supplier'];
  try {
    $result = $psps[$provider['psp']]->execute($trans, function(){});
    $succeeded = true;
  } catch(Exception $e) {
    $messages[] = array(
      'psp' => $provider['psp'],
      'supplier' => $provider['supplier'],
      'error' => $e->getMessage()
    );
  } finally {
    $i++;
  }
}

if (!$succeeded) {
 echo json_encode(array('errors' => $errors));
}
