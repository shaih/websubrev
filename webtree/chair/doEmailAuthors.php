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
$message = str_replace("\r\n", "\n", $message); // eliminate CRLF issues

// If either $_POST['saveText_ACC'] or $_POST['saveText_REJ']
// is set, store the current text in the database

if (isset($_POST['saveText_ACC']) || isset($_POST['saveText_REJ'])) {
  if (isset($_POST['saveText_ACC'])) { // save the accept text
    $subjFld = 'acptSbjct';
    $textFld = 'acceptLtr';
  } else {                             // save the reject text
    $subjFld = 'rjctSbjct';
    $textFld = 'rejectLtr';
  }
  if (!empty($subject)) {
    $qry = "UPDATE parameters SET $subjFld='"
      . my_addslashes($subject,$cnnct)."' WHERE version=".PARAMS_VERSION;
    db_query($qry, $cnnct);
  }
  if (!empty($message)) {
    $qry = "UPDATE parameters SET $textFld='"
      . my_addslashes($message,$cnnct)."' WHERE version=".PARAMS_VERSION;
    db_query($qry, $cnnct);
  }
}

// Handle the special case where you only save the text, not send it
if (isset($_POST['saveOnly'])) return_to_caller('notifications.php');

// To whom should we send this email
$emailTo = isset($_POST["emailTo"])? trim($_POST["emailTo"]) : '';
$cond = "false";
if ($emailTo=="all")     $cond = "status!='Withdrawn'";
else if ($emailTo=="AC") $cond = "status='Accept'";
else if ($emailTo=="MA") $cond = "status='Maybe Accept'";
else if ($emailTo=="DI") $cond = "status='Needs Discussion'";
else if ($emailTo=="NO") $cond = "status='None'";
else if ($emailTo=="MR") $cond = "status='Perhaps Reject'";
else if ($emailTo=="RE") $cond = "status='Reject'";
else if ($emailTo=="these") { // send only to certain submissions
  $IDs = my_addslashes(trim($_POST['subIDs']),$cnnct);
  if (!empty($IDs)) $cond = "subId IN (".$IDs.")";
}

$qry = "SELECT s.subId, subPwd, title, authors, contact,
  comments2authors, confidence, score, attachment
  FROM submissions s LEFT JOIN reports r USING(subId)
  WHERE $cond
  ORDER by s.subId, SHA1(CONCAT('".CONF_SALT."',s.subId,r.revId))";

$res = db_query($qry, $cnnct);

$submissions = array();
while ($row=mysql_fetch_row($res)) {
  $subId = (int) $row[0];
  if ($subId<=0) continue;

  if (!isset($submissions[$subId])) { // a new submission
    $submissions[$subId] = array($row[1], // 0 => password
				 $row[2], // 1 => title
				 $row[3], // 2 => authors
				 $row[4], // 3 => contact
				 array(), // 4 => comments-to-authors
				 array());// 5 => attachmenets
  }
  $comment = trim($row[5]);
  if (!empty($comment) || !empty($row[8])) {
    if (isset($_POST['withGrades']) && $row[7]>0) {
      $score = "Score: ".$row[7];
      if ($row[6]>0) $score .= "\nConfidence: ".$row[6];
      $comment = $score."\n\n".$comment;
    }
    if (!empty($row[8])) {
      $comment .= "\n\nSee attached file ".$row[8]."\n";
    }
    array_push($submissions[$subId][4],wordwrap($comment,78));
  }
  if (!empty($row[8])) {
    $attachment = array(SUBMIT_DIR."/attachments/", $row[8]);
    array_push($submissions[$subId][5], $attachment);
  }
}

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

$outgoing = array();
$count=0;
foreach ($submissions as $subId => $sb) {
  sendEmail2Sub($subId, $sb, $subject, $message);
  $outgoing[] = ($subId.": ".$sb[3]); // remember who you sent email to

  $count++;
  if (($count % 25)==0) { // rate-limiting, avoids cutoff
    print "$count messages sent so far...<br/>\n";
    ob_flush();flush();sleep(2);
  }
}

// Send email to the chair with a summary
$chairname = $chair['name'];
$authors = implode("\n", $outgoing);

//Send a message to the Chairs
$chair_msg = <<<EndMark

A message was sent to the following addresses by $chairname:

$authors

Message:

$message
  
EndMark;

my_send_mail(chair_emails(), $subject, $chair_msg, array(), "Outgoing email to submission authors.");

print <<<EndMark
<br/>
Total of $count messages sent. Check the <a href="viewLog.php">log file</a>
for any errors.
<hr/>
$links
</body>
</html>

EndMark;

function sendEmail2Sub($subId, $sb, $subject, $text)
{
  /* $sb = array(0 => password, 1 => title, 2 => authors, 3 => contact,
   *             4 => comments-to-authors, 5 => attachmenets)
   */

  $subject = str_replace('<$subId>', $subId, $subject);
  $subject = str_replace('<$title>', $sb[1], $subject);

  $text = str_replace('<$subId>',   $subId, $text);
  $text = str_replace('<$subPwd>',  $sb[0], $text);
  $text = str_replace('<$title>',   $sb[1], $text);
  $text = str_replace('<$authors>', $sb[2], $text);

  $contact = $sb[3];
  $errMsg = "email for submission {$subId} to {$contact}";

  $withComments = (strpos($text, '<$comments>')!== false);
  if ($withComments) { // embed comments, attachments in email text
    $cmnts = $sb[4];
    if (is_array($cmnts) && count($cmnts)>0)
      $text = str_replace('<$comments>', implode("\n\n========================================================================\n\n", $cmnts), $text);
    else $text = str_replace('<$comments>', "\nNo Reviewer Comments\n", $text);
    my_send_mail($contact, $subject, $text, chair_emails(), $errMsg, $sb[5]);
  }
  else // send without comments or attachments
    my_send_mail($contact, $subject, $text, chair_emails(), $errMsg);
}

?>
