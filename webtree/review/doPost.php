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
$threaded= (int) $pcMember[4];

if (isset($_POST['subId'])) { $subId = (int) trim($_POST['subId']); }
else exit("<h1>No Submission specified</h1>");

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1 && (!has_reviewed_paper($revId, $subId) && $disFlag == 2)) exit("<h1>$revName cannot discuss submissions yet</h1>");

if (empty($_POST['subject']) && empty($_POST['comments']))
  exit(); // empty post?

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$qry = "SELECT a.assign FROM {$SQLprefix}submissions s 
      LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=?
      WHERE s.subId=?";
$res = pdo_query($qry, array($revId,$subId,$subId));
if (!($row = $res->fetch(PDO::FETCH_NUM)) || $row[0]<0) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

// Find the latest post-ID for that submission
$qry = "SELECT MAX(postId) FROM {$SQLprefix}posts WHERE subId=$subId";
$lastPost = $db->query($qry)->fetchColumn();

$qry = "INSERT INTO {$SQLprefix}posts SET subId=?,revId=?,";
$prms = array($subId,$revId);
if (isset($_POST['parent'])) {
  $qry .= "parentId=?,";
  $prms[] = trim($_POST['parent']);
}
if (isset($_POST['subject'])) {
  $qry .= "subject=?,";
  $prms[] = trim($_POST['subject']);
}
if (isset($_POST['comments'])) {
  $qry.= "\n comments=?,";
  $prms[] = trim($_POST['comments']);
}
$qry .= "\n whenEntered=NOW()";

pdo_query($qry, $prms);
$newPost = array('depth'  => $_POST['depth'], 
		 'postId' => $db->lastInsertId(),
		 'mine'   => true,
		 'subject'  => $_POST['subject'], 
		 'comments' => $_POST['comments'], 
		 'whenEntered' => utcDate('j/n H:i'),
		 'name' => $revName);

// Touch the entry to update the 'lastModified' timestamp
$qry = "UPDATE {$SQLprefix}submissions SET lastModified=NOW() WHERE subId=?";
pdo_query($qry, array($subId));

// Add this post to list of changes for this submission
$qry = "INSERT INTO {$SQLprefix}changeLog (subId,revId,changeType,description,entered) VALUES (?,?,'Post',?,NOW())";

pdo_query($qry, array($subId,$revId,($pcMember[1].' posted a message')));

// Send the new post by email to reviewers that have this submission
// on their watch list and asked to be notified by email of new posts

$qry = "SELECT c.email, c.flags FROM {$SQLprefix}assignments a, {$SQLprefix}committee c WHERE c.revId=a.revId AND a.subId=? AND a.revId!=? AND a.assign>=0 AND a.watch=1";
$res = pdo_query($qry, array($subId,$revId));
$notify = $comma = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $flags = $row[1];
  if ($flags & FLAG_EML_WATCH_EVENT) {
    $notify .= $comma . $row[0];
    $comma = ', ';
  }
}
if (!empty($notify)) {
  $qry = "SELECT title from {$SQLprefix}submissions WHERE subId=?";
  $row = pdo_query($qry, array($subId))->fetch(PDO::FETCH_NUM);
  $prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
  $msg = $row[0]."\n";
  $msg .= "===============================================================\n"
    . $pcMember[1] . ": " . trim($_POST['subject'])
    . "\n\n" . trim($_POST['comments']) . "\n\n";
  $msg .= "---------------------------------------------------------------\n"
    . "$prot://".BASE_URL."review/discuss.php?subId=$subId\n";
  $sbjct = "New post for submission $subId to ".CONF_SHORT.' '.CONF_YEAR;
  my_send_mail($notify, $sbjct, $msg);
}

if (isset($_POST['ajax'])) { // return HTML sniplet only for this post
  include 'showReviews.php';
  $html = show_posts(array($newPost), $subId, true, $_POST['lastSaw'],
		     $_POST['pageWidth'], isset($_POST['newThread']));
  $json = json_encode(array('html'   => $html, 
			    'postId' => $newPost['postId'],
			    'hasNew' => ($_POST['lastSaw']<$lastPost)));
  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-cache");
  exit($json);
}

// if this was reply to a previous post, return to that post
if (!empty($_POST['parent']))
     $anchor = 'p'.trim($_POST['parent']);
else $anchor = "endDiscuss"; // return to the end of the page

header("Location: discuss.php?subId=$subId#".$anchor);
?>