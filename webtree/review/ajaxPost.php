<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, William Blair, Adam Udi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;

require 'header.php';

$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];
$threaded= (int) $pcMember[4];

if (isset($_POST['subId'])) { $subId = (int) trim($_POST['subId']); }
else exit("1");

// An empty post?
if (empty($_POST['subject']) && empty($_POST['comments']))
  exit();

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1 && (!has_reviewed_paper($revId, $subId) && $disFlag == '2')) exit("Cannot discuss yet");

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$qry = "SELECT a.assign FROM {$SQLprefix}submissions s 
      LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=?
      WHERE s.subId=?";
$res = pdo_query($qry, array($revId,$subId,$subId));
if (!($row = $res->fetch(PDO::FETCH_NUM)) || $row[0]<0) {
  exit("1"); // return error code #1
}

$qry = "INSERT INTO {$SQLprefix}posts SET postId=?,subId=?,revId=?";
$prms = array(0,$subId,$revId);

if (isset($_POST['parent'])) {
  $qry .= ",parentId=?";
  $prms[] = trim($_POST['parent']);
}
if (isset($_POST['subject'])) {
  $qry .= ",subject=?";
  $prms[] = trim($_POST['subject']);
}
if (isset($_POST['comments'])) {
  $qry.= ",\n comments=?";
  $prms[] = trim($_POST['comments']);
}
$qry .= ",\n whenEntered=NOW()";

// get the next available post-ID
$res=pdo_query("SELECT 1+IFNULL(MAX(postId),0) FROM {$SQLprefix}posts");
$prms[0] = $res->fetchColumn();
pdo_query($qry, $prms);
$postId = $prms[0];

// Touch the entry to update the 'lastModified' timestamp
$qry = "UPDATE {$SQLprefix}submissions SET lastModified=NOW() WHERE subId=?";
pdo_query($qry, array($subId));

// Add this post to list of changes for this submission
$qry = "INSERT INTO {$SQLprefix}changeLog (subId,revId,changeType,description,entered) VALUES (?,?,'Post',?,NOW())";
pdo_query($qry, array($subId, $revId,($pcMember[1]." posted a message")));

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
header("Content-Type: application/json");
echo json_encode(array("postId"=>$postId,
		       "subject"=>$_POST['subject'],
		       "comments"=>$_POST['comments'],
		       "parentId"=>$_POST['parent'],
		       "name"=>$revName
		       )
		 );
