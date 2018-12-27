<?php
declare(strict_types=1);
require_once(__DIR__.'/Assoc.php');

class Contact extends Assoc {
  /** @var string */
  public $first_name;
  public $middle_name;
  public $last_name;
  public $full_name;
  public $email;
  public $phone;
  public $social_id;
  public $address;

  function __construct($assoc) {
    parent::__construct($assoc, $this);
  }
}
