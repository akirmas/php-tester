<?php
declare(strict_types=1);

require_once('./CreditCard.php');

class Transaction {
  public $amount;
  public $currency;
  public $creditCard;
  public $contact;
  public $product;
  public $check;
  public $supplier;
  //public const type = 1; // count of charges

  /**
   * @param array $assoc Contains int $amount, Currency $currency, CreditCard $creditCard, string $contact, string $product, bool $check|false, string $supplier|''
   */

  function __construct(
    $assoc
  ) { 
    $this->amount = $assoc['amount'];
    $this->currency = $assoc['currency'];
    $this->creditCard = new CreditCard($assoc);
    $this->contact = $assoc['contact'];
    $this->product = $assoc['product'];
    $this->check = array_key_exists('check', $assoc) ? $assoc['check'] : false;
    $this->supplier = array_key_exists('supplier', $assoc) ? $assoc['supplier'] : '';
  }
}
