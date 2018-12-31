<?php
ini_set("implicit_flush", 1);
ini_set('max_execution_time', 0);
while (@ob_end_flush());
ob_implicit_flush(true);
class Collector {
  public const fireField = 'fireId';
  private $fireId;
  private $nodeJS;
  public $callbackUrl;

  static function contentPath($fireId) {
    return __DIR__."/content/$fireId.json";
  }

  function __construct($fireId) {
    ini_set('max_execution_time', 0);
    $this->fireId = $fireId;
    $this->nodeJS = new SyncEvent($this->fireId);
    $this->callbackUrl =
      "https://"
      ."payment.gobemark.info"
      ."/php/psps/collector"
      ."/?"
      .self::fireField."=$this->fireId&";
  }

  function wait(&$content = '') {
    ob_flush();
    flush();  
    $this->nodeJS->wait();
    $content = file_get_contents(self::contentPath($this->fireId));
    return $content;
  }
}
