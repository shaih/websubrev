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

if (isset($_GET['subId'])) {
  $subId = (int) trim($_GET['subId']); 
}
else
  exit("0");

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. 
$cnnct = db_connect();
$qry = "SELECT a.assign, a.watch FROM submissions s 
      LEFT JOIN assignments a ON a.revId='{$revId}' AND a.subId='{$subId}'
      WHERE s.subId='{$subId}'";
$res = db_query($qry, $cnnct);
if (!($row = mysql_fetch_row($res)) || $row[0]==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$watch = 0;

if (isset($row[1])) { // modify existing entry
  $watch = 1 - $row[1];
  $qry = "UPDATE assignments SET watch={$watch}
  WHERE revId={$revId} AND subId='{$subId}'";
} else {              // insert a new entry
  $watch = 1;
  $qry = "INSERT INTO assignments SET revId=$revId, subId=$subId, watch=1";
}

db_query($qry, $cnnct);

header("Content-Type: application/json");
header("Cache-Control: no-cache");
echo $watch;
?>
