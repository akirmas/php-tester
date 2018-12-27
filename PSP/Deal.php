<?php
declare(strict_types=1);
require_once(__DIR__.'/Assoc.php');

class Deal extends Assoc {
  /** @var string */
  public $amount;
  public $currency;
  public $description;
  public $id;
  function __construct($assoc) {
    parent::__construct($assoc, $this);
  }
}
