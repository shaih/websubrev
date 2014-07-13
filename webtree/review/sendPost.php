<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];
$isChair = is_chair($revId);

if (isset($_GET['pid'])) { $pid = (int) trim($_GET['pid']); }
else exit("<h1>No Post specified</h1>");

// Get the relevant post from database
$qry = "SELECT sb.title,sb.subId,pst.subject,pst.comments,(pst.revId=?) mine
  FROM {$SQLprefix}submissions sb, {$SQLprefix}posts pst
  WHERE pst.postId=? AND sb.subId=pst.subId";
$post = pdo_query($qry,array($revId,$pid))->fetch(PDO::FETCH_ASSOC);
if (!$post || (!$post['mine'] && !$isChair))
  exit("<h1>Post not found or not mine</h1>");

// Check that this reviewer is allowed to discuss submissions
$subId = (int) $post['subId'];
if (!$disFlag || ($disFlag == 2 && !has_reviewed_paper($revId, $subId)))
  exit("<h1>$revName cannot discuss submissions yet</h1>");

$title = htmlspecialchars($post['title']);
$subject = htmlspecialchars($post['subject']);
$comments = htmlspecialchars($post['comments']);
$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<title>Send comment by email</title>
</head>
<body>
$links
<hr/>

<h1>Send comment by email</h1>
<h2>Submission $subId: $title</h2>
<p>
Use this form to send the following discussion-board comment by email,
either to the authors of submission $subId or to sub-reviewers. You can
edit the subject line and text of this email, but please do it lightly
so as to maintain as much of the content of the original comment.</p>
<p>
The receiver will be able to respond to your email, and the response
will appear as part of the discussion on the discussion-board for
submission $subId.
</p>

<form action="doSendPost.php" accept-charset="utf-8" enctype="multipart/form-data" method="POST">
<p>
<input type="hidden" name="pid" value="$pid">
Email subject: <input size="80" type="text" name="subject" value="Comment/question for $confName submission $subId">
</p>
<p>
<b>$subject</b><br/>
<textarea name="msg" cols="80" rows="15">
$comments
</textarea>
</p>
<table>
<tr><td><input type="submit" value="Send comment to"></td><td>
  <input type="radio" name="sendTo" value="authors"> the authors (subject to chair&prime;s approval)<br/>
  <input type="radio" name="sendTo" value="others" checked="checked"> this email address 
  <input size="60" type="text" name="email">
</td></tr>
</table>
</form>
<hr/>
$links
</body>
</html>

EndMark;
?>
