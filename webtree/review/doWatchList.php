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
  $cnnct = db_connect();

  if (isset($_POST['watch'])) {   // Some submissions to be watched
    $watchSubs = array_keys($_POST['watch']);
    $vals = '('
      .implode(",$revId,3,0,0,0,1),(", $watchSubs).",$revId,3,0,0,0,1)";
    $watchList = implode(', ', $watchSubs);

    // Insert records to the database (existing records will not be effected)
    $qry = "INSERT IGNORE INTO assignments VALUES{$vals}";
    db_query($qry, $cnnct);

    // Update existing recors: first set watch=1 for records in the list,
    $qry = "UPDATE assignments SET watch=1 WHERE revId=$revId AND subId IN ($watchList)";
    db_query($qry, $cnnct);

    // ... then set watch=0 for records not in the list,
    $qry = "UPDATE assignments SET watch=0 WHERE revId=$revId AND subId NOT IN ($watchList)";
    db_query($qry, $cnnct);
  }
  else {  // No submissions to be watched
    $qry = "UPDATE assignments SET watch=0 WHERE revId=$revId";
    db_query($qry, $cnnct);
  }

  $newPCMflags = $pcmFlags;
  if (isset($_POST['emlNewPosts'])) $newPCMflags |= FLAG_EML_WATCH_EVENT;
  else $newPCMflags &= (~FLAG_EML_WATCH_EVENT);
  if ($newPCMflags != $pcmFlags) {
    $qry = "UPDATE committee SET flags=$newPCMflags WHERE revId=$revId";
    db_query($qry, $cnnct);
  }
}
header("Location: .");
?>
