<?php 
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$bypassAuth = true; // allow access to this script even after the deadline
require 'header.php'; // brings in the contacts file and utils file
$adminEmail = ADMIN_EMAIL;

$subId = $_GET['subId'];
$subPwd = $_GET['subPwd'];
// $warn = $_GET['warning'];

$links = show_sub_links();
print <<<EndMark
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="utf-8">
<title>Submission/Revision Receipt</title>
</head>

<body>
$links
<hr />
<h1>Submission/Revision Receipt</h1>

EndMark;

if (empty($subId) || empty($subPwd)) generic_receipt($subId, $subPwd);

$qry = "SELECT *, UNIX_TIMESTAMP(whenSubmitted) sbmtd, UNIX_TIMESTAMP(lastModified) revised FROM {$SQLprefix}submissions WHERE subId = ? AND subPwd = ?";
$row = pdo_query($qry, array($subId,$subPwd))->fetch();

$ttl = htmlspecialchars($row['title']);
$athr = htmlspecialchars($row['authors']);
$affl = htmlspecialchars($row['affiliations']);
$cntct = htmlspecialchars($row['contact']);
$abs = nl2br(htmlspecialchars($row['abstract']));
$cat = htmlspecialchars($row['category']);
$kwrd = htmlspecialchars($row['keyWords']);
$cmnt = nl2br(htmlspecialchars($row['comments2chair']));
$frmt = htmlspecialchars($row['format']);
$aux =  htmlspecialchars($row['auxMaterial']);
$unsupFormat = (substr($row['format'], -12) == '.unsupported');
$status = $row['status'];
$sbmtd = (int) $row['sbmtd'];
$sbmtd = $sbmtd ? utcDate('Y-m-j H:i:s (T)', $sbmtd) : ''; 
$rvsd = (int) $row['revised'];
$rvsd = $rvsd ? utcDate('Y-m-j H:i:s (T)', $rvsd) : '';
$needsStamp = ($row['flags'] & SUBMISSION_NEEDS_STAMP);
$eprint = '';

$checked = ($row['flags'] & FLAG_IS_CHECKED) ? "Yes" : "No";

$checkedtext = $showchecked = '';
if (defined("OPTIN_TEXT")) {
  $showchecked = $checked;
  $checkedtext = OPTIN_TEXT;
}

if (defined('CAMERA_PERIOD') && $status=='Accept') { // camera-ready
  $subRev = 'camera-ready revision';
  $where2go = "the <a href=\"cameraready.php?subId=$subId&amp;subPwd=$subPwd\">
Camera-ready revision</a> page";

  if (defined('IACR')) {
    $qry = "SELECT * FROM {$SQLprefix}acceptedPapers WHERE subId=?";
    $accptd = pdo_query($qry, array($subId))->fetch();
    $eprint = $accptd['eprint'];
  }
} else { // initial submission
  $subRev = 'submission/revision';
  $where2go = "the <a href=\"revise.php?subId=$subId&amp;subPwd=$subPwd\">
Revision</a> or the <a href=\"withdraw.php?subId=$subId&amp;subPwd=$subPwd\">
Withdrawal</A> page";
}

if ($status=='Withdrawn')
  print "<b>Your submission was withdrawn.</b> ";
else
  print "<b>Your $subRev was successful.</b> ";

if ($unsupFormat) {
  print "<b style=\"color: red\">WARNING: Although we received your submission, it was flagged as having an unsupported format ($frmt). Please check with the program chair(s).</b>";
}

if (!empty($eprint) && substr($eprint,0,4)=="xxxx") {
  print "<blockquote>\nYour 1st camera-ready upload was also auto-uploaded to the ePrint archive as submission $eprint. Note that <b>revising your camera-ready version DOES NOT UPDATE the ePrint submission!!</b> You need to update the ePrint subimssion separately with the latest version of your paper.</blockquote>\n\n";
}

print <<<EndMark
<br/><br/>
You can bookmark this page for future references, and use it
to go directly to $where2go for this submission. <i>(This will load the
appropriate form with all the submission details already filled in.)</i>
<br/><br/>
Below are the (up-to-date) details of your submission. You will need the
submission-ID and password if you want to revise or withdraw the
submission. 
EndMark;

if (!defined('REVIEW_PERIOD') || REVIEW_PERIOD!=true) {
  print "An email confirmation was sent to the contact address below. If you do not receive the confirmation email soon, please contact the chair. ";
}
if ((PERIOD<PERIOD_CAMERA) &&(CONF_FLAGS & FLAG_AUTH_CONFLICT))
  $authorConflictLine = 'You can use the following link to <a href="specifyConflicts.php?subId='.$subId.'&amp;subPwd='.$subPwd.'" target="_blank">revise your conflict-of-interest declarations</a>.';
else $authorConflictLine = '';

print <<<EndMark
$authorConflictLine
<hr/>

<table style="text-align: left;" cellspacing="6">
  <tbody>
    <tr>
      <td style="text-align: right; width: 110px;">Submission-ID:</td>
      <td>$subId</td>
    </tr>
    <tr>
      <td style="text-align: right;">Password:</td>
      <td>$subPwd</td>
    </tr>
    <tr>
      <td style="text-align: right;">Title:</td>
      <td>$ttl</td>
    </tr>
    <tr>
      <td style="text-align: right;">Authors:</td>
      <td>$athr</td>
    </tr>

EndMark;
if (USE_AFFILIATIONS) { print '    <tr>
      <td style="text-align: right;">Affiliations:</td>
      <td>'.$affl."</td>
    </tr>\n";
}
print <<<EndMark
    <tr>
      <td style="text-align: right;">Contact:</td>
      <td>$cntct</td>
    </tr>
    <tr style="vertical-align: top;">
      <td style="text-align: right;">Abstract:</td>
      <td style="background: lightgrey;">$abs</td>
    </tr>

EndMark;
if (!empty($cat)) { print '    <tr>
      <td style="text-align: right;">Category:</td>
      <td>'.$cat."</td>
    </tr>\n";
}

if (!empty($frmt)) { 
  $subFileLine =<<<EndMark
<a href="download.php?subId=$subId&amp;subPwd=$subPwd">$frmt</a> (click to download)
EndMark;
} 
else $subFileLine = "<b>No file uploaded yet</b>";

if (isset($aux)) { 
  $subFileLine .=<<<EndMark
    , Supporting material: <a href="download.php?subId=$subId&amp;subPwd=$subPwd&amp;aux=yes">$aux</a> (click to download)
EndMark;
} 

print <<<EndMark
    <tr>
      <td style="text-align: right;">File format:</td>
      <td>$subFileLine</td>
    </tr>
    <tr style="vertical-align: top;">
      <td style="text-align: right;">Key words:</td>
      <td>$kwrd</td>
    </tr>
    <tr style="vertical-align: top;">
      <td style="text-align: right;">Comments:</td>
      <td>$cmnt</td>
    </tr>
    <tr style="vertical-align: top;">
      <td style="text-align: right;">$showchecked</td>
      <td>$checkedtext</td>
    </tr>
    <tr>
      <td style="text-align: right;"></td>
      <td>Submitted $sbmtd, Revised: $rvsd</td>
    </tr>
  </tbody>
</table>
<hr />
$links
</body>
</html>
EndMark;
flush();      // Don't let the user wait while we stamp the file
if ($needsStamp && PERIOD<PERIOD_CAMERA) {
  include_once('stampFiles.php');
  // re-set the "needs stump" flag for this submission
  $qry = "UPDATE {$SQLprefix}submissions SET flags=(flags&(~".SUBMISSION_NEEDS_STAMP.")) WHERE subId=?";
  pdo_query($qry, array($subId),"Cannot mark file as stamped: ");
  stampSubmission($subId,$row['format']); // Stump the file (if possible)
}
exit();

function generic_receipt($subId, $subPwd)
{
  global $links;
  $adminEml = ADMIN_EMAIL;
  print <<<EndMark
Your submission/revision was received, but due to database problems we
currently cannot access its details. An administrator was notified of
the problems and he/she will contact you if there is a need. You can contact
the administrator at <a href="mailto:$adminEml">$adminEml</a>

EndMark;
  if (!empty($subPwd)) {
    print "<br /><br />";
    if (!empty($subId))
      print "The submission was assigned ID $subId and password $subPwd.";
    else {
      print "The submission was assigned password $subPwd.";
      print "Contact the administrator for the submission-ID";
    }
  }
  exit("<hr />\n{$links}\n</body>\n</html>");
}

?>
