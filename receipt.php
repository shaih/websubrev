<?php 
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
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
<title>Submission/Revision Receipt</title>
</head>

<body>
$links
<hr />
<h1>Submission/Revision Receipt</h1>

EndMark;

if (empty($subId) || empty($subPwd)) generic_receipt($subId, $subPwd);
if (!($cnnct = @mysql_connect(MYSQL_HOST, MYSQL_USR, MYSQL_PWD))
    || !@mysql_select_db(MYSQL_DB, $cnnct))
  generic_receipt($subId, $subPwd);

$subId = my_addslashes($subId);
$subPwd = my_addslashes($subPwd);
$qry = "SELECT *, UNIX_TIMESTAMP(lastModified) revised FROM submissions WHERE subId = '{$subId}' AND subPwd = '{$subPwd}'";

if (!($res=@mysql_query($qry, $cnnct)) || !($row=@mysql_fetch_array($res)))
  generic_receipt($subId, $subPwd);

$ttl = htmlspecialchars($row['title']);
$athr = htmlspecialchars($row['authors']);
$affl = htmlspecialchars($row['affiliations']);
$cntct = htmlspecialchars($row['contact']);
$abs = nl2br(htmlspecialchars($row['abstract']));
$cat = htmlspecialchars($row['category']);
$kwrd = htmlspecialchars($row['keyWords']);
$cmnt = nl2br(htmlspecialchars($row['comments2chair']));
$frmt = htmlspecialchars($row['format']);
$unsupFormat = (substr($row['format'], -12) == '.unsupported');
$status = $row['status'];
$rvsd = ((int) $row['revised']);
$rvsd = $rvsd ? date('Y-m-j H:i:s', $rvsd) : ''; 
$sbmtd = htmlspecialchars($row['whenSubmitted']);

if (defined('CAMERA_PERIOD') && $status=='Accept') {
  $subRev = 'camera-ready revision';
  $where2go = "the <a href=\"cameraready.php?subId=$subId&amp;subPwd=$subPwd\">
Camera-ready revision</a> page";
} else {
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
  print "<b>WARNING: Although we received your submission, it was flagged as having an unsupported format ($frmt). Please check with the program chair(s).</b>";
}

print <<<EndMark
<br/><br/>
You can bookmark this page for future references, and use it
to go directly to $where2go for this submission. <i>(This will load the
appropriate form with all the submission details already filled in.)</i>
<br/><br/>
Below are the (up-to-date) details of your submission. You will need the
submission-ID and password if you want to revise or withdraw the
submission. An email confirmation was sent to the contact address below.
If you do not receive the confirmation email soon, contact the administrator
at $adminEmail.
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

print <<<EndMark
    <tr style="vertical-align: top;">
      <td style="text-align: right;">Key words:</td>
      <td>$kwrd</td>
    </tr>
    <tr style="vertical-align: top;">
      <td style="text-align: right;">Comments:</td>
      <td>$cmnt</td>
    </tr>
    <tr>
      <td style="text-align: right;">File format:</td>
      <td>$frmt</td>
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
exit();

function generic_receipt($subId, $subPwd)
{
  global $links;
  $adminEml = ADMIN_EMAIL;
  print <<<EndMark
Your submission/revision was recieved, but due to database problems we
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
