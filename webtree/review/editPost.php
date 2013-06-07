<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)
$revId = (int) $pcMember[0];
$postId = (int) $_GET['postId'];

$qry = "SELECT p.subId, p.subject, p.comments, s.title FROM {$SQLprefix}posts p, {$SQLprefix}submissions s WHERE p.postId=? AND s.subId=p.subId";
//if (!is_chair($revId)) {
    $qry .= " AND p.revId = $revId";
//}
$post = pdo_query($qry, array($postId))->fetch(PDO::FETCH_NUM)
  or exit("<h1>Post not found</h1>");

$subId = $post[0];
$subject = htmlspecialchars($post[1]);
$comments = htmlspecialchars($post[2]);
$title = htmlspecialchars($post[3]);

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<title>Edit a Post for Submission $subId</title>
</head>
<body>
$links
<hr />
<center>
<h1>Edit a Post for Submission $subId</h1>
<h3><a href="submission.php?subId=$subId">$title</a></h3>
</center>

Please <b>DO NOT OVERUSE THIS FEATURE!</b> The message boards are
supposed to be "append only", and editing existing posts may lead to
confusion. This feature is meant for correcting typos (or in case you
violated confidentiality by mistake). Try to use it as little as
possible.<br/>
<br/>
<form accept-charset="utf-8" action="doEditPost.php" enctype="multipart/form-data" method="post">
Subject:&nbsp;&nbsp;<input style="width: 91%;" type="text" name="subject" value="$subject">
<br/><textarea style="width: 100%;" rows="9" name="comments">$comments</textarea>
<br/><input type="submit" value="Edit Post">
<input type="hidden" name="postId" value="$postId">
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
