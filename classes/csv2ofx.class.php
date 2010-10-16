<?php
/**
 *  A PHP class providing a set of methods for converting various 
 *  forms of CSV to Open Financial Exchange (OFX) files.
 *
 *  For more resources visit {@link http://edwilde.com}
 *
 *  @author     Ed Wilde <csv2ofx@edwilde.com>
 *  @version    1.4.4 (last revision: June 01, 2010)
 *  @copyright  (c) 2009 Ed Wilde
 *  @package    csv2ofx
 *  @example    not yet
 */
class csv2ofx
{
	const version = "1.4.4";
	public $mapversion = "not loaded";

    // public property declaration
    public $debug = false;
    public $errorMessage = null;
    
    public $accountNumber = "";
    public $bankName = "";
    public $currency = "GBP";
	
	// mapping variables
	// the first row number where the transactions begin
	public $rowLoopStarts = null;
	// column number for the date
	public $colDate = null;
	// column number for account credits
	public $colCredit = null;
	// column number for account debits
	public $colDebit = null;
	// column number for transaction descriptions
	public $colDescription = null;
	// second column number for transaction descriptions 
	public $colDescription2 = null;
	
	// x-grid coordinates for account balance cell
	public $colBalance = null;
	// y-grid coordinates for account balance cell
	public $rowBalance = null;
	// variable to manually add balance
	public $balance = null;
	
	// create array to hold CSV data
	// form is $csv[row][column] - from 0 ('java-style')
	public $csv = array();
	public $csvType = "";
	public $csvSeperator = ",";
	public $csvLineBreak = "\n";
	
	// specifiy the OFX schema used for export, 1 (1.0.3) or 2 (2.1.1)
	public $ofxType = 2;
	
	// private property declaration
    private $dataDateFormat = "YmdHis";
	private $dataDate = "";
	
	// array of words to skip (if a row contains this word, it wont be included in the OFX
	private $wordsToSkip = array();
	
	// array of words to strip from data, always strip £ signs and characters that make XML bork
	private $wordsToStrip = array();
	
	// the OFX
	private $ofx = "";
	
	// some re-usable syntax
	private $br = "\n";
	private $tab = "\t";
	
    
    function __construct() 
    {
    	$this->dataDate = date($this->dataDateFormat);
    	$this->wordsToStrip = array("£",chr(163),"\"","\\","\n","\r", ",");
    }
    
    
    public function addSkip($word)
    {
    	// add a word to the skip list
    	array_push($this->wordsToSkip, $word);
    }
    
    
    public function addStrip($word)
    {
    	// add a word to the list of words/characters to strip from data
    	array_push($this->wordsToStrip, $word);
    }
    
    
    public function parseVariable($data)
    {
    
    	$row = 0;
    	if ($data != null) {
    	
    		$dataRows = explode($this->csvLineBreak, $data);
    	
    		foreach ($dataRows as $d)
    		{
    			$this->csv[$row] = explode($this->csvSeperator, $d);
    			$row++;
    		}
    	}
    	
    	if ($this->debug) { print_r($this->csv); }
    
    }
    

    // method declaration
    public function parseCSV($fileName)
    {
    	// open file
    	$fileHandle = fopen($fileName, 'r');
    	
		// parse the CSV into an array
		$row = 0;
		if ($fileHandle != false) {
		
			while (($data = fgetcsv($fileHandle, 1000, $this->csvSeperator)) !== false)
			{				$num = count($data);						for ($c=0; $c < $num; $c++) 
				{
					// make it multidimensional
					$this->csv[$row] = $data;				}
					
				$row++;			}
			
			// close file			fclose($fileHandle);
			
		}
		
		// delete the CSV
		unlink($fileName);
		
		if ($this->debug) { print_r($this->csv); }
	    
    }
    
    
    public function getOFX()
    {
    	$output = "";
    	
    	// remove syntax tidy if not in debug mode
    	if ((!$this->debug) && ($this->ofxType == 2)) { $this->br = ""; $this->tab = ""; }
    	
    	// first, make sure we have all the ingredients
    	if ($this->bankName == "") { $this->errorMessage .= "bankName not set" . $this->br; }
		if ($this->accountNumber == "") { $this->errorMessage .= "accountNumber not set" . $this->br; }
		if ($this->csvType == "") { $this->errorMessage .= "csvType is not set" . $this->br; }
    	
    	// next, check the mappings
		if (is_null($this->rowLoopStarts)) { $this->errorMessage .= "rowLoopStarts is not set" . $this->br; }
		if (is_null($this->colDate)) { $this->errorMessage .= "colDate is not set" . $this->br; }
		if (is_null($this->colCredit)) { $this->errorMessage .= "colCredit is not set" . $this->br; }
		if (is_null($this->colDebit)) { $this->errorMessage .= "colDebit is not set" . $this->br; }
		if (is_null($this->colDescription)) { $this->errorMessage .= "colDescription is not set" . $this->br; }
		if (is_null($this->balance)) {
			if (is_null($this->colBalance)) { $this->errorMessage .= "colBalance is not set" . $this->br; }
			if (is_null($this->rowBalance)) { $this->errorMessage .= "rowBalance is not set" . $this->br; }
		}
		if (count($this->csv) < $this->rowLoopStarts) { $this->errorMessage .= "problem with CSV" . $this->br; }
    	
    	if (is_null($this->errorMessage))
    	{
    		// make the OFX
    		if ($this->ofxType == 2) {
		    	$this->getOFX2Header();
		    	$this->getOFX2Transactions();
		    	$this->getOFX2Footer();
		    } else {
		    	$this->getOFX1Header();
		    	$this->getOFX1Transactions();
		    	$this->getOFX1Footer();
		    }
	    	
	    	$output = $this->ofx;
	    } else {
	    	// fail
	    	$output = $this->errorMessage;
	    }
	    
	    return $output;
    }
    
    
    private function getOFX1Header()
    {
    	
        // prepare the OFX header
        $ofx = "OFXHEADER:100";
        $ofx .= $this->br;
        $ofx .= "DATA:OFXSGML";
        $ofx .= $this->br;
        $ofx .= "VERSION:102";
        $ofx .= $this->br;
        $ofx .= "SECURITY:NONE";
        $ofx .= $this->br;
        $ofx .= "ENCODING:USASCII";
        $ofx .= $this->br;
        $ofx .= "CHARSET:1252";
        $ofx .= $this->br;
        $ofx .= "COMPRESSION:NONE";
        $ofx .= $this->br;
        $ofx .= "OLDFILEUID:NONE";
        $ofx .= $this->br;
        $ofx .= "NEWFILEUID:NONE";
        $ofx .= $this->br;
        $ofx .= $this->br;
        $ofx .= "<OFX>";
        $ofx .= $this->br;
        $ofx .= "<SIGNONMSGSRSV1>";
        $ofx .= $this->br;
        $ofx .= "<SONRS>";
        $ofx .= $this->br;
        $ofx .= "<STATUS>";
        $ofx .= $this->br;
        $ofx .= "<CODE>0";
        $ofx .= $this->br;
        $ofx .= "<SEVERITY>INFO";
        $ofx .= $this->br;
        $ofx .= "</STATUS>";
        $ofx .= $this->br;
        $ofx .= "<DTSERVER>" . $this->dataDate;
        $ofx .= $this->br;
        $ofx .= "<LANGUAGE>ENG";
        $ofx .= $this->br;
        $ofx .= "</SONRS>";
        $ofx .= $this->br;
        $ofx .= "</SIGNONMSGSRSV1>";
        $ofx .= $this->br;
        
        switch ($this->csvType)
        {
        	case "CREDITCARD":
		        $ofx .= "<CREDITCARDMSGSRSV1>";
		        $ofx .= $this->br;
		        $ofx .= "<CCSTMTTRNRS>";
		        $ofx .= $this->br;
		        $ofx .= "<TRNUID>1";
		        $ofx .= $this->br;
		        $ofx .= "<STATUS>";
		        $ofx .= $this->br;
		        $ofx .= "<CODE>0";
		        $ofx .= $this->br;
		        $ofx .= "<SEVERITY>INFO";
		        $ofx .= $this->br;
		        $ofx .= "</STATUS>";
		        $ofx .= $this->br;
		        $ofx .= "<CCSTMTRS>";
		        $ofx .= $this->br;
		        $ofx .= "<CURDEF>" . $this->currency;
		        $ofx .= $this->br;
		        $ofx .= "<CCACCTFROM>";
		        $ofx .= $this->br;
		        $ofx .= "<ACCTID>" . $this->accountNumber;
		        $ofx .= $this->br;
		        $ofx .= "</CCACCTFROM>";
		        $ofx .= $this->br;
		        break;
		        
		     default:
		     	$ofx .= "<BANKMSGSRSV1>";
		     	$ofx .= $this->br;
		     	$ofx .= "<STMTTRNRS>";
		     	$ofx .= $this->br;
		     	$ofx .= "<TRNUID>1";
		     	$ofx .= $this->br;
		     	$ofx .= "<STATUS>";
		     	$ofx .= $this->br;
		     	$ofx .= "<CODE>0";
		     	$ofx .= $this->br;
		     	$ofx .= "<SEVERITY>INFO";
		     	$ofx .= $this->br;
		     	$ofx .= "</STATUS>";
		     	$ofx .= $this->br;
		     	$ofx .= "<STMTRS>";
		     	$ofx .= $this->br;
		     	$ofx .= "<CURDEF>" . $this->currency;
		     	$ofx .= $this->br;
		     	$ofx .= "<BANKACCTFROM>";
		     	$ofx .= $this->br;
		     	$ofx .= "<BANKID>000000";
		     	$ofx .= $this->br;
		     	$ofx .= "<ACCTID>" . $this->accountNumber;
		     	$ofx .= $this->br;
		     	$ofx .= "<ACCTTYPE>" . $this->csvType;
		     	$ofx .= $this->br;
		     	$ofx .= "</BANKACCTFROM>";
		     	break;
		}
    	    	
    	$this->ofx .= $ofx;
    }
    
    
    private function getOFX2Header()
    {
    	
	    // prepare the OFX header
		$ofx = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
		$ofx .= $this->br;
		$ofx .= "<?OFX OFXHEADER=\"200\" SECURITY=\"NONE\" OLDFILEUID=\"NONE\" VERSION=\"200\" NEWFILEUID=\"NONE\"?>";
		$ofx .= $this->br;
		$ofx .= $this->br;
		$ofx .= "<OFX>";
		$ofx .= $this->br;
		$ofx .= $this->tab(1) . "<SIGNONMSGSRSV1>";
		$ofx .= $this->br;
		$ofx .= $this->tab(2) . "<SONRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<STATUS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<CODE>0</CODE>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<SEVERITY>INFO</SEVERITY>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "</STATUS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<DTSERVER>" . $this->dataDate . "</DTSERVER>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<LANGUAGE>ENG</LANGUAGE>";
		$ofx .= $this->br;
		$ofx .= $this->tab(2) . "</SONRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(1) . "</SIGNONMSGSRSV1>";
		$ofx .= $this->br;
		$ofx .= $this->tab(1) . "<BANKMSGSRSV1>";
		$ofx .= $this->br;
		$ofx .= $this->tab(2) . "<STMTTRNRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<TRNUID>1</TRNUID>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<STATUS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<CODE>0</CODE>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<SEVERITY>INFO</SEVERITY>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "</STATUS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "<STMTRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<CURDEF>" . $this->currency . "</CURDEF>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "<BANKACCTFROM>";
		$ofx .= $this->br;
		$ofx .= $this->tab(5) . "<BANKID>" . $this->bankName . "</BANKID>";
		$ofx .= $this->br;
		$ofx .= $this->tab(5) . "<ACCTID>" . $this->accountNumber . "</ACCTID>";
		$ofx .= $this->br;
		// ACCTTYPE can be 'CHECKING', 'SAVINGS', 'CREDITCARD'
		$ofx .= $this->tab(5) . "<ACCTTYPE>" . $this->csvType . "</ACCTTYPE>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "</BANKACCTFROM>";
		$ofx .= $this->br;
		
		$this->ofx .= $ofx;
	}
	
	
	private function getOFX1Transactions()
	{
		$br = $this->br;
		
		// iterate through the array
		$ofx = "<BANKTRANLIST>";
		$ofx .= $this->br;
		
		// <DTSTART><DTEND> - can represent the time/date when the server started/stopped *looking* for the data, not the first/last transction.
		$ofx .= "<DTSTART>".$this->dataDate;
		$ofx .= $this->br;
		$ofx .= "<DTEND>".$this->dataDate;
		$ofx .= $this->br;
		
		
		// the good stuff
		for ($y=$this->rowLoopStarts; $y < count($this->csv); $y++) {
			if ($this->debug) { echo "loop " . $y . "\n"; }
			// full description
			if ($this->csv[$y][$this->colDate] != "") {
				if (!is_null($this->colDescription))
				{
					if ($this->csv[$y][$this->colDescription] != "")
					{
						$fullDescription = $this->csv[$y][$this->colDescription];
					}
				}
				if (!is_null($this->colDescription2))
				{
					if ($this->csv[$y][$this->colDescription2] != "")
					{
						$fullDescription .= " " . $this->csv[$y][$this->colDescription2];
					}
				}
				
				
				if ($this->substr_count_array($fullDescription, $this->wordsToSkip) == 0) {
					
					// begin transaction syntax
					
					$ofx .= "<STMTTRN>";
					$ofx .= $this->br;
					
					if (($this->csv[$y][$this->colDebit] == "") || ($this->csv[$y][$this->colDebit] == "0"))
					{ 
						$trntype = "CREDIT";
					} else {
						$trntype = "DEBIT";
					}
					
					$ofx .= "<TRNTYPE>" . $trntype;
					$ofx .= $this->br;
					
					$ofx .= "<DTPOSTED>" . date($this->dataDateFormat, strtotime($this->csv[$y][$this->colDate]));
					$ofx .= $this->br;
					
					// remove the 'infuriating' pound sign, add a negative if it's a DEBIT
					if ($trntype == "CREDIT") {
						$trnamt = $this->csv[$y][$this->colCredit];
					} else {
						$trnamt = "-" . $this->csv[$y][$this->colDebit]; 
					}
					
					$ofx .= "<TRNAMT>" . $this->prep($trnamt);
					$ofx .= $this->br;
					
					// FITID must be unique within the institution, md5 seems like a good idea
					$ofx .= "<FITID>" . $this->getFitd($y);
					$ofx .= $this->br;
					
					// Clean and tidy the NAME
					
					$dataName = trim($this->csv[$y][$this->colDescription]);
					if (!is_null($this->colDescription2)) {
						if ($this->csv[$y][$this->colDescription2] != "") { 
							$dataName .= " / " . trim($this->csv[$y][$this->colDescription2]);
						}
					}
					// truncate to 32 characters max - compatibility for MS Money 2004
					$dataName = substr($dataName, 0, 32);
					
					$ofx .= "<NAME>" . $this->prep($dataName);
					$ofx .= $this->br;
					
					$ofx .= "</STMTTRN>";
					$ofx .= $this->br;
				}
			}
		}
		
		$ofx .= "</BANKTRANLIST>";
		$ofx .= $this->br;
		
		$this->ofx .= $ofx;
	}
	
	
	private function getOFX2Transactions()
	{
		$br = $this->br;
		
		// iterate through the array
		$ofx = $this->tab(4) . "<BANKTRANLIST>";
		$ofx .= $this->br;
		
		// <DTSTART><DTEND> - can represent the time/date when the server started/stopped *looking* for the data, not the first/last transction.
		$ofx .= $this->tab(5) . "<DTSTART>".$this->dataDate."</DTSTART>";
		$ofx .= $this->br;
		$ofx .= $this->tab(5) . "<DTEND>".$this->dataDate."</DTEND>";
		$ofx .= $this->br;
		
		
		// the good stuff
		for ($y=$this->rowLoopStarts; $y < count($this->csv); $y++) {
			if ($this->debug) { echo "loop " . $y . "\n"; }
			// full description
			if ($this->csv[$y][$this->colDate] != "") {
				if (!is_null($this->colDescription))
				{
					if ($this->csv[$y][$this->colDescription] != "")
					{
						$fullDescription = $this->csv[$y][$this->colDescription];
					}
				}
				if (!is_null($this->colDescription2))
				{
					if ($this->csv[$y][$this->colDescription2] != "")
					{
						$fullDescription .= " " . $this->csv[$y][$this->colDescription2];
					}
				}
				
				if ($this->substr_count_array($fullDescription, $this->wordsToSkip) == 0) {
					
					// begin transaction syntax
					
					$ofx .= $this->tab(5) . "<STMTTRN>";
					$ofx .= $this->br;
					
					if (($this->csv[$y][$this->colDebit] == "") || ($this->csv[$y][$this->colDebit] == "0"))
					{ 
						$trntype = "CREDIT";
					} else {
						$trntype = "DEBIT";
					}
					
					$ofx .= $this->tab(6) . "<TRNTYPE>" . $trntype . "</TRNTYPE>";
					$ofx .= $this->br;
					
					$ofx .= $this->tab(6) . "<DTPOSTED>" . date($this->dataDateFormat, strtotime($this->csv[$y][$this->colDate])) . "</DTPOSTED>";
					$ofx .= $this->br;
					
					// remove the 'infuriating' pound sign, add a negative if it's a DEBIT
					if ($trntype == "CREDIT") {
						$trnamt = $this->csv[$y][$this->colCredit];
					} else {
						$trnamt = "-" . $this->csv[$y][$this->colDebit]; 
					}
					
					$ofx .= $this->tab(6) . "<TRNAMT>" . $this->prep($trnamt) . "</TRNAMT>";
					$ofx .= $this->br;
					
					// FITID must be unique within the institution, md5 seems like a good idea
					$ofx .= $this->tab(6) . "<FITID>" . $this->getFitd($y) . "</FITID>";
					$ofx .= $this->br;
					
					// Clean and tidy the NAME
					
					$dataName = trim($this->csv[$y][$this->colDescription]);
					if (!is_null($this->colDescription2)) {
						if ($this->csv[$y][$this->colDescription2] != "") { 
							$dataName .= " / " . trim($this->csv[$y][$this->colDescription2]);
						}
					}
					
					$ofx .= $this->tab(6) . "<NAME>" . $this->prep($dataName) . "</NAME>";
					$ofx .= $this->br;
					
					$ofx .= $this->tab(5) . "</STMTTRN>";
					$ofx .= $this->br;
				}
			}
		}
		
		$ofx .= $this->tab(4) . "</BANKTRANLIST>";
		$ofx .= $this->br;
		
		$this->ofx .= $ofx;
	}
	
	
	private function getOFX1Footer()
	{	
		// prepare the OFX footer
		$ofx = "<LEDGERBAL>";
		$ofx .= $this->br;
		
		if ((!is_null($this->rowBalance)) && (!is_null($this->colBalance)))
		{
			$this->balance = $this->csv[$this->rowBalance][$this->colBalance];
		}
		
		$ofx .= "<BALAMT>" . $this->prep($this->balance);
		$ofx .= $this->br;
		$ofx .= "<DTASOF>" . $this->dataDate;
		$ofx .= $this->br;
		$ofx .= "</LEDGERBAL>";
		$ofx .= $this->br;
		
		switch ($this->csvType)
		{
			case "CREDITCARD":
				$ofx .= "</CCSTMTRS>";
				$ofx .= $this->br;
				$ofx .= "</CCSTMTTRNRS>";
				$ofx .= $this->br;
				$ofx .= "</CREDITCARDMSGSRSV1>";
				break;
			
			default:
				$ofx .= "</STMTRS>";
				$ofx .= $this->br;
				$ofx .= "</STMTTRNRS>";
				$ofx .= $this->br;
				$ofx .= "</BANKMSGSRSV1>";
				break;
		}
		
		$ofx .= $this->br;
		$ofx .= "</OFX>";
		
		$this->ofx .= $ofx;
	}
	
	
	private function getOFX2Footer()
	{	
		// prepare the OFX footer
		$ofx = $this->tab(4) . "<LEDGERBAL>";
		$ofx .= $this->br;
		
		if ((!is_null($this->rowBalance)) && (!is_null($this->colBalance)))
		{
			$this->balance = $this->csv[$this->rowBalance][$this->colBalance];
		}
		
		$ofx .= $this->tab(5) . "<BALAMT>" . $this->prep($this->balance) . "</BALAMT>";
		$ofx .= $this->br;
		$ofx .= $this->tab(5) . "<DTASOF>" . $this->dataDate . "</DTASOF>";
		$ofx .= $this->br;
		$ofx .= $this->tab(4) . "</LEDGERBAL>";
		$ofx .= $this->br;
		$ofx .= $this->tab(3) . "</STMTRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(2) . "</STMTTRNRS>";
		$ofx .= $this->br;
		$ofx .= $this->tab(1) . "</BANKMSGSRSV1>";
		$ofx .= $this->br;
		$ofx .= "</OFX>";
		$ofx .= $this->br;
		
		$this->ofx .= $ofx;
	}
	
	
	private function getFitd($y)
	{
		return strtoupper(md5(date($this->dataDateFormat . "u", strtotime($this->csv[$y][$this->colDate])) . $y));
	}
    
    
    // simple methods
    private function tab($n)
    {
    	$output = "";
    	
    	for ($i = 0; $i < $n; $i++)
		{
			$output .= $this->tab;
		}

		return $output;
    }
    
    
	private function substr_count_array($haystack, $needle)
	{
		// count $needle[] in a $haystack
		$count = 0;
		if (!is_null($needle)) {
			foreach ($needle as $substring) 
			{
				$count += substr_count(strtolower($haystack), strtolower($substring));
			}
		}
		return $count;
	}
	
	
	private function prep($string)
	{
		// prepare strings for output
		$output = $string;
		$output = trim($output);
		$output = str_replace($this->wordsToStrip, "", $output);
		$output = htmlspecialchars($output);
		
		return $output;
	}
    
    
    public function getVersion()
    {
    	return self::version;
    }
    
}

?>
