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

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1) exit("<h1>$revName cannot discuss submissions yet</h1>");

if (isset($_POST['subId'])) { $subId = (int) trim($_POST['subId']); }
else exit("<h1>No Submission specified</h1>");



// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$cnnct = db_connect();
$qry = "SELECT a.assign FROM submissions s 
      LEFT JOIN assignments a ON a.revId='$revId' AND a.subId='$subId'
      WHERE s.subId='$subId'";
$res = db_query($qry, $cnnct);
if (!($row = mysql_fetch_row($res)) || $row[0]==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$qry = "INSERT INTO posts SET subId='$subId', revId='$revId',";

if (isset($_POST['parent']))
  $qry .= "parentId='". my_addslashes(trim($_POST['parent']), $cnnct). "',\n";

if (isset($_POST['subject']))
  $qry .= "  subject='".my_addslashes(trim($_POST['subject']), $cnnct)."',\n";

if (isset($_POST['comments']))
  $qry.= "  comments='".my_addslashes(trim($_POST['comments']), $cnnct)."',\n";

$qry .= "  whenEntered=NOW()";

if (!empty($_POST['subject']) || !empty($_POST['comments'])) {
  db_query($qry, $cnnct);

  // Touch the entry to update the 'lastModified' timestamp
  $qry = "UPDATE submissions SET lastModified=NOW() WHERE subId='$subId'";
  db_query($qry, $cnnct);

  // Add this post to list of changes for this submission
  $name =  mysql_real_escape_string($pcMember[1],$cnnct);
  $qry = "INSERT INTO changeLog (subId,revId,changeType,description,entered)
  VALUES ($subId,$revId,'Post','{$name} posted a message',NOW())";
  db_query($qry, $cnnct);

  // Send the new post by email to reviewers that have this submission
  // on their watch list and asked to be notified by email of new posts

  $qry = "SELECT c.email, c.flags FROM assignments a, committee c
  WHERE c.revId=a.revId AND a.subId=$subId AND a.revId!=$revId AND a.assign!=-1 AND a.watch=1";
  $res = db_query($qry, $cnnct);
  $notify = $comma = '';
  while ($row = mysql_fetch_row($res)) {
    $flags = $row[1];
    if ($flags & FLAG_EML_WATCH_EVENT) {
      $notify .= $comma . $row[0];
      $comma = ', ';
    }
  }
  if (!empty($notify)) {
    $qry = "SELECT title from submissions WHERE subId=$subId";
    $res = db_query($qry, $cnnct);
    $row = mysql_fetch_row($res); // $row[0] is the submissions title
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
}

// if this was reply to a previous post, return to that post
if (!empty($_POST['parent']))
     $anchor = 'p'.trim($_POST['parent']);
else $anchor = "endDiscuss"; // return to the end of the page

header("Location: discuss.php?subId=$subId#".$anchor);
?>
