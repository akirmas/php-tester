<?php
declare(strict_types=1);
require_once(__DIR__.'/Assoc.php');

class Contact extends Assoc {
  /** @var string */
  public $first_name;
  public $last_name;
  public $full_name;
  public $email;
  public $phone;
  public $social_id;
  public $address;

  function __construct($assoc) {
    parent::__construct($assoc, $this);
    if (empty($this->full_name))
      $this->full_name = join(' ', [$this->first_name, $this->last_name]);
    elseif (empty($this->first_name) || empty($this->last_name)) {
      $names = explode(' ', $this->full_name);
      $this->last_name = array_pop($names);
      $this->first_name = array_shift($names);
      $this->last_name = join(' ', $names + [$this->last_name]);
    }
  }
}
