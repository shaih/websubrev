<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication=true;
require 'header.php';  // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];
$pcmFlags= (int) $pcMember[5];

if (isset($_POST['updateWatchList'])) {

  if (isset($_POST['watch']) && is_array($_POST['watch'])) { // Some submissions to be watched
    $watchSubs = array();
    foreach($_POST['watch'] as $subId => $s) {
      $subId = intval($subId);
      if ($subId>0) $watchSubs[] = $subId;
    }
    $vals = '('
      .implode(",$revId),(", $watchSubs).",$revId)";
    $watchList = implode(', ', $watchSubs);

    // Insert records to the database (existing records will not be affected)
    $qry = "INSERT IGNORE INTO {$SQLprefix}assignments (subId,revId) VALUES{$vals}";
    pdo_query($qry);

    // Update existing recors: first set watch=1 for records in the list,
    $qry = "UPDATE {$SQLprefix}assignments SET watch=1 WHERE revId=? AND subId IN ($watchList)";
    pdo_query($qry, array($revId));

    // ... then set watch=0 for records not in the list,
    $qry = "UPDATE {$SQLprefix}assignments SET watch=0 WHERE revId=? AND subId NOT IN ($watchList)";
    pdo_query($qry, array($revId));
  }
  else {  // No submissions to be watched
    $qry = "UPDATE {$SQLprefix}assignments SET watch=0 WHERE revId=?";
    pdo_query($qry, array($revId));
  }

  $newPCMflags = $pcmFlags;

  if (isset($_POST['emlNewPosts'])) $newPCMflags |= FLAG_EML_WATCH_EVENT;
  else $newPCMflags &= (~FLAG_EML_WATCH_EVENT);

  if (isset($_POST['orderWatchAtHome']))
       $newPCMflags |= FLAG_ORDER_REVIEW_HOME;
  else $newPCMflags &= (~FLAG_ORDER_REVIEW_HOME);

  if ($newPCMflags != $pcmFlags) {
    $qry = "UPDATE {$SQLprefix}committee SET flags=? WHERE revId=?";
    pdo_query($qry, array($newPCMflags,$revId));
  }
}
header("Location: .");
?>
