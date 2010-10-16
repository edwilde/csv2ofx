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
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="keywords" content="Nationwide, OFX, convert, bank, financial, file" /><meta name="description" content="Convert statement text files downloaded from Nationwide Bank's online banking system to Open Financial Exchange (OFX) files." />
<title>Convert Nationwide Bank Statements to Open Financial Exchange (OFX)</title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" href="css/screen.css" type="text/css" media="screen, projection" />
<script type="text/javascript" src="http://www.google-analytics.com/ga.js"></script><script type="text/javascript">try { var pageTracker = _gat._getTracker("UA-308705-12"); pageTracker._initData(); pageTracker._trackPageview(); } catch (error) {}</script>
<script type="text/javascript" src="js/min.js"></script>
<style type="text/css">
input
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
	<div id="disqus_thread" class="span-11 append-1 last"></div><script type="text/javascript" src="http://disqus.com/forums/edwildecomnationwide2ofx/embed.js"></script><noscript><a href="http://disqus.com/forums/edwildecomnationwide2ofx/?url=ref">View the discussion thread.</a></noscript><a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>
	</div>
	<?php } ?>
</div></form>
<?php if (!$clean) { ?>
<div class="prepend-1 span-9 last quiet">
<h3>Privacy</h3>
<h4>What you are <b>not</b> sharing</h4>
<ul>
<li>Your name, address or any other personal identification</li>
<li>Your full account number</li>
<li>Your sort code</li>
</ul>
<h4>What you are sharing</h4>
<ul>
<li>Your transactions, including amount and <abbr title="In some cases, description can include account numbers. Editing these out before sending is fairly simple using Excel or a simple text editor">description</abbr></li>
<li>Your account balance</li>
</ul>
<h4><b>The file you upload is deleted by the time you receive the OFX file (immediately).</b></h4>
<p>if you are not comfortable with this, don't use the service and <a href="http://www.nationwide.co.uk/contact_us/making_a_complaint/making_a_complaint.htm">complain to Nationwide</a> - they are the ones who <a href="http://www.nationwide.co.uk/troubleshooting/microsoftmoney/microsoftmoney.htm#q3">have told their customers</a> to put trust in a third party to put their financial data into OFX format.</p><p>The privacy concerns above will be the same for *any* conversion service or application.  I assure you that I have no interest whatsoever in looking at other peoples finances, I am not an accountant and never want to be.</p>
<h3>Problems?!</h3>
<p>If something hideous happens, you can find me as <a href="http://www.twitter.com/edwilde/">@edwilde on Twitter</a> or leave a comment here so others can see.</p>
<h3>Action</h3>
<p>I would still prefer that Nationwide brought back their direct OFX service, so please keep the complaints flowing.  Send a secure email, <a href="http://www.nationwide.co.uk/contact_us/making_a_complaint/making_a_complaint.htm">complain over the phone</a>, write a letter to <a href="http://www.nationwide.co.uk/search/DisplayArticle.aspx?article=1380">head office</a>, attend a <a href="https://www.nationwide-members.co.uk/talkbacks">talkback session</a> or complain to the FSA.</p><p>Follow the progress at <a href="http://betteronlinebanking.co.uk">betteronlinebanking.co.uk</a>.</p>
<h3>Disclaimer</h3>
<p>This converter is in no way associated with Nationwide Bank or <a href="http://www.wesabe.com/">Wesabe</a>.</p>
</div>

</div>
<?php } ?>
<?php
// page footer
?>
</div>
<?php if (!$clean) { ?>
<script type="text/javascript">//<![CDATA[(function() {	var links = document.getElementsByTagName('a');	var query = '?';	for(var i = 0; i < links.length; i++) {	if(links[i].href.indexOf('#disqus_thread') >= 0) {		query += 'url' + i + '=' + encodeURIComponent(links[i].href) + '&';	}	}	document.write('<script charset="utf-8" type="text/javascript" src="http://disqus.com/forums/edwildecomnationwide2ofx/get_num_replies.js' + query + '"></' + 'script>');})();//]]></script>
<?php } ?>
</body>
</html>
<?php
}
?>