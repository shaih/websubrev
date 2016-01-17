<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';

function arrayOfIDs($list)
{
  global $db, $SQLprefix;

  $list = numberlist($list);
  $ids = explode(",",$list);

  // verify that all of these submissions actually exist
  $qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions WHERE status!='Withdrawn' AND subId IN ($list)";
  if (pdo_query($qry)->fetchColumn() != count($ids)) {
    header("Location: groups.php?failed=noSub");
    exit();
  }
  return $ids;
}


// Modify or delete existing groups
if (isset($_POST['EditGroups'])) {
  $success = '';
  // Get a list of all the groups, these are submissions with large
  // submiddion-ID (>9000) and status 'Withdrawn'

  $qry = "SELECT subId,title FROM {$SQLprefix}submissions s WHERE s.subId >= 9000 AND status='Withdrawn' AND (flags & ".FLAG_IS_GROUP.")";
  $res = pdo_query($qry);

  // modify/remove groups if needed
  $qry = "UPDATE {$SQLprefix}submissions SET title=? WHERE subID=?";
  $stmtUpdt = $db->prepare($qry);
  $qry = "UPDATE {$SQLprefix}submissions SET flags=(flags &(~".FLAG_IS_GROUP.")) WHERE subID=?";
  $stmtDlet = $db->prepare($qry);

  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $subId = $row[0];
    if (empty($_POST[$subId])) // remove this group
      $stmtDlet->execute(array($subId));

    else { // check if need to update the list
      $IDlist= explode(',', $row[1]);
      $newIDlist = arrayOfIDs($_POST[$subId]);
      $delta1 = array_diff($IDlist, $newIDlist);
      $delta2 = array_diff($newIDlist, $IDlist);
      if (!empty($delta1) || !empty($delta2)) { // lists are different
	if (count($newIDlist)>1) {
	  $newIDlist = implode(",", $newIDlist);
	  $stmtUpdt->execute(array($newIDlist, $subId));
	  $success = '?success='.urlencode($newIDlist);
	}
	else // a group cannot have just one submission, delete this group
	  $stmtDlet->execute(array($subId));
      }
    }
  }
  header("Location: groups.php{$success}");
}

// create a new group
if (isset($_POST['newGroup']) && !empty($_POST['IDs'])) {
  $ids = arrayOfIDs($_POST['IDs']);
  if (count($ids)<2) {
    header("Location: groups.php?failed=tooFew");
    exit();
  }
  // Create the new group in the database
  $maxSubId = pdo_query("SELECT MAX(subId) FROM {$SQLprefix}submissions")->fetchColumn();
  $newId = ($maxSubId < 9000)? 9000 : ($maxSubId+1);
  $ids = implode(",", $ids);

  $qry = "INSERT INTO {$SQLprefix}submissions (subId, title, status, flags) VALUES (?,?,'Withdrawn',".FLAG_IS_GROUP.")";
  pdo_query($qry, array($newId,$ids));
  header("Location: groups.php?success=".urlencode($ids));
}
?>
