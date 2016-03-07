<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
require 'header.php'; // brings in the contacts file and utils file
$adminEmail = ADMIN_EMAIL;

if (defined('CAMERA_PERIOD')) {
  $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			  $_SERVER['PHP_AUTH_PW'], chair_ids());
  if ($chair === false) {
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Contact the chair to withdraw the submission</h1>");
  }
}

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$subId = (int) trim($_GET['subId']);
$subPwd = $_GET['subPwd'];

if (defined('REVIEW_PERIOD') && REVIEW_PERIOD===true) {
  $noNotify = ', <span style="color:red">but AUTHORS WERE NOT NOTIFIED!</span>';
}
else $noNotify = '.';

$links = show_sub_links(); 
print <<<EndMark
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
tr { vertical-align: top; }
</style>

<title>Withdrawal Confirmation</title>
</head>
<body>
$links
<hr />
<h1>Withdrawal Confirmation</h1>

<b>Submission $subId to $confName has been withdrawn{$noNotify}</b>
<br/>
<br/>
An email confirmation was sent to the contact address below. If you do not
receive the confirmation email in the next few minutes, please contact the
chair.<br/>
<br/>
If you change your mind (or find a way to fix the bug), you can
"un-withdraw" the submission any time before the deadline by going
to the <A href="revise.php?subId=$subId&amp;subPwd=$subPwd">
Revision page</A> and revising anything in the submission.
EndMark;

if (!empty($subId) && !empty($subPwd)) { 
print <<<EndMark
You can also just click the "Oops" button below.
<br /><br />

<!-- A big, bold "Oops button" -->

<form action="act-revise.php" enctype="multipart/form-data" method="post">
<input name="subId" type="hidden" value="$subId">
<input name="subPwd" type="hidden" value="$subPwd">
<input name="reinstate" type="hidden" value="1">
<input 
 value="Oops.. I made a mistake and would like to re-instate the submission"
 type="submit">
</form>
<hr />

EndMark;
} 

if (empty($subId) || empty($subPwd)) generic_confirm();

$qry = "SELECT *, UNIX_TIMESTAMP(whenSubmitted) sbmtd, UNIX_TIMESTAMP(lastModified) revised FROM {$SQLprefix}submissions WHERE subId =? AND subPwd =?";

$row = pdo_query($qry, array($subId,$subPwd))->fetch();

$ttl = htmlspecialchars($row['title']);
$athr = htmlspecialchars($row['authors']);
$affl = htmlspecialchars($row['affiliations']);
$cntct = htmlspecialchars($row['contact']);
if (defined('REVIEW_PERIOD') && REVIEW_PERIOD===true) {
  $cntct .= ' (BUT WITHDRAWAL EMAIL WAS SENT ONLY TO CHAIR)';
}
$abs = nl2br(htmlspecialchars($row['abstract']));
$cat = htmlspecialchars($row['category']);
$kwrd = htmlspecialchars($row['keyWords']);
$cmnt = nl2br(htmlspecialchars($row['comments2chair']));
$frmt = htmlspecialchars($row['format']);
$status = $row['status'];
$sbmtd = (int) $row['sbmtd'];
$sbmtd = $sbmtd ? utcDate('Y-m-j H:i:s (T)', $sbmtd) : ''; 
$rvsd = (int) $row['revised'];
$rvsd = $rvsd ? utcDate('Y-m-j H:i:s (T)', $rvsd) : ''; 

print "<br/>\n";
if ($status!='Withdrawn')
  print "<b>Submission was not withdrawn</b>, did you get here by mistake?\n";
else
  print "Below are the details of your withdrawn submission.\n";
     
print <<<EndMark

<table style="text-align: left;" cellspacing="6">
  <tbody>
    <tr>
      <td style="text-align: right;">Submission-ID:</td>
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
if (USE_AFFILIATIONS) {
  print '    <tr>
      <td style="text-align: right;">Affiliations:</td>
      <td>'.$affl."</td>
    </tr>\n";
}
print <<<EndMark
    <tr>
      <td style="text-align: right;">Contact:</td>
      <td>$cntct</td>
    </tr>
    <tr>
      <td style="text-align: right;">Abstract:</td>
      <td style="background: lightgrey;">$abs</td>
    </tr>
    <tr>
      <td style="text-align: right;">Category:</td>
      <td>$cat</td>
    </tr>
    <tr>
      <td style="text-align: right;">Key words:</td>
      <td>$kwrd</td>
    </tr>
    <tr>
      <td style="text-align: right;">Comments:</td>
      <td>$cmnt</td>
    </tr>
    <tr>
      <td style="text-align: right;">File format:</td>
      <td>$frmt</td>
    </tr>
    <tr>
      <td style="text-align: right;"></td>
      <td>Submitted $sbmtd, Withdrawn: $rvsd</td>
    </tr>
  </tbody>
</table>
<hr />
$links
EndMark;

print "</body>\n</html>\n";
exit();

function generic_confirm()
{
  global $links;
  print "Due to database problems we currently cannot access the details";
  print "of your withdrawn submission.\n"; 
  print "<hr />\n{$links}\n</body>\n</html>\n";
  exit();
}
?>
