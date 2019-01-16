<?php
function mkdir2(...$folders) {
  $dir = join(DIRECTORY_SEPARATOR, $folders);
  if (!file_exists($dir)) mkdir($dir, 0777, true);
  return realpath($dir);
}
