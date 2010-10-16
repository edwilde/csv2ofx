<?php
/** 
 *  mapping file for Nationwide(UK) formatted CSV files.
 *  good for 4 types of file they produce (current, savings, isa, credit card)
 *  all mapping variables are required unless otherwise noted
 *
 *  @author     Ed Wilde <csv2ofx@edwilde.com>
 *  @version    1.0 (last revision: December 24, 2009)
 *  @copyright  (c) 2009 Ed Wilde
 *  @package    csv2ofx
**/


// name of the bank
$csv2ofx->bankName = "Nationwide (UK)";
// form of currency
$csv2ofx->currency = "GBP";
// type of statement
$csv2ofx->csvType = "CHECKING";

// mapping variables
// the first row number where the transactions begin
$csv2ofx->rowLoopStarts = null;
// column number for the date
$csv2ofx->colDate = null;
// column number for account credits
$csv2ofx->colCredit = null;
// column number for account debits
$csv2ofx->colDebit = null;
// column number for transaction descriptions
$csv2ofx->colDescription = null;
// second column number for transaction descriptions (optional)
$csv2ofx->colDescription2 = null;

// x-grid coordinates for account balance cell
$csv2ofx->colBalance = null;
// y-grid coordinates for account balance cell
$csv2ofx->rowBalance = null;

$csv2ofx->addStrip("Account Balance: ");
$isISA = false;

// find out what kind of statement this is
switch ($csv2ofx->csv[0][0])
{
	case "Account name: ":
		$csv2ofx->csvType = "CHECKING";
		break;
	case "Full Statement":
		if ($csv2ofx->csv[8][0] != "")
		{
			$isISA = true;
		}
		$csv2ofx->csvType = "SAVINGS";
		break;
	case "Date":
		$csv2ofx->csvType = "CREDITCARD";
		break;
}


// find essential data in the CSV		
switch ($csv2ofx->csvType) {
	case "CHECKING":
		// for Current Accounts
		$csv2ofx->rowLoopStarts = 5;
		$csv2ofx->colDate = 0;
		$csv2ofx->colCredit = 3;
		$csv2ofx->colDebit = 2;
		$csv2ofx->colDescription = 1;
		
		$csv2ofx->colBalance = 1;
		$csv2ofx->rowBalance = 1;
		break;
	case "SAVINGS":
		// for Savings Accounts
		if ($isISA)
		{
			$csv2ofx->rowLoopStarts = 8;
			$csv2ofx->colDate = 0;
			$csv2ofx->colCredit = 2;
			$csv2ofx->colDebit = 3;
			$csv2ofx->colDescription = 1;
			
			$csv2ofx->colBalance = 0;
			$csv2ofx->rowBalance = 6;
		} else {
			$csv2ofx->rowLoopStarts = 10;
			$csv2ofx->colDate = 0;
			$csv2ofx->colCredit = 2;
			$csv2ofx->colDebit = 3;
			$csv2ofx->colDescription = 1;
			
			$csv2ofx->colBalance = 0;
			$csv2ofx->rowBalance = 6;
		}
		break;
	case "CREDITCARD":
		// for Credit Card Accounts
		$csv2ofx->rowLoopStarts = 2;
		$csv2ofx->colDate = 0;
		$csv2ofx->colCredit = 3;
		$csv2ofx->colDebit = 4;
		$csv2ofx->colDescription = 1;
		$csv2ofx->colDescription2 = 2;
		
		$csv2ofx->colBalance = 7;
		$csv2ofx->rowBalance = 0;
		break;
}


?>