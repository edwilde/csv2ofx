<?php set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] ); ?><?php
$debug = false;
if ($debug) { ini_set('display_errors',1); }

// am I local
$local = false;
$path = "";
if (substr_count($_SERVER['HTTP_HOST'], 'powerbook')) { $local = true; }
if (!$local) { $path = "includes/"; }

// get the class
require_once($path.'classes/csv2ofx.class.php');
$csv2ofx = new csv2ofx();

if ($debug) { $csv2ofx->debug = true; } else { $csv2ofx->debug = false; }

$clean = false;
if (isset($_GET["clean"])) { $clean = true; }

$submitted = false;
if (isset($_POST["submitted"])) { $submitted = true; }

// is form submitted?
if ($submitted) {

// find & deal with the CSV
$csv2ofx->parseCSV($_FILES['uploadedFile']['tmp_name']);

// set the csvType
$csv2ofx->csvType = $_POST["accountType"];

if (!$local) { $path = ""; } else { $path = "maps/"; }
// include the map
require_once($path.'map.NationwideUK.php');

// set the account number	
$csv2ofx->accountNumber = $_POST["accountNumber"];

$csv2ofx->ofxType = $_POST["ofxType"];

if (!$debug) {
	// set the headers
	header('Content-type: text/ofx');
	// set the download filename
	$OFXFilename .= strtolower($csv2ofx->bankName);
	$OFXFilename .= '-';
	$OFXFilename = strtolower($csv2ofx->csvType);
	if ($csv2ofx->isIsa) { $OFXFilename .= '-isa'; }
	$OFXFilename .= '-';
	$OFXFilename .= $csv2ofx->accountNumber;
	$OFXFilename .= '-';
	$OFXFilename .= date("dmY");
	$OFXFilename .= '.ofx"';
	header('Content-Disposition: attachment; filename="' . $OFXFilename);
}


// output
print $csv2ofx->getOFX();

} else {

// page header
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>Convert Nationwide Bank Statements to Open Financial Exchange (OFX)</title>

<style type="text/css">
input, select
{
	font-size:18px;
	margin-top:0 !important;
	width:68px;
	text-align: right;
}

input.radio
{
	width: auto !important;
}

input#uploadedFile
{
	width: 92px;
}	

input#submit
{
	width: 270px;
	text-align: center;	
}

select
{
	width: 190px;
	text-align: left;

}

#ofxType1Label,
#ofxType2Label
{
	line-height: 1.1em;
	margin-top: 10px;
}
</style>
</head>
<body>
<div class="container">
<hr class="space"/>
<div id="header" class="span-22 append-2 last">
<div class="span-15"><h1>Nationwide CSV to OFX Converter</h1></div>
<div id="version" class="span-7 last"><br/>version <?php echo $csv2ofx->getVersion(); ?><?php if (!$clean) { ?><br/><a href="?clean=me">too much clutter? use the minimalist version</a><?php } ?></div>
<div class="span-14 last"><p class="quiet">Provide the last four digits of the account number you downloaded the <abbr title="Comma Seperated Variable / Text File">CSV</abbr> from and upload the full statement (not the mini-statement) for your current account, savings, isa or credit card.</p></div>
</div>
<?php
// create the form
?>
<form id="form" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
<div>	<input type="hidden" name="MAX_FILE_SIZE" value="204800" />
	<input type="hidden" name="submitted" value="true" />
</div>
<hr class="space"/><hr class="space"/>
<div class="prepend-1 span-13 <?php if (!$clean) { echo "border"; } ?> large">

	<div id="account" class="span-12 last">
		<div class="span-4"><label for="accountNumber" id="accountNumberLabel">Last Four Digits</label></div>
		<div class="span-7 last"><input id="accountNumber" type="text" name="accountNumber" value="" maxlength="4"/></div>	</div>
	<hr class="space"/>
	<hr class="space"/>
	<div id="type" class="span-12 last">
		<div class="span-4"><label for="accountType" id="accountTypeLabel">Account Type</label></div>
		<div class="span-7 last">
			<select id="accountType" name="accountType">
				<option value="CHECKING">Current account</option>
				<option value="CREDITCARD">Credit Card</option>
				<option value="SAVINGS">Savings (inc. ISA)</option>
			</select>
		</div>
	</div>
	<div id="upload" class="span-12 last">		<div class="span-4"><label for="uploadedFile" id="uploadedFileLabel">Full Statement</label></div>
		<div class="span-7 last"><input id="uploadedFile" name="uploadedFile" type="file" size="9"/></div>
	</div>
	<hr class="space"/>
	<div id="type" class="span-12 last">
		<div class="span-4"><label for="ofxType" id="ofxTypeLabel">OFX version</label></div>
		<div class="span-1"><input id="ofxType1" type="radio" name="ofxType" value="1" class="radio" /></div>
		<div class="span-3 small" id="ofxType1Label">OFX 1.0.2 <div class="quiet">for microsoft money</div></div>
		<div class="span-1"><input id="ofxType2" type="radio" name="ofxType" value="2" class="radio" checked="checked" /></div>
		<div class="span-3 last small" id="ofxType2Label">OFX 2.1.1 <div class="quiet">for everything else</div></div>
	</div>
	<hr class="space"/><hr class="space"/>
	<div id="download" class="span-12 last">		<div class="prepend-4 span-7"><input id="submit" type="submit" value="Download OFX" /></div>
	</div>
	<?php if (!$clean) { ?>
	<div class="span-13">
	<hr class="space"/><hr class="space"/>
	<hr class="prepend-1 span-12 append-1"/>
	<hr class="space"/><hr class="space"/>

	<?php } ?>
</div></form>
<?php
// page footer
?>
</div>

</body>
</html>
<?php
}
?>