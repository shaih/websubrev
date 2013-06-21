<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';
require 'showReviews.php';

$revId = (int) $pcMember[0];
$subId = (int) trim($_GET['subId']);
$markRead = (int) trim($_GET['current']); // should be 0 or 1

// Check if a row exists in the laspPost table
$qry = "SELECT COUNT(*) FROM {$SQLprefix}lastPost WHERE revId=$revId AND subId=$subId";

if ($db->query($qry)->fetchColumn()<=0) { // no such row, insert one
  // Check that this submission exists
  $qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions WHERE subId=$subId";
  if ($db->query($qry)->fetchColumn() >0) {
    $db->exec("INSERT IGNORE INTO {$SQLprefix}lastPost SET lastSaw=0,revId=$revId,subId=$subId,lastVisited=NOW()");
    $markRead = 1;
  }
  else $markRead = -1; // signal an error
}
else { // row exists in lastPost table, toggle the read status
  if ($markRead) { // current status is read
    $lastVisit= 'FROM_UNIXTIME(0)'; // long time ago
    $markRead = 0;
  }
  else {
    $lastVisit = 'NOW()';
    $markRead = 1;
  }
  $db->exec("UPDATE {$SQLprefix}lastPost SET lastVisited=$lastVisit WHERE revId=$revId AND subId=$subId");
}

if (!empty($_GET['ajax'])) { // Ajax call, just return the new markRead status
  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-cache");
  echo json_encode(array('current'=>$markRead));
  // error_log(date('Y.m.d-H:i:s ' ).$_SERVER['QUERY_STRING']."\n",3,LOG_FILE);
} else {
  return_to_caller('listSubmissions.php'); // non-Ajax, redirect back
}
?>