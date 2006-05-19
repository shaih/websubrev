<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';
$cnnct = db_connect();

/* If chair specified parameters - write them to database
 *******************************************************************/
if (isset($_POST["setup"])) { 

  // set the vote flags
  $voteFlags = 0;
  $voteOnWhat = trim($_POST["voteOnWhat"]);
  if ($voteOnWhat!="other") {
    $voteFlags |= VOTE_ON_SUBS;
    if ($voteOnWhat=="all") $voteFlags |= VOTE_ON_ALL;
    else {
      if (isset($_POST["voteOnAC"])) $voteFlags |= VOTE_ON_AC;
      if (isset($_POST["voteOnMA"])) $voteFlags |= VOTE_ON_MA;
      if (isset($_POST["voteOnDI"])) $voteFlags |= VOTE_ON_DI;
      if (isset($_POST["voteOnNO"])) $voteFlags |= VOTE_ON_NO;
      if (isset($_POST["voteOnMR"])) $voteFlags |= VOTE_ON_MR;
      if (isset($_POST["voteOnRE"])) $voteFlags |= VOTE_ON_RE;
    }
  }
  $values = "voteFlags=$voteFlags";

  $instructions = trim($_POST["voteInstructions"]);
  if (!empty($instructions)) {
    $values .= ", instructions='" . my_addslashes($instructions, $cnnct)."'";
  }
  $voteTitle = trim($_POST["voteTitle"]);
  if (!empty($voteTitle)) {
    $values .= ", voteTitle='".my_addslashes($voteTitle, $cnnct)."'";
  }
  $deadline = trim($_POST["voteDeadline"]);
  if (!empty($deadline)) {
    $values .= ",deadline='".my_addslashes($deadline, $cnnct)."'";
  }
  $voteType = trim($_POST["voteType"]);
  if (!empty($voteType)) {
    if ($voteType!='Grade') $voteType='Choose';
    $values .= ",voteType='$voteType'";
  }
  $voteMaxGrade = trim($_POST["voteMaxGrade"]);
  if (!empty($voteMaxGrade)) {
    $voteMaxGrade = (int) $voteMaxGrade;
    if ($voteType=='Choose' || $voteMaxGrade < 1 || $voteMaxGrade > 9)
      $voteMaxGrade = 1;
    $values .= ", voteMaxGrade=".$voteMaxGrade;
  }
  $voteBudget = trim($_POST["voteBudget"]);
  if (!empty($voteBudget)) {
    $voteBudget = (int) $voteBudget;
    if ($voteBudget < 0) $voteBudget = 0;
    $values .= ", voteBudget=".$voteBudget;
  }
  if (!($voteFlags & VOTE_ON_SUBS)) {
    $voteOnThese = trim($_POST["voteItems"]);
  } else if (!($voteFlags & VOTE_ON_ALL)) {
    $voteOnThese = trim($_POST["voteOnThese"]);
  }
  if (!empty($voteOnThese)) {
    $values .= ", voteOnThese='".my_addslashes($voteOnThese,$cnnct)."'";
  }

  $voteId = intval(trim($_POST["voteId"]));
  if ($voteId > 0) { // update an existing entry
    $qry = "UPDATE votePrms SET $values WHERE voteId=$voteId";
  } else {           // update insert a new row
    $qry = "INSERT INTO votePrms SET voteActive=1, $values";
  }
  db_query($qry, $cnnct);
}  // if (isset($_POST["setup"]))

/* Close the current vote and remove the vote-parameter file
 *******************************************************************/
if (isset($_POST["closeVote"])) {
  $voteId = intval(trim($_POST["voteId"]));
  if ($voteId > 0) {
    db_query("UPDATE votePrms SET voteActive=0 WHERE voteId=$voteId", $cnnct);
  }
  header("Location: voteDetails.php?voteId=$voteId");
}

header("Location: voting.php");
?>
