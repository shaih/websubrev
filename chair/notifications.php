<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");

$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();
$cmrDdline = utcDate('r (T)', CAMERA_DEADLINE);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
</style>
<title>Generate Accept/Reject Letters</title>
</head>
<body>
$links
<hr />
<h1>Generate Accept/Reject Letters</h1>
<h2>$cName</h2>

EndMark;

// the default accept letter
$acc = 'Dear <$authors>,

It is our pleasure to inform you that your submission

  "<$title>"

was accepted to '.$cName.'. Congratulations.

Please confirm receipt of this email, and provide us with the
(corrected) title and complete list of authors and affiliations.
This will allow us to distribute the list of accepted papers.

The selection of the papers was a challenging and difficult task. The
Program Committee members have put in a significant effort in order to
provide useful feedback to the authors, but due to time constraints, this
was not always possible.

In a few days, we will forward the comments on your paper and the
instructions for producing and sending the final version. You will
need your submission-ID and password in order to upload the camera-ready
version. These are:

Submission-ID: <$subId>
Password:      <$subPwd>

In order to be included in the proceedings, the final version of your paper
must be received no later than '.$cmrDdline.'.
This is a firm deadline.

Thank you very much for contributing to '.$cName.'.
We are looking forward to seeing you at the conference.

Sincerely,

'.$cName.' program chair(s)';


// the default reject letter
$rej = 'Dear <$authors>,

We are sorry to inform you that your submission

  "<$title>"

was not accepted to '.$cName.'.

We received many good submissions, but could only accept a small number
of them to the program.

The selection of the papers was a challenging and difficult task. The
Program Committee members have put in a significant effort in order to
provide useful feedback to the authors, but due to time constraints, this
was not always possible. We will send you the comments on your paper in a
few days. 

Thank you very much for submitting your work to '.$cName.',
and we hope to see you at the conference.

Sincerely,

'.$cName.' program chair(s)';

// If $_POST['notifySubmitters'] is set, send the actual emails
if (isset($_POST['notifySubmitters'])) {
  $x = trim($_POST['accLetter']);
  if (!empty($x)) $acc = $x;
  $acc = str_replace("\r\n", "\n", $acc); // just in case

  $x = trim($_POST['rejLetter']);
  if (!empty($x)) $rej = $x;
  $rej = str_replace("\r\n", "\n", $rej); // just in case

  $cnnct = db_connect();
  $qry = "SELECT subId, title, authors, contact, status, subPwd FROM submissions WHERE status!='Withdrawn'";

  $subIds2notify = trim($_POST['subIds2notify']);
  if (!empty($subIds2notify)) {
    $subIds2notify = my_addslashes($subIds2notify, $cnnct);
    $qry .= " AND subId IN ({$subIds2notify})";
  }
  $res = db_query($qry, $cnnct);

  print "<h3>Sending notification letters...</h3>\n";

  $count=0;
  while ($row = mysql_fetch_row($res)) {
    if ($row[4]=='Accept') {
      notifySubmitters($row[0], $row[1], $row[2], $row[3], $row[5],
		       "Your submission was accepted to {$cName}", $acc);
    }
    // Send a rejection letter only when status='Reject'
    else if ($row[4]=='Reject') {
      notifySubmitters($row[0], $row[1], $row[2], $row[3], $row[5],
		       "Your {$cName} submission", $rej);
    }
    else continue;

    $count++;
    if (($count % 25)==0) { // rate-limiting, avoids cutoff
      print "$count messages sent so far...<br/>\n";
      sleep(1);
    }
  }

  print <<<EndMark
<br/>
Total of $count messages sent. Check the <a href="view-log.php">log file</a>
for any errors.

<hr />
$links
</body>
</html>

EndMark;
  exit();
}

// Allow the chair to customize the emails
print <<<EndMark
Use the form below to customize your accept/reject letters. The email
notifications will be sent when you hit the "Send Notification" button
at the bottom of this page.<br />
<br />

(Note that the keywords <code>&lt;&#36;authors&gt;</code>, 
<code>&lt;&#36;title&gt;</code>,  <code>&lt;&#36;subId&gt;</code>
and <code>&lt;&#36;subPwd&gt;</code> will be replaced by the authors,
title and the submission-ID and password as they appear in the
database. To be recognized as keywords, these words MUST include the
'&lt;' and '&gt;' characters and the dollar-sign.)

<form action="notifications.php" enctype="multipart/form-data" method="post">

<h3>Acceptance letters</h3>
<textarea name="accLetter" cols=80 rows=13>$acc</textarea>

<h3>Rejection letters</h3>
<textarea name="rejLetter" cols=80 rows=13>$rej</textarea>
<br /><br />

<h3>Notify only a few submissions</h3>
To send notifications only to certain submissions, put a comma-separated
list of submission-IDs in the line below. Leaving the line empty will send
notification letters to all the accepted and rejected submissions.<br/>
<br/>
Notify only these submissions:
<input type="text" name="subIds2notify" size="70">
<br /><br />

<input type="submit" value="Send Notifications">
<input type="hidden" name="notifySubmitters" value="yes">
</form>

<hr />
$links
</body>
</html>

EndMark;
exit();

function notifySubmitters($subId, $title, $authors, $contact, $pwd, $sbjct, $text)
{
  $cName = CONF_SHORT.' '.CONF_YEAR;

  $hdr = "From: {$cName} Chair <".CHAIR_EMAIL.">".EML_CRLF;
  $hdr .= "Cc: ".CHAIR_EMAIL.EML_CRLF;
  $hdr .= "X-Mailer: PHP/" . phpversion();

  $text = str_replace('<$authors>', $authors, $text);
  $text = str_replace('<$title>', $title, $text);
  $text = str_replace('<$subId>', $subId, $text);
  $text = str_replace('<$subPwd>', $pwd, $text);

  if (ini_get('safe_mode') || !defined('EML_EXTRA_PRM'))
    $success = mail($contact, $sbjct, $text, $hdr);
  else
    $success = mail($contact, $sbjct, $text, $hdr, EML_EXTRA_PRM);

  if (!$success) error_log(date('Y.m.d-H:i:s ') . "Cannot send notification for submission {$subId} to {$contact}. {$php_errormsg}\n", 3, './log/'.LOG_FILE);
}
?>
