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

if (isset($_POST['postId'])) { $postId = (int) trim($_POST['postId']); }
else exit("<h1>No post specified</h1>");

// Make sure that this post exists and it is by this reviewer
$qry = "SELECT subId, revId FROM {$SQLprefix}posts WHERE postId=?";
//if (!is_chair($revId)) {
  $qry .= " AND revId=$revId";
//}
$res = pdo_query($qry, array($postId));
if (!($post = $res->fetch(PDO::FETCH_NUM))) {
	exit("<h1>Post not found</h1>");
}
$subId = $post[0];

// Check that this reviewer is allowed to discuss submissions
if ($disFlag < 1 || ($disFlag == 2 && !has_reviewed_paper($revId,$subId)))
  exit("<h1>$revName cannot discuss this submissiont</h1>");

$postRevId = $post[1];
$qry = "UPDATE {$SQLprefix}posts SET subject=?, comments=? WHERE postId=? AND revId=?";
pdo_query($qry, array(trim($_POST['subject']), trim($_POST['comments']),
		      $postId, $postRevId));
header("Location: discuss.php?subId=$subId");
?>
