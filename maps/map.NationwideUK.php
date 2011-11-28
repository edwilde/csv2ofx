<?php
/** 
 *  mapping file for Nationwide(UK) formatted CSV files.
 *  good for 4 types of file they produce (current, savings, isa, credit card)
 *  all mapping variables are required unless otherwise noted
 *
 *  @author     Ed Wilde <csv2ofx@edwilde.com>
 *  @version    1.1 (last revision: November 8, 2011)
 *  @copyright  (c) 2011 Ed Wilde
 *  @package    csv2ofx
**/


// name of the bank
$csv2ofx->bankName = "Nationwide (UK)";
// form of currency
$csv2ofx->currency = "GBP";

// mapping variables (all numbers start at 0)
// the first row number where the transactions begin
$csv2ofx->rowLoopStarts = 5;
// column number for the date
$csv2ofx->colDate = 0;
// column number for account credits
$csv2ofx->colCredit = 4;
// column number for account debits
$csv2ofx->colDebit = 3;
// column number for transaction descriptions
$csv2ofx->colDescription = 1;
// second column number for transaction descriptions (optional)
$csv2ofx->colDescription2 = 2;

// x-grid coordinates for account balance cell
$csv2ofx->colBalance = 1;
// y-grid coordinates for account balance cell
$csv2ofx->rowBalance = 1;

?>