<?php
declare(strict_types=1);
require_once('./CreditCard.php');

class Transaction {
  public $amount;
  public $currency;
  public $creditCard;

  function __construct(int $amount, string $currency, CreditCard $creditCard) { 
    $this->amount = $amount;
    $this->currency = $currency;
    $this->creditCard = $creditCard;
  }
}
