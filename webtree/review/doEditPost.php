<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, discussFlag)
$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];

$cnnct = db_connect();

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1 && (!has_reviewed_paper($revId, $subId) && $disFlag == 2)) exit("<h1>$revName cannot discuss submissions yet</h1>");
if (isset($_POST['postId'])) { $postId = (int) trim($_POST['postId']); }
else exit("<h1>No post specified</h1>");

// Make sure that this post exists and it is by this reviewer
$qry = "SELECT subId, revId FROM posts WHERE postId=$postId";
//if (!is_chair($revId)) {
  $qry .= " AND revId = $revId";
//}
$res = db_query($qry, $cnnct);
if (!($post = mysql_fetch_row($res))) {
	exit("<h1>Post not found</h1>");
}
$subId = $post[0];
$postRevId = $post[1];
$subject = my_addslashes(trim($_POST['subject']), $cnnct);
$comments= my_addslashes(trim($_POST['comments']), $cnnct);
$qry = "UPDATE posts set subject='$subject', comments='$comments' WHERE postId=$postId AND revId=$postRevId";
db_query($qry, $cnnct);
header("Location: discuss.php?subId=$subId");
?>
