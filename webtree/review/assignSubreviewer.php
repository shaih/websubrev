<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
$msg = '';
if(isset($_GET['success'])) {
	$msg = "<h2>Success! Your message was sent.</h2>";	
} else if (isset($_GET['fail'])) {
	$msg = "<h2>Please try again, the message did not send </h2>";
} else if (isset($_GET['conflict'])) {
	$msg = "<h2>Message not sent due to suspected conflict </h2>";
}
$cnnct = db_connect();
$qry = "SELECT subId, title, authors FROM submissions WHERE status!='Withdrawn' ORDER by subId";
$res = db_query($qry, $cnnct);
$paperTable = '<table>.<tr>';
$count = 0;
$options = "";
while($subs = mysql_fetch_assoc($res)) {
  $auth = '';
  if (!ANONYMOUS && isset($subs['authors']))
    $auth = ' ('.htmlspecialchars($subs['authors']).')';
  $options .="<option value='".$subs['subId']."'>".$subs['subId'].'. '.$subs['title'].$auth." </option>";
}
$links = show_rev_links(2);
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<style type="text/css">
h1 { text-align: center; }
.available {
   width:48em;
}
   
.selected {
   width:48em;
}
</style>
<title>Send Submissions to External Reviewers</title>
<script type="application/javascript" src="{$JQUERY_URL}"></script>
<script type="application/javascript" src="../common/ui.js"></script>
</head>
<body>
$links
<hr />
$msg
<h1>Send Submissions to External Reviewers</h1>
<p>Use this form to send email to potential external reviewers, asking them
to review submissions to the conference. The submission file itself will
be attached to the email. If you specify more than one email address
below, the same email will  besent to all of them.
</p>

<form name="subreviewer" action="doAssignSubreviewers.php" method="post">
<table><tbody>
<tr><td align="right"><tt>Subject:</tt></td><td><input name="subject" type="text" size="80"></td></tr>
<tr><td align="right" valign="top"><tt>To:</tt>     </td><td><input name="sendTo" type="text" size="80"><br/>
Comma separated list of subreviewer emails, e.g. <tt>person1@university.edu, person2@organization.org</tt>
</td></tr>
</tbody></table>

Email body:<br> <textarea cols='80' rows ='10' name="emailBody"></textarea>


<h4>Select submission to attach to the email.</h4>
<div class="many-select" data-name="subId">
  <select multiple size=10 class="available">
  $options
  </select>
  <a class="add" href="javascript:">Add</a>
  <select multiple class="selected">
  </select>
  <a class="remove" href="javascript:">Remove</a>
  <div class="hidden-selects">
  </div>
</div>

<input name="assignSubreviewers" value="Submit" type="submit">
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
