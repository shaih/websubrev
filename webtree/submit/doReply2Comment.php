<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$bypassAuth = true; // allow access to this script after the deadline
require 'header.php'; // brings in the constants and utils files

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$mid= (int) $_POST['mid'];
$token = $_POST['auth'];
if (substr(sha1(CONF_SALT.'auxCom'.$mid),0,12)!=$token)
  exit("authentication failure");

$rply = trim($_POST['rply2cmnt']);
if (empty($rply)) exit("Empty reply, go back and try again.");

$qry = "SELECT * FROM {$SQLprefix}misc WHERE id=$mid AND type>1";
$record = pdo_query($qry)->fetch(PDO::FETCH_ASSOC);
if (!$record) exit("comment not found");

$subId = (int) $record['subId'];
$revId = (int) $record['revId'];
$type = (int) $record['type'];
$pid = (int) $record['numdata'];
$fromWhom = ($type==2)? "authors" : $_POST['yourName'];
if (!empty($fromWhom)) $fromWhom = " from $fromWhom";

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$qry = "SELECT a.assign FROM {$SQLprefix}submissions s 
      LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=?
      WHERE s.subId=?";
$res = pdo_query($qry, array($revId,$subId,$subId));
if (!($row = $res->fetch(PDO::FETCH_NUM)) || $row[0]<0) {
  exit("1"); // return error code #1
}

$qry = "INSERT INTO {$SQLprefix}posts (postId,subId,revId,parentId,subject,comments,whenEntered) VALUES (?,?,?,?,?,?,NOW())";

// get the next available post-ID
$res=pdo_query("SELECT 1+IFNULL(MAX(postId),0) FROM {$SQLprefix}posts");
$prms = array($res->fetchColumn(), $subId, $revId, $pid,
	      "Reply{$fromWhom}", $rply);
pdo_query($qry, $prms);
$postId = $prms[0];

// Remove record for this "impending reply"
pdo_query("DELETE FROM {$SQLprefix}misc WHERE id=$mid");

// Touch the entry to update the 'lastModified' timestamp
$qry = "UPDATE {$SQLprefix}submissions SET lastModified=NOW() WHERE subId=?";
pdo_query($qry, array($subId));

// Add this post to list of changes for this submission
$qry = "INSERT INTO {$SQLprefix}changeLog (subId,revId,changeType,description,entered) VALUES (?,?,'Post',?,NOW())";
pdo_query($qry, array($subId, $revId,$prms[4]));

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
header("Location: replyToComment.php?notifyOnly=true");
?>