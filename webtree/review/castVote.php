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

$qry = "SELECT * from {$SQLprefix}votePrms WHERE voteId=? AND voteActive=1";

// Before the discussion phase, cannot vote on submissions
if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";

$res = pdo_query($qry,array($voteId));
$row = $res->fetch(PDO::FETCH_ASSOC)
     or die("<h1>No Active vote with Vote-ID $voteId</h1>");

$voteType = isset($row['voteType'])
     ? htmlspecialchars($row['voteType']) : 'Choose';
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteBudget= isset($row['voteBudget'])? intval($row['voteBudget']): 0;
$voteOnThese = isset($row['voteOnThese'])? $row['voteOnThese'] : '';
$voteMaxGrade= isset($row['voteMaxGrade'])? intval($row['voteMaxGrade']): 1;

// Check that user vote did not exceed budget
$newVotes = isset($_POST['votes'])? $_POST['votes'] : NULL;
$voteSum = is_array($newVotes) ? array_sum($newVotes) : 0;
if ($voteBudget > 0 && $voteSum > $voteBudget) {
  if ($voteType=='Grade')
    exit ("<h1>Sum of all gardes cannot exceed $voteBudget</h1>");
  else
    exit ("<h1>Cannot choose more than $voteBudget items</h1>");
}

// Get the items to vote on
$voteItems = array();
if ($voteFlags & VOTE_ON_SUBS) { // voting on submissions

  // Don't record votes on submissions where there is a conflict
  $forbidden = array();
  $qry = "SELECT subId from {$SQLprefix}assignments WHERE revId=? AND assign<0";
  $res = pdo_query($qry, array($revId));
  while ($row=$res->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $row[0];
    $forbidden[$subId] = true;
  }

  if ($voteFlags & VOTE_ON_ALL)
    $where = "s.status!='Withdrawn'";
  else 
    $where = "false";

  if ($voteFlags & VOTE_ON_RE) {
    $where .= " OR status='Reject'"; }
  if ($voteFlags & VOTE_ON_MR) {
    $where .= " OR status='Perhaps Reject'"; }
  if ($voteFlags & VOTE_ON_NO) {
    $where .= " OR status='None'"; }
  if ($voteFlags & VOTE_ON_DI) {
    $where .= " OR status='Needs Discussion'"; }
  if ($voteFlags & VOTE_ON_MA) {
    $where .= " OR status='Maybe Accept'"; }
  if ($voteFlags & VOTE_ON_AC) {
    $where .= " OR status='Accept'"; }
  if (!empty($voteOnThese)) {
    $where .= " OR s.subId IN (".numberlist($voteOnThese).")"; }

  $qry = "SELECT s.subId FROM {$SQLprefix}submissions s
  WHERE $where ORDER by s.subId";
  $res = pdo_query($qry);
  while ($row=$res->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $row[0];
    if (!isset($forbidden[$subId])) $voteItems[$subId] = 0;
  }
}
else {                           // voting on "other things"
  $voteTitles = explode(';', $voteOnThese);
  $i = 1;
  foreach ($voteTitles as $title) {
    $title = trim($title);
    if (!empty($title)) 
    $voteItems[$i] = 0;
    $i++;
  }
}

// Update the voteItems with the actual votes that we got from the user
if (is_array($newVotes)) foreach ($newVotes as $itemId => $vote) {
  $itemId = (int) $itemId;
  if (isset($voteItems[$itemId])) $voteItems[$itemId] = (int) $vote;
}

// Prepare a list of votes
$values = '';
foreach ($voteItems as $itemId => $vote) {
  $values .= "($voteId,$revId,$itemId,$vote),";
}
if (!empty($values)) $values = substr($values, 0, -1); // remove last comma

// Remove all prior votes (if any) and insert the new ones
if (!empty($values)) { // What is the right behaviour is $values is empty?
  $qry = "DELETE FROM {$SQLprefix}votes WHERE voteId=? AND revId=?";
  pdo_query($qry, array($voteId,$revId));

  $qry = "INSERT INTO {$SQLprefix}votes (voteId,revId,subId,vote)
  VALUES $values";
  pdo_query($qry);
}
header("Location: vote.php?voteId=$voteId&voteRecorded=yes");
?>
