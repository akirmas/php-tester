<?php
declare(strict_types=1);
require_once(__DIR__.'/Assoc.php');

class Contact extends Assoc {
  /** @var string */
  public $first_name;
  public $last_name;
  public $email;
  public $phone;
  function __construct(array $assoc, bool $filterEmpty = true) {
   parent::__construct($assoc, $filterEmpty, $this);
  }
}
