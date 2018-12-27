<?php
declare(strict_types=1);

require_once(__DIR__.'/Assoc.php');

class Transaction extends Assoc{
  public $account;
  public $fee;
  public $installments;
  public $verify;
  public $id;

  function __construct($assoc) {
    parent::__construct($assoc, $this);
  }
}
