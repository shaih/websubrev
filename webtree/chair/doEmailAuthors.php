<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';
$cnnct = db_connect();

$subject = trim($_POST['subject']);
$message = trim($_POST['message']);
$message = str_replace("\r\n", "\n", $message);

$stts = (PERIOD>=PERIOD_CAMERA)? "status='Accept'" : "status!='Withdrawn'";
$qry = "SELECT subId, subPwd, title, authors, contact FROM submissions WHERE $stts";
$res = db_query($qry, $cnnct);
$count=0;

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Email to Authors Sent</title>
</head>
<body>
$links
<hr/>

EndMark;

while ($row = mysql_fetch_row($res)) {
  $subId = (int) $row[0];
  $subPwd   = $row[1];
  $title = $row[2];
  $authors = $row[3];
  $contact = $row[4];

  $text = str_replace('<$subId>', $subId, $message);
  $text = str_replace('<$subPwd>', $subPwd, $text);
  $text = str_replace('<$authors>', $authors, $text);
  $text = str_replace('<$title>', $title, $text);
  my_send_mail($contact, $subject, $text, CHAIR_EMAIL,
	       "email for subID $subId, contact $contact");

  $count++;
  if (($count % 25)==0) { // rate-limiting, avoids cutoff
    print "$count messages sent so far...<br/>\n";
    ob_flush();flush();sleep(1);
  }
}
print <<<EndMark
<br/>
Total of $count messages sent. Check the <a href="viewLog.php">log file</a>
for any errors.
<hr/>
$links
</body>
</html>

EndMark;
?>
