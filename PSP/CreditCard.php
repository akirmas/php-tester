<?php
declare(strict_types=1);
require_once(__DIR__.'/Assoc.php');

class CreditCard extends Assoc {
  protected $prefix = 'cc_';
  public $number;
  public $cvv;
  public $expire;

  function __construct($assoc) {
    parent::__construct($assoc, $this);
  }
}
