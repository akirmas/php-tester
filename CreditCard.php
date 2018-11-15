<?php
class CreditCard {
  private $number;
  private $cvv;
  private $month;
  private $year;

  function __construct($number, $month, $year, $cvv) {
    $this->number = $number;
    $this->month = $month;
    $this->year = $year;
    $this->cvv = $cvv;
  }

  function export() {
    
  }
}
