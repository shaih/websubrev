<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php';     // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit('no submission specified');

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$qry = "SELECT a.assign,a.watch FROM {$SQLprefix}submissions s LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId WHERE s.subId=?";
$row = pdo_query($qry,array($revId,$subId))->fetch(PDO::FETCH_NUM);
if (!$row || $row[0]<0) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$watch = 0;
if (!empty($_GET['current'])) $watch = (int) trim($_GET['current']);
elseif (!empty($row[1]))      $watch = (int) $row[1];

$watch = 1 - $watch;
if (isset($row[0])) {  // modify existing entry
  $qry = "UPDATE {$SQLprefix}assignments SET watch={$watch} WHERE revId=? AND subId=?";
} elseif ($watch==1) { // insert a new entry
  $qry = "INSERT INTO {$SQLprefix}assignments SET revId=?, subId=?, watch=1";
}
pdo_query($qry,array($revId,$subId));

if (!empty($_GET['ajax'])) { // Ajax call, just return the new markRead status
  header("Content-Type: application/json; charset=utf-8");
  header("Cache-Control: no-cache");
  echo json_encode(array('current'=>$watch));
} else {
  return_to_caller('listSubmissions.php');
}
?>
