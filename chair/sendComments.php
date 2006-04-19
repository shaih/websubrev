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
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
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
  $qry = "SELECT s.subId, title, authors, contact, comments2authors, status
  FROM submissions s LEFT JOIN reports r USING(subId)
  WHERE s.status!='Withdrawn'";

  $subIds2send = trim($_POST['subIds2send']);
  if (!empty($subIds2send)) {
    $subIds2send = my_addslashes($subIds2send, $cnnct);
    $qry .= " AND s.subId IN ({$subIds2send})";
  }
  $qry .= " ORDER by s.subId";

  $res = db_query($qry, $cnnct);

  $submissions = array();
  $curId = -1;
  while ($row=mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    if ($subId<=0) continue;

    if (!isset($submissions[$subId])) { // a new submission
      $submissions[$subId] = array($row[1], $row[2],
				   $row[3], array(), trim($row[5]));
    }
    $comment = trim($row[4]);
    if (!empty($comment))
      array_push($submissions[$subId][3], wordwrap($comment, 78));
  }
  print "<h3>Sending comments...</h3>\n";

  foreach ($submissions as $subId => $sb) {
    if (($sb[4]=="Accept") || ($sb[4]=="Reject"))
      sendComments($subId, $sb[0], $sb[1], $sb[2], $sb[3], $ltr);
  }

  print <<<EndMark
Comments sent. Check the <a href="view-log.php">log file</a>
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
will be replaced by the authors and title as they specified by the
submitters and by the list of comments, respectively. To be recognized
as keywords, these words MUST include the '&lt;' and '&gt;' characters
and the dollar-sign.)

<form action="sendComments.php" enctype="multipart/form-data" method="post">
<textarea name="commentsLetter" cols=80 rows=16>$ltr</textarea>

<h3>Send comments to only a few submissions</h3>
To send comments only to certain submissions, put a comma-separated
list of submiddion-IDs in the line below. Leaving the line empty will
send comments to the authors of all the submissions.<br /><br />

Send comments only for these submissions:
<input type="text" name="subIds2send" size="70">
<br /><br />

<input type="submit" value="Send Comments">
<input type="hidden" name="sendComments2Submitters" value="yes">
</form>

<hr />
$links
</body>
</html>

EndMark;
exit();

function sendComments($subId, $title, $authors, $contact, &$cmnts, $text)
{
  $cName = CONF_SHORT.' '.CONF_YEAR;

  $hdr = "From: {$cName} Chair <".CHAIR_EMAIL.">".EML_CRLF;
  $hdr .= "Cc: ".CHAIR_EMAIL.EML_CRLF;
  $hdr .= "X-Mailer: PHP/" . phpversion();

  $text = str_replace('<$authors>', $authors, $text);
  $text = str_replace('<$title>', $title, $text);
  if (is_array($cmnts) && count($cmnts)>0)
    $text = str_replace('<$comments>', implode("\n\n========================================================================\n\n", $cmnts), $text);
  else $text = str_replace('<$comments>', "\nNo Reviewer Comments\n", $text);

  if (ini_get('safe_mode') || !defined('EML_EXTRA_PRM'))
    $success = mail($contact, "Reviewer comments for $cName submission",
		    $text, $hdr);
  else
    $success = mail($contact, "Reviewer comments for $cName submission",
		    $text, $hdr, EML_EXTRA_PRM);

  if (!$success) error_log(date('Y.m.d-H:i:s ') . "Cannot send comments for submission {$subId} to {$contact}. {$php_errormsg}\n", 3, './log/'.LOG_FILE);
}
?>
