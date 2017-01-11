<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file
include_once '../includes/ePrint.php';

$confName = CONF_SHORT . ' ' . CONF_YEAR;
if (PERIOD<PERIOD_CAMERA)
     die("<h1>Final-version submission site for $confName is not open</h1>");

$chairNotice = '';
if (PERIOD>PERIOD_CAMERA)
  $chairNotice = "<b>Notice: only the PC chair can use this page after the deadline.</b><br/>\n";

$h1text = "<h1>Camera-Ready Revision for $confName</h1>";
$timeleft = show_deadline(CAMERA_DEADLINE);
$deadline = 'Deadline is '. utcDate('r (T)', CAMERA_DEADLINE);

$subId = isset($_GET['subId']) ? ((int)trim($_GET['subId'])) : '';
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';
$title = $authors = $affiliations = $contact = $abstract 
  = $nPages = $copyright = $urlPrms = $authorIDs = $eprint = '';

if (!empty($subId) && !empty($subPwd)) {
  $qry = "SELECT title, authors, affiliations, contact, abstract, nPages, copyright, eprint, authorIDs FROM {$SQLprefix}submissions sb LEFT JOIN {$SQLprefix}acceptedPapers ac USING(subId) WHERE sb.subId=? AND subPwd=? AND status='Accept'";
  $res=pdo_query($qry, array($subId,$subPwd));
  if (!($row = $res->fetch(PDO::FETCH_NUM))) {
    exit("<h1>Non-Existent Accepted Submission</h1>\n"
	 . "No accepted submission with ID $subId and password $subPwd found");
  }
  $subId = (int) $subId;
  $urlPrms = "?subId=$subId&subPwd=$subPwd";
  $subPwd = htmlspecialchars($subPwd);
  $title = htmlspecialchars($row[0]);
  $authors  = explode('; ',htmlspecialchars($row[1]));
  $affiliations  = explode('; ',htmlspecialchars($row[2]));
  $contact = htmlspecialchars($row[3]);
  $abstract= htmlspecialchars($row[4]);
  $nPages = (int) $row[5];
  $copyright = htmlspecialchars($row[6]);
  $eprint =  htmlspecialchars($row[7]);
  $authorIDs     = explode('; ',htmlspecialchars($row[8]));
  if ($nPages <= 0) $nPages = '';
}
else $subId=$subPwd='';

// If authors need to submit a copyright file but didn't, ask them to
$file = SUBMIT_DIR."/final/copyright.html";
if (file_exists($file) && empty($copyright) && !$chair) {
  header("Location: copyright.php{$urlPrms}");
  exit();
}

if (defined('IACR')) { // Specify ePrint report (if exists)
  $guessHTML = '';
  if (empty($eprint) && !empty($title)) { // try to search ePrint for title
    if (function_exists('search_ePrint'))
      $eprint=search_ePrint($title);
    if (!empty($eprint)) { // found something, tell user that it's a guess
      $eprintURL = "http://eprint.iacr.org/$eprint";
      $guessHTML ="<br/><b style='color:red'>Found ePrint report with this
        title, please check that this is the right report by clicking</b>
        <a target='_blank' href='$eprintURL'>$eprintURL<a/>";
    }
  }

  $ePrintHTML = '<tr><td style="text-align: right;">ePrint&nbsp;report:</td>
  <td><input name="eprint" ID="ePrint" size="10" type="text" value="'
    .$eprint.'">
    If this work is available on <a href="http://eprint.iacr.org">ePrint</a>,
    specify the report number using the format <tt>yyyy/nnn</tt>'.
    "\n $guessHTML</td>\n</tr>";
  $onSubmit = "onsubmit='return check_ePrint();'";
}
else $ePrintHTML = $onSubmit = '';

$links = show_sub_links(6);
print <<<EndMark
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
<link rel="stylesheet" type="text/css" href="../common/saving.css"/>
<style type="text/css">
h3 { text-align: center; color: blue; }
tr { vertical-align: top; }
</style>
<link rel="stylesheet" type="text/css" href="$JQUERY_CSS"> 
<script src="$JQUERY_URL"></script>
<script src="$JQUERY_UI_URL"></script>
<script src="../common/ui.js"></script>
<script src="../common/authNames.js"></script>
<script>
function check_ePrint() {
  var fld = document.getElementById("ePrint");
  var pdf = document.getElementById("PDFfile");
  if (fld && fld.value=="" &&  pdf.value) {
    return confirm("no ePrint report specified, a new report with the camera-ready file will be posted to ePrint!");
  } else {
    return true;
  }
}
</script>
<title>Camera-Ready Revision for $confName</title>
</head>
<body>
$links
<hr />
$chairNotice
$h1text
<h3 class=timeleft>$deadline<br/>
$timeleft</h3>

<form name="cameraready" action="act-revise.php" enctype="multipart/form-data" method="POST" accept-charset="utf-8" $onSubmit>
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="referer" value="cameraready.php">
<table cellspacing="6">
<tr><td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;ID:</td>
  <td><input name="subId" size="10" type="text" value="$subId">
    The submission number, as returned when the paper was first submitted.</td>
