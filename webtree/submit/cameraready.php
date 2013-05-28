<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file

$cnnct = db_connect();
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
  = $nPages = $copyright = $urlPrms = $eprint = '';

if (!empty($subId) && !empty($subPwd)) {
  $pw = my_addslashes($subPwd, $cnnct);
  $qry = "SELECT title, authors, affiliations, contact, abstract, nPages, copyright, eprint FROM submissions sb LEFT JOIN acceptedPapers ac USING(subId) WHERE sb.subId=$subId AND subPwd='$pw' AND status='Accept'";
  $res=db_query($qry, $cnnct);
  if (!($row = mysql_fetch_row($res))) {
    exit("<h1>Non-Existent Accepted Submission</h1>\n"
	 . "No accepted submission with ID $subId and password $subPwd found");
  }
  $subPwd = htmlspecialchars($subPwd);
  $title = htmlspecialchars($row[0]);
  $authors  = htmlspecialchars($row[1]);
  $affiliations  = htmlspecialchars($row[2]);
  $contact = htmlspecialchars($row[3]);
  $abstract= htmlspecialchars($row[4]);
  $nPages = (int) $row[5];
  $copyright = htmlspecialchars($row[6]);
  $eprint =  htmlspecialchars($row[7]);
  if ($nPages <= 0) $nPages = '';
  $urlPrms = "?subId=$subId&subPwd=$subPwd";
}

// If authors need to submit a copyright file but didn't, ask them to
$file = SUBMIT_DIR."/final/copyright.html";
if (file_exists($file) && empty($copyright) && !$chair) {
  header("Location: copyright.php{$urlPrms}");
  exit();
}

$links = show_sub_links(6);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">
tr { vertical-align: top; }
</style>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>

<script type="text/javascript" src="../common/validate.js"></script>
<script language="Javascript" type="text/javascript">
<!--
function checkform( form )
{
  var pat = /^\s*$/;
  // Checking that all the mandatory fields are present
  st = 0;
  if (pat.test(form.subId.value)) { st |= 1; }
  if (pat.test(form.subPwd.value))   { st |= 2; }

  if (st != 0) {
    alert( "You must specify the submission number and password" );
    if (st & 1) { form.subId.focus(); }
    else if (st & 2) { form.subPwd.focus(); }
    return false;
  }
  return true ;
}
//-->
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

<form name="cameraready" onsubmit="return checkform(this);" action="act-revise.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="referer" value="cameraready.php">
<table cellspacing="6">
<tbody>
  <tr>
    <td style="text-align: right;">
         <small>(*)</small>&nbsp;Submission&nbsp;ID:</td>
    <td> <input name="subId" size="4" type="text"
                value="$subId">
         The submission number, as returned when the paper was first submitted.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Password:</td>
    <td><input name="subPwd" size="11" value="$subPwd" type="text">
        The password that was returned with the original submission.
    </td>
  </tr>

EndMark;

if (empty($subId)) { // put a button to "Load submission details"
  print '  <tr>
    <td></td>
    <td><input value="Reload Form with Submission Details (Submission-ID and Password must be specified)" type="submit" name="loadDetails">
    (<a href="../documentation/submitter.html#camera" target="documentation" title="this button reloads the revision form with all the submission details filled-in">what\'s this?</a>)
    </td>
  </tr>';
}

if (defined('IACR')) { // Specify ePrint report (if exists)
  $ePrintHTML = '<tr>
    <td style="text-align: right;">ePrint&nbsp;report:</td>
    <td><input name="eprint" size="10" type="text" value="'.$eprint.'">
     If this work is available on <a href="http://eprint.iacr.org">ePrint</a>,
     specify the report number using the format <tt>yyyy/nnn</tt>
    </td>
  </tr>';
}
else $ePrintHTML = '';

print <<<EndMark
  <tr>
    <td colspan="2" style="text-align: center;"><hr />
        <big>Any input below will overwrite existing information;
             no input means the old content remains intact.</big><br /><br />
    </td>
  </tr>
  $ePrintHTML
  <tr>
    <td style="text-align: right;">Number&nbsp;of&nbsp;Pages:</td>
    <td><input name="nPages" size="3" type="text" value="$nPages">
     Will be used by the chair to
     automatically generate the table-of-contents and author index.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Title:</td>
    <td><input name="title" size="90" type="text" value="$title"><br/>
        The title of your submission</td>
  </tr>
  <tr>
    <td style="text-align: right;">Authors:</td>
    <td><input name="authors" size="90" type="text" value="$authors"><br/>
        Separate multiple authors with '<i>and</i>' (e.g., Alice First 
	<i>and</i> Bob T. Second <i>and</i> C. P. Third). <br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Affiliations:</td>
        <td><input name="affiliations" size="70" type="text" value="$affiliations">
  </tr>
  <tr>
    <td style="text-align: right;">Contact Email(s):</td>
    <td><input name="contact" size="70" type="text" value="$contact" onchange="return checkEmailList(this)"><br />
    <u><b>Make sure that these are valid addresses</b></u>, they will be used for communication with the publisher.<br/><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Abstract:</td>
    <td><textarea name="abstract" rows="15" cols="80">$abstract</textarea><br/>
        Use only plain ASCII and LaTeX conventions for math, but no HTML tags.
        <br/><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Submission&nbsp;Files: </td>
    <td><input name="pdf_file" size="70" type="file">
        <tt><==</tt> PDF file only<br/>
    <input name="sub_file" size="70" type="file">
    <tt><==</tt> Archive file<br/>
    The archive file (tar, tzg, etc.) must include all the necessary files
    <i>including the PDF file from above</i>.
    See <a href="cameraInstructions.php">the instructions</a>.<br/><br/>
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input value="Submit camera-ready revision" type="submit">
    </td>
  </tr>
</tbody>
</table>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
