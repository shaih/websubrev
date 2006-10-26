<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
$preReview=true;      // page is available also before the review peiod

require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];
$disFlag= (int) $pcMember[3];

$voteId = intval($_GET['voteId']);
if ($voteId <= 0) die("<h1>Vote-ID must be specified</h1>");

/* Make sure we didn't get here by mistake.. */
if (!isset($_POST['castVote'])) {
  header("Location: vote.php?voteId=$voteId");
  exit();
}

// Get the parameters of the current active vote.

$cnnct = db_connect();
$qry = "SELECT * from votePrms WHERE voteId=$voteId AND voteActive=1";

// Before the discussion phase, cannot vote on submissions
if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";

$res = db_query($qry,$cnnct);
$row = mysql_fetch_array($res)
     or die("<h1>No Active vote with Vote-ID $voteId</h1>");

$voteType = isset($row['voteType'])
     ? htmlspecialchars($row['voteType']) : 'Choose';
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteBudget= isset($row['voteBudget'])? intval($row['voteBudget']): 0;
$voteMaxGrade= isset($row['voteMaxGrade'])? intval($row['voteMaxGrade']): 1;

/* Handle the (easy) case of clear-all */
$newVotes = isset($_POST['votes'])? $_POST['votes'] : NULL;
if (!isset($newVotes) || !is_array($newVotes) || count($newVotes)==0) {
  $qry = "DELETE FROM votes WHERE voteId=$voteId AND revId=$revId";
  db_query($qry, $cnnct);
  header("Location: vote.php?voteId=$voteId&voteRecorded=yes");
  exit();
}

// Check that user vote did not exceed budget
$voteSum = array_sum($newVotes);
if ($voteBudget > 0 && $voteSum > $voteBudget) {
  if ($voteType=='Grade')
    exit ("<h1>Sum of all gardes cannot exceed $voteBudget</h1>");
  else
    exit ("<h1>Cannot choose more than $voteBudget items</h1>");
}

// Don't record votes on submissions where there is a conflict
$forbidden = array();
if ($voteFlags & VOTE_ON_SUBS) {
  $qry = "SELECT subId from assignments WHERE revId=$revId and assign=-1";
  $res = db_query($qry, $cnnct);
  while ($row=mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $forbidden[$subId] = true;
  }
}

$voteItems = array(); // represents the current state of the databse

// Insert dummy items for options that the user voted on
foreach ($newVotes as $itemId => $vote) {
  $itemId = (int) $itemId;
  if (!isset($forbidden[$itemId]))
    $voteItems[$itemId] = NULL;
}
  
// Get the votes that are currently recorded in the database
$qry = "SELECT subId, vote FROM votes WHERE voteId=$voteId AND revId=$revId ORDER by subId";
$res = db_query($qry, $cnnct);
while ($row=mysql_fetch_row($res)) {
  $itemId = (int) $row[0];
  if (!isset($forbidden[$itemId]))
    $voteItems[$itemId] = $row[1];
}

foreach($voteItems as $itemId => $vItem) {
  $itemId = (int) $itemId;
  $theVote = NULL;
  if (isset($newVotes[$itemId])) {
    $theVote = intval($newVotes[$itemId]);
    if ($theVote<0) $theVote = 0;
    else if ($theVote>$voteMaxGrade) $theVote = $voteMaxGrade;
  }
  if ($theVote!==$vItem){
    if (!isset($vItem))      // insert a new entry (uses REPLACE rather than INSERT just for safety)
      $qry = "REPLACE votes VALUES ($voteId,$revId,$itemId,$theVote)";
    else if (isset($theVote))// modify existing entry
      $qry = "UPDATE votes SET vote=$theVote WHERE voteId=$voteId AND revId=$revId AND subId=$itemId";
    else                     // delete existing entry
      $qry = "DELETE FROM votes WHERE voteId=$voteId AND revId=$revId AND subId=$itemId";
    db_query($qry, $cnnct);
  }
}
header("Location: vote.php?voteId=$voteId&voteRecorded=yes");
?>
