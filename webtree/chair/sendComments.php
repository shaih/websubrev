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
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
</style>
<title>Send Comments to Authors</title>
</head>
<body>
$links
<hr />
<h1>Send Comments to Authors</h1>
<h2>$cName</h2>

EndMark;

$ltr = 'Dear <$authors>,

Below please find the reviewer comments on your paper

  "<$title>"

That was submitted to '.$cName.'. Thank you again for submitting
your work to '.$cName.'.

Sincerely,

The program chair(s)
************************************************************************

<$comments>';

// If $_POST['sendComments2Submitters'] is set, send the actual emails
if (isset($_POST['sendComments2Submitters'])) {
  $x = trim($_POST['commentsLetter']);
  if (!empty($x)) $ltr = $x;
  $ltr = str_replace("\r\n", "\n", $ltr); // just in case

  $cnnct = db_connect();
  $qry = "SELECT s.subId, title, authors, contact, comments2authors, status,
    confidence, score, attachment
  FROM submissions s LEFT JOIN reports r USING(subId)
  WHERE s.status!='Withdrawn'";

  $subIds2send = trim($_POST['subIds2send']);
  if (!empty($subIds2send)) {
    $subIds2send = my_addslashes($subIds2send, $cnnct);
    $qry .= " AND s.subId IN ({$subIds2send})";
  }
  $qry .= " ORDER by s.subId";
  $submissions = array();
  $curId = -1;

  $res = db_query($qry, $cnnct);
  while ($row=mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    if ($subId<=0) continue;

    if (!isset($submissions[$subId])) { // a new submission
      $submissions[$subId] = array($row[1], $row[2], $row[3], 
				   array(), NULL, trim($row[5]));
    }
    $comment = trim($row[4]);
    if (!empty($comment)) {
      if (isset($_POST['withGrades']) && $row[7]>0) {
        $grade = "Score: ".$row[7];
        if ($row[6]>0) $grade .= "\nConfidence: ".$row[6];
        $comment = $grade."\n\n".$comment;
      }
     if (!empty($row[8])) {
       $comment .= "\n\nSee attached file ".$row[8]."\n";
     }
      array_push($submissions[$subId][3], wordwrap($comment, 78));
    }
    if (!empty($row[8])) {
      if (!isset($submissions[$subId][4])) $submissions[$subId][4] = array();
      $attachment = array(SUBMIT_DIR."/attachments/", $row[8]);
      array_push($submissions[$subId][4], $attachment);
    }
  }
  print "<h3>Sending comments...</h3>\n";

  $count=0;
  foreach ($submissions as $subId => $sb) {
    if (($sb[5]=="Accept") || ($sb[5]=="Reject")) {
      sendComments($subId, $sb[0], $sb[1], $sb[2], $sb[3], $sb[4], $ltr);
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
Total of $count messages sent. Check the <a href="viewLog.php">log file</a>
for any errors.

<hr />
$links
</body>
</html>

EndMark;
  exit();
}

/********************************************************************/
/********************************************************************/

// Allow the chair to customize the emails
print <<<EndMark
The comments-for-authors will be send when you hit the "Send Comments"
button at the bottom of this page. You can customize the header of
these emails below.<br />
<br />

(Note that the keywords <code>&lt;&#36;authors&gt;</code>,
<code>&lt;&#36;title&gt;</code>, and <code>&lt;&#36;comments&gt;</code>
will be replaced by the authors and title as specified by the
submitters and by the list of comments, respectively. To be recognized
as keywords, these words MUST include the '&lt;' and '&gt;' characters
and the dollar-sign.)

<form action="sendComments.php" enctype="multipart/form-data" method="post">
<textarea name="commentsLetter" cols=80 rows=16>$ltr</textarea>

<h3>Send comments to only a few submissions</h3>
To send comments only to certain submissions, put a comma-separated
list of submission-IDs in the line below. Leaving the line empty will
send comments to the authors of all the submissions.<br /><br />

Send comments only for these submissions:
<input type="text" name="subIds2send" size="70">
<br /><br />

<input type="submit" value="Send Comments">
<input type="hidden" name="sendComments2Submitters" value="yes">
<input type=checkbox name=withGrades value=yes> Check to include score
and confidence in the email sent to the authors
</form>

<hr />
$links
</body>
</html>

EndMark;
exit();

function sendComments($subId, $title, $authors, $contact,
		      $cmnts, $attachments, $text)
{
  $subject = "Reviewer comments for ".CONF_SHORT.' '.CONF_YEAR." submission";
  $errMsg = "comments for submission {$subId} to {$contact}";

  $text = str_replace('<$authors>', $authors, $text);
  $text = str_replace('<$title>', $title, $text);
  if (is_array($cmnts) && count($cmnts)>0)
    $text = str_replace('<$comments>', implode("\n\n========================================================================\n\n", $cmnts), $text);
  else $text = str_replace('<$comments>', "\nNo Reviewer Comments\n", $text);

  my_send_mail($contact, $subject, $text, CHAIR_EMAIL, $errMsg, $attachments);
}
?>
