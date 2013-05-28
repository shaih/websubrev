<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

$subId=0;
$revId = (int) $chair[0];
$sttsCodes = array("None"=>"NO",
		   "Reject"=>"RE",
		   "Perhaps Reject"=>"MR",
		   "Needs Discussion"=> "DI",
		   "Maybe Accept"=>"MA",
		   "Accept"=>"AC");

// Read the current status before changing it
$cnnct = db_connect();
$qry = "SELECT subId, scratchStatus FROM submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry,$cnnct);

$oldStts = array();
while ($row = mysql_fetch_row($res)) {
  $subId = $row[0];
  $oldStts[$subId] = $row[1];
}

if(isset($_POST['changes']) && is_array($_POST['changes'])) {

foreach ($_POST['changes'] as $key => $val) {
  
        if (strncmp($key, 'scrsubStts', 10)!=0 || empty($val))
          continue;
	$subId = (int) substr($key, 10);
	if ($subId<=0) continue;
	
	$status = my_addslashes(trim($val), $cnnct);
	//if ($status==$oldStts[$subId]) continue;
	$stCode = $sttsCodes[$status];
	$oldStCode = $sttsCodes[$oldStts[$subId]];
	$qry = "UPDATE submissions SET scratchStatus='$status', lastModified=lastModified WHERE subId={$subId} AND scratchStatus!='$status'";
	db_query($qry, $cnnct);
	if (isset($_POST['noAnchor'])){ 
          $qry = "UPDATE submissions SET status='$status', lastModified=NOW() WHERE subId={$subId} AND status!='$status'";
          db_query($qry, $cnnct);
		// If status changed, send email to those who asked for it
		if (mysql_affected_rows($cnnct)==1 && isset($_POST['noAnchor'])) {
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
				$sbjct = "Submission $subId to ".CONF_SHORT.' '.CONF_YEAR
				.': moved to '.$stCode;
				my_send_mail($notify, $sbjct, '');
			}
			if (isset($_POST['noAnchor'])){
				// Add a change-log record
				$qr = "INSERT INTO changeLog (subId,revId,changeType,description,entered)
				VALUES ($subId,$revId,'Status','$oldStCode => $stCode',NOW())";
				db_query($qry, $cnnct);
			}
		}
	
		// Insert an entry to the acceptedPapers table if needed (note: there
		// is no real need to remove from that table if status changes back to
		// something other than accept).
		if (isset($_POST['noAnchor'])) {
			if ($status='Accept') {
				$qry = "SELECT 1 from acceptedPapers where subId={$subId}";
				$res = db_query($qry, $cnnct);
				if (mysql_num_rows($res)<=0) {
					db_query("INSERT INTO acceptedPapers SET subId={$subId}", $cnnct);
				}
			}
		}
	}
}

}

//Get the most up to date information.
$statuses = array();
$qry = "SELECT scratchStatus, COUNT(subId) from submissions WHERE status!='Withdrawn'
  GROUP BY scratchStatus";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) {
  $stts = $row[0];
  $statuses[$stts] = $row[1];
}

// Prepare an array of submissions
$qry = "SELECT subId, scratchStatus, status from submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry, $cnnct);
$data = array();

while ($row=mysql_fetch_assoc($res)) {
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
