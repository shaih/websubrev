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
<head>
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
$acc = ACCEPT_LTR;
if (empty($acc)) $acc = 'Dear <$authors>,

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
$acc = htmlspecialchars($acc);

// the default reject letter
$rej = REJECT_LTR;
if (empty($rej)) $rej = 'Dear <$authors>,

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

$rej = htmlspecialchars($rej);

// If either $_POST['saveText'] or $_POST['notifySubmitters'] are set
// then store the current text in the database

// If $_POST['notifySubmitters'] is set, send the actual emails
if (isset($_POST['notifySubmitters']) || isset($_POST['saveText'])) {
  $x = trim($_POST['accLetter']);
  if (!empty($x)) $acc = $x;
  $acc = str_replace("\r\n", "\n", $acc); // just in case

  $x = trim($_POST['rejLetter']);
  if (!empty($x)) $rej = $x;
  $rej = str_replace("\r\n", "\n", $rej); // just in case

  $cnnct = db_connect();
  $qry = "UPDATE parameters SET acceptLtr='".my_addslashes($acc,$cnnct)
  . "',\n  rejectLtr='".my_addslashes($rej,$cnnct)
  . "'\n  WHERE version=".PARAMS_VERSION;
  db_query($qry, $cnnct);
}

if (isset($_POST['notifySubmitters'])) {
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
      ob_flush();flush();sleep(1);
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
<form action="notifications.php" enctype="multipart/form-data" method="post">
Use the form below to customize your accept/reject letters. The email
notifications will be sent when you hit the "Send Notification" button
at the bottom of this page. You can also make changes to the text of
these letetrs and then save it without sending the email yet by using
this button: <input type="submit" name="saveText" value="Save Text"><br/>
<br/>
(Note that the keywords <code>&lt;&#36;authors&gt;</code>, 
<code>&lt;&#36;title&gt;</code>,  <code>&lt;&#36;subId&gt;</code>
and <code>&lt;&#36;subPwd&gt;</code> will be replaced by the authors,
title and the submission-ID and password as they appear in the
database. To be recognized as keywords, these words MUST include the
'&lt;' and '&gt;' characters and the dollar-sign.)

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

<input type="submit" name="notifySubmitters" value="Send Notifications">
</form>

<hr />
$links
</body>
</html>

EndMark;
exit();

function notifySubmitters($subId, $title, $authors, $contact, $pwd, $sbjct, $text)
{
  $errMsg = "notification for submission {$subId} to {$contact}";
  $cName = CONF_SHORT.' '.CONF_YEAR;

  $text = str_replace('<$authors>', $authors, $text);
  $text = str_replace('<$title>', $title, $text);
  $text = str_replace('<$subId>', $subId, $text);
  $text = str_replace('<$subPwd>', $pwd, $text);

  my_send_mail($contact, $sbjct, $text, CHAIR_EMAIL, $errMsg);
}
?>