</tr><tr>
  <td style="text-align: right;"><small>(*)</small>&nbsp;Password:</td>
  <td><input name="subPwd" size="10" value="$subPwd" type="text">
    The password that was returned with the original submission.</td>
</tr>
EndMark;

if (empty($subId)) { // put a button to "Load submission details"
  print '<tr>
  <td></td>
  <td><input value="Reload Form with Submission Details (Submission-ID and Password must be specified)" type="submit" name="loadDetails">
    (<a href="../documentation/submitter.html#camera" target="documentation" title="this button reloads the revision form with all the submission details filled-in">what\'s this?</a>)
  </td>
</tr>
</table>
</form>
<hr />'.$links.'
</body>
</html>';
  exit();
}

print <<<EndMark
$ePrintHTML
<tr><td style="text-align: right;"><small>(*)</small>&nbsp;Number&nbsp;of&nbsp;Pages:</td>
  <td><input name="nPages" size="3" type="text" value="$nPages" class="required"> Will be used by the chair to generate the table-of-contents and author index.</td>
</tr><tr>
  <td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;Title:</td>
<td><input name="title" size="90" type="text" value="$title" class="required">
</td></tr><tr>
<td style="text-align: right;"><small>(*)</small>&nbsp;Contact Email(s):</td>
<td><input name="contact" size="90" type="text" value="$contact" class="required"><br/>
  Comma-separated list of email addresses of the form user@domain</td>
</tr><tr><td colspan="2" style="border-bottom: 1px black solid;"></td></tr>
<tr>
<tbody style="border: 4;" id="authorFields"> <!-- Grouping together the author-related fields -->
  <td style="text-align: right;"><small>(*)</small>&nbsp;<b>Authors:</b><br/>
  <a href='../documentation/submitter.html#cryptodb' target='_blank'>CryptoDB help</a>&nbsp;</td>
  <td>List authors in the order they appear on the paper, using names of the form <tt>GivenName M. FamilyName</tt>.
<ol class="authorList compactList">

EndMark;

$nAuthors = count($authors);
if (isset($_GET['nAuthors']) && $_GET['nAuthors']>$nAuthors)
  $nAuthors = (int) $_GET['nAuthors'];
for ($i=0; $i<$nAuthors; $i++) {
  $name= isset($authors[$i])?      $authors[$i]:      '';
  $aff = isset($affiliations[$i])? $affiliations[$i]: '';
  $authID = isset($authorIDs[$i])? $authorIDs[$i]:    '';
  if (defined('IACR')) {
    $chk = !empty($authID)? " checked='checked'" : "";
    $shown = !empty($authID)? "shown" : "hidden";
    $idLine ="<br/><input type='checkbox' name='authChk[]' class='authChk' value='on' title='UNcheck if author does not have a record in CryptoDB'{$chk}>
  Author has a record in CryptoDB with autor-ID:<input type='text' size='3' name='authID[]' class='authID' value={$authID}>
  <a href='https://www.iacr.org/cryptodb/data/author.php?authorkey={$authID}' class='authLink $shown' target='_blank' title='Lookup this author in CryptoDB'> Is this the right author?</a>";
  } else {
    $idLine = "<input type='hidden' name='authID[]' class='authID' value='$authID'>";
  }
  print "  <li class='oneAuthor' style='margin-top:10px;'>
  Name:<input name='authors[]' size='42' type='text' class='author' value='$name'>,
  Affiliations:<input name='affiliations[]' size='32' type='text' class='affiliation' value='$aff'>
  $idLine</li>\n";
}
if ($subId>0 && !empty($subPwd))
  $url = "./cameraready.php?subId={$subId}&subPwd={$subPwd}&nAuthors=".($nAuthors+3);
else 
  $url = "./cameraready.php?nAuthors=".($nAuthors+3);
print <<<EndMark
</ol>
<a style="float: right;" class="moreAuthors" href="$url" rel="$nAuthors">more authors</a><br/>
If the list above is not empty, it will replace the curret author list even if these lists have different number of authors.
</td></tr>
</tr><tr><td colspan="2" style="border-bottom: 1px black solid;"></td></tr>
</tbody style="border: 2px;"> <!-- End of group of author-related fields -->
<tr><td style="text-align: right;"><small>(*)</small>&nbsp;Abstract:</td>
  <td><textarea name="abstract" rows="15" cols="80" class="required">$abstract</textarea><br/></td>
</tr><tr>
<td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;Files:</td>
<td><input name="pdf_file" ID="PDFfile" size="70" type="file"><tt><==</tt> PDF file only<br/>
    <input name="sub_file" size="70" type="file"><tt><==</tt> Archive file<br/>
   The archive file (tar, tzg, etc.) must include all the necessary files
   <i>including the PDF file from above</i>.<br/>
    See <a href="cameraInstructions.php">the instructions</a>.</td>
</tr><tr>
<td></td><td><input value="Submit camera-ready revision" type="submit"></td>
</tr>
</table>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
