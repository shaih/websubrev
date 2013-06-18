<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, William Blair, Adam Udi
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
$qry = "SELECT a.assign, a.watch FROM {$SQLprefix}submissions s 
      LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=?
      WHERE s.subId=?";
$res = pdo_query($qry, array($revId,$subId,$subId));
if (!($row = $res->fetch(PDO::FETCH_NUM)) || $row[0]<0) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$watch = 0;

if (isset($row[1])) { // modify existing entry
  $watch = 1 - $row[1];
  pdo_query("UPDATE {$SQLprefix}assignments SET watch=? WHERE revId=? AND subId=?",array($watch,$revId,$subId));
} else {              // insert a new entry
  $watch = 1;
  pdo_query("INSERT INTO {$SQLprefix}assignments SET revId=?, subId=?, watch=1",
	    array($revId,$subId));
}

header("Content-Type: application/json");
header("Cache-Control: no-cache");
echo $watch;
?>
