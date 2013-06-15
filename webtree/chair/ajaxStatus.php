<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

$sttsCodes = array("None"=>"NO",
		   "Reject"=>"RE",
		   "Perhaps Reject"=>"MR",
		   "Needs Discussion"=> "DI",
		   "Maybe Accept"=>"MA",
		   "Accept"=>"AC");

if (isset($_POST['updateOne'])) { // update a single record
  $subId = (int) $_POST['subId'];
  $newStatus = $_POST['status'];
  if ($subId <= 0 || !isset($sttsCodes[$newStatus])) {
    header('HTTP/1.0 400 Bad Request', true, 400);
    exit();
  }
  // update the status in the database
  $res = pdo_query("UPDATE {$SQLprefix}submissions SET scratchStatus=? WHERE subId=?", array($newStatus,$subId));
  if ($res->rowCount() <= 0) { // no such submissions?
    header('HTTP/1.0 400 Bad Request', true, 400);
    exit();
  }
  // return the result to the user
  $json = json_encode(array('status' => $newStatus,
			    'html' => show_status($newStatus)));
  header("Content-Type: application/json");
  header("Cache-Control: no-cache");
  exit($json);
}
/***
$subId=0;
$revId = (int) $chair[0];
// Read the current status before changing it
$qry = "SELECT subId, scratchStatus FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);

$oldStts = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = $row[0];
  $oldStts[$subId] = $row[1];
}

if (isset($_POST['changes']) && is_array($_POST['changes'])) {
  $stmt = $db->prepare("UPDATE {$SQLprefix}submissions SET scratchStatus=?, lastModified=lastModified WHERE subId=? AND scratchStatus!=?");
  foreach ($_POST['changes'] as $key => $val) {  
    if (strncmp($key, 'scrsubStts', 10)!=0 || empty($val))
      continue;

    $subId = (int) substr($key, 10);
    if ($subId>0) $stmt->execute(array($val,$subId,$val));
  }
}

//Get the most up to date information.
$statuses = array();
$qry = "SELECT scratchStatus, COUNT(subId) FROM {$SQLprefix}submissions WHERE status!='Withdrawn' GROUP BY scratchStatus";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $stts = $row[0];
  $statuses[$stts] = $row[1];
}

// Prepare an array of submissions
$qry = "SELECT subId, scratchStatus, status FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$data = array();

while ($row=$res->fetch(PDO::FETCH_ASSOC)) {
  $maxSubId = $row['subId'];
  $subId = $row['subId'];
  $data["scrsubStts$subId"] = $row['scratchStatus'];
//  $data["subStts$subId"] = $row["status"];
}

header("Content-Type: application/json");
header("Cache-Control: no-cache");

echo json_encode(
                 array(
                       "data"=> $data, 
                       "stats"=>$statuses)
                 );
***/