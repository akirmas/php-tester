<?php
class CreditCard {
  public $number;
  public $cvv;
  public $month;
  public $year;

  /**
   * @param array $assoc Consist string $number, int $month, int $year, string $cvv
   */
  function __construct($assoc) {
    $this->number = (string) $assoc['number'];
    $this->month = (int) $assoc['month'];
    $this->year = (int) $assoc['year'];
    $this->cvv = (string) $assoc['cvv'];
  }
}
