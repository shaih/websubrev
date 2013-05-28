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
$qry = "SELECT subId, scratchStatus, status FROM submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry,$cnnct);

$oldStts = array();
$oldScStts = array();
while ($row = mysql_fetch_row($res)) {
  $subId = (int) $row[0];
  $oldScStts[$subId] = $row[1];
  $oldStts[$subId] = $row[2];
}

foreach ($_POST as $key => $val) {
  if (strncmp($key, 'scrsubStts', 10)!=0 || empty($val))
    continue;

  $subId = (int) substr($key, 10);
  if ($subId<=0) continue;

  $status = my_addslashes(trim($val), $cnnct);
  if ($status==$oldStts[$subId] && $status==$oldScStts[$subId]) continue; // no change
  $stCode = $sttsCodes[$status];
  $oldStCode = $sttsCodes[$oldStts[$subId]];

  if ($status!=$oldScStts[$subId]) { // save scratch copy, don't update lastModified
    $qry = "UPDATE submissions SET scratchStatus='$status', lastModified=lastModified WHERE subId={$subId} AND scratchStatus!='$status'";
    db_query($qry, $cnnct);
  }

  // save also to visible status, if needed
  if (isset($_POST['noAnchor']) && $status!=$oldStts[$subId]) {
    $qry = "UPDATE submissions SET status='$status', lastModified=NOW() WHERE subId={$subId} AND status!='$status'";
    db_query($qry, $cnnct);

    // If status changed, send email to those who asked for it, and record change in log
    if (mysql_affected_rows($cnnct)==1) {
      // make a list of reviewers to send email to
      $qry = "SELECT c.email, c.flags FROM assignments a, committee c WHERE c.revId=a.revId AND a.subId=$subId AND a.revId!=$revId AND a.assign!=-1 AND a.watch=1";
      $res = db_query($qry, $cnnct);
      $notify = $comma = '';
      while ($row = mysql_fetch_row($res)) {
        $flags = $row[1];
        if ($flags & FLAG_EML_WATCH_EVENT) {
          $notify .= $comma . $row[0];
          $comma = ', ';
        }
      }
      // if the list is not empty, send email
      if (!empty($notify)) {
        $sbjct = "Submission $subId to ".CONF_SHORT.' '.CONF_YEAR.': moved to '.$stCode;
        my_send_mail($notify, $sbjct, '');
      }

      // Add a change-log record
      $qry = "INSERT INTO changeLog (subId,revId,changeType,description,entered) VALUES ($subId,$revId,'Status','$oldStCode => $stCode',NOW())";
      db_query($qry, $cnnct);

      // Insert an entry to the acceptedPapers table if needed (note: there
      // is no real need to remove from that table if status changes back to
      // something other than accept).
      if ($status='Accept') {
        $qry = "SELECT 1 from acceptedPapers where subId={$subId}";
        $res = db_query($qry, $cnnct);
        if (mysql_num_rows($res)<=0)
          db_query("INSERT INTO acceptedPapers SET subId={$subId}", $cnnct);
      }
    }
  }
}
if ($subId>0 && !isset($_POST['noAnchor']))
     $anchor="#stts{$subId}";
else $anchor="";
return_to_caller('index.php', '', $anchor);
?>
