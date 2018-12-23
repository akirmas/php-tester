<?php
  require_once('./Transaction.php');

  interface PSP {
    public function execute(Transaction $transaction, callable $onAction) :array;
  }