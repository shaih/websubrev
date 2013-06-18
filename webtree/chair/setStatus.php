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
$qry = "SELECT subId, scratchStatus, status FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);

$oldStts = array();
$oldScStts = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = (int) $row[0];
  $oldScStts[$subId] = $row[1];
  $oldStts[$subId] = $row[2];
}

// update the scratch status first, don't update lastModified
$stmt = $db->prepare("UPDATE {$SQLprefix}submissions SET scratchStatus=?, lastModified=lastModified WHERE subId=?");

$changes = array();
foreach ($_POST as $key => $status) {
  if (strncmp($key, 'scrsubStts', 10)!=0 || empty($status))
    continue;

  $subId = (int) substr($key, 10);
  if ($subId<=0) continue;

  if ($status!=$oldScStts[$subId]) { // scratch status has changed
    $stmt->execute(array($status,$subId));  // record new scratch status
    $changes[$subId] = show_status($status); // add to list of changes
    // show_status returns HTML code to show the new status (in confUtils.php)
  }
}

// If needed, update also the real status
if (!empty($_POST['visible'])) {

  $stmt = $db->prepare("UPDATE {$SQLprefix}submissions SET status=?, lastModified=lastModified WHERE subId=?");

  // Record in the changes log and send email to people who asked for it

  $stmt1 = $db->prepare("SELECT c.email,c.flags FROM {$SQLprefix}assignments a, {$SQLprefix}committee c WHERE c.revId=a.revId AND a.subId=? AND c.revId!=$revId AND a.assign>=0 AND a.watch=1");

  $stmt2 = $db->prepare("INSERT INTO {$SQLprefix}changeLog (subId,revId,changeType,description,entered) VALUES (?,?,'Status',?,NOW())");

  $stmt3 = $db->prepare("INSERT IGNORE INTO {$SQLprefix}acceptedPapers SET subId=?");
  foreach ($_POST as $key => $status) {
    if (strncmp($key, 'scrsubStts', 10)!=0 || empty($status))
      continue;

    $subId = (int) substr($key, 10);
    if ($subId<=0) continue;

    $stCode = $sttsCodes[$status];
    $oldStCode = $sttsCodes[$oldStts[$subId]];

    if ($stCode != $oldStCode) { // status has changed

      $stmt->execute(array($status,$subId)); // record new status in database

      // make a list of reviewers to send email to
      $notify = $comma = '';
      $stmt1->execute(array($subId));
      while ($row = $stmt1->fetch(PDO::FETCH_NUM)) {
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
      $stmt2->execute(array($subId,$revId,"$oldStCode => $stCode"));

      // Insert an entry to the acceptedPapers table if needed (note: there
      // is no real need to remove from that table if status changes back to
      // something other than accept).
      if ($status=='Accept') $stmt3->execute(array($subId));
    }
  }
}
if (isset($_POST['ajax'])) {
  header("Content-Type: application/json");
  header("Cache-Control: no-cache");
  exit(json_encode($changes));
}
else return_to_caller('.');
?>