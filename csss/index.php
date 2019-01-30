<?php
require_once(__DIR__.'/../utils.php');

$css = !array_key_exists('css', $_GET)
  // Trick to cause error - 'out of folder' in this case
  ? '..'
  : $_GET['css'].'.css';
fileDelivery(__DIR__, $css, 'text/css');
exit;
