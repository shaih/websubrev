<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

/* If chair specified parameters - write them to database
 *******************************************************************/
if (isset($_POST["setup"])) { 

  // set the vote flags
  $voteFlags = 0;
  $voteOnWhat = trim($_POST["voteOnWhat"]);
  if ($voteOnWhat!="other") {
    $voteFlags |= FLAG_VOTE_ON_SUBS;
    if ($voteOnWhat=="all") $voteFlags |= FLAG_VOTE_ON_ALL;
    else {
      if (isset($_POST["voteOnAC"])) $voteFlags |= FLAG_VOTE_ON_AC;
      if (isset($_POST["voteOnMA"])) $voteFlags |= FLAG_VOTE_ON_MA;
      if (isset($_POST["voteOnDI"])) $voteFlags |= FLAG_VOTE_ON_DI;
      if (isset($_POST["voteOnNO"])) $voteFlags |= FLAG_VOTE_ON_NO;
      if (isset($_POST["voteOnMR"])) $voteFlags |= FLAG_VOTE_ON_MR;
      if (isset($_POST["voteOnRE"])) $voteFlags |= FLAG_VOTE_ON_RE;
    }
  }
  $values = "voteFlags=?";
  $prms = array($voteFlags);

  $instructions = trim($_POST["voteInstructions"]);
  if (!empty($instructions)) {
    $values .= ", instructions=?";
    $prms[] = $instructions;
  }
  $voteTitle = trim($_POST["voteTitle"]);
  if (!empty($voteTitle)) {
    $values .= ", voteTitle=?";
    $prms[] = $voteTitle;
  }
  $deadline = trim($_POST["voteDeadline"]);
  if (!empty($deadline)) {
    $values .= ",deadline=?";
    $prms[] = $deadline;
  }
  $voteType = trim($_POST["voteType"]);
  if (!empty($voteType)) {
    $values .= ",voteType=?";
    $prms[] = ($voteType=='Grade')? 'Grade' : 'Choose';
  }
  $voteMaxGrade = trim($_POST["voteMaxGrade"]);
  if (!empty($voteMaxGrade)) {
    if ($voteType=='Choose' || $voteMaxGrade < 1 || $voteMaxGrade > 9)
      $voteMaxGrade = 1;
    $values .= ", voteMaxGrade=?";
    $prms[] = $voteMaxGrade;
  }

  $voteBudget = trim($_POST["voteBudget"]);
  if (!empty($voteBudget)) {
    if ($voteBudget <= 0) $voteBudget = 0;
    $values .= ", voteBudget=?";
    $prms[] = $voteBudget;
  }
  if (!($voteFlags & FLAG_VOTE_ON_SUBS)) {
    $voteOnThese = trim($_POST["voteItems"]);
  } else if (!($voteFlags & FLAG_VOTE_ON_ALL)) {
    $voteOnThese = trim($_POST["voteOnThese"]);
  }
  if (!empty($voteOnThese)) {
    $values .= ", voteOnThese=?";
    $prms[] = $voteOnThese;
  }

  $voteId = intval(trim($_POST["voteId"]));
  if ($voteId > 0) { // update an existing entry
    $qry = "UPDATE {$SQLprefix}votePrms SET $values WHERE voteId=$voteId";
  } else {           // update insert a new row
    $qry = "INSERT INTO {$SQLprefix}votePrms SET voteActive=1, $values";
  }
  pdo_query($qry, $prms);
}  // if (isset($_POST["setup"]))


if (isset($_POST["closeVote"])) { // Close the current vote
  $voteId = intval(trim($_POST["voteId"]));
  $hide = (isset($_POST["hideVote"]) && $_POST["hideVote"]=="on")? -1 :0;
  if ($voteId > 0) {
    pdo_query("UPDATE {$SQLprefix}votePrms SET voteActive=$hide WHERE voteId=$voteId");
  }
  header("Location: voteDetails.php?voteId=$voteId");
}

header("Location: voting.php");
?>