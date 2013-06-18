<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication = true; 
require 'header.php';

if (defined('CAMERA_PERIOD')) exit("<h1>Review Site is Closed</h1>");

// Get the lists of submissions and reviewers. With each submission also
// record the number of reviewers that should be assigned to it. We assign
// the same number to each submission (3 by default), but "special"
// submissions can be assigned more reviewers. 

$subCovrg = trim($_POST['subCoverage']);
if (empty($subCovrg)) $subCovrg = 3;
else $subCovrg = (int) $subCovrg;

if (isset($_POST['specialSubs'])) { // submissions that need more reviewers
  $spclSubs = explode(',', $_POST['specialSubs']);// semi-colon separated list
  for ($i=0; $i<count($spclSubs); $i++)
    $spclSubs[$i] = (int) trim($spclSubs[$i]);

  $spclCovrg = trim($_POST['specialCoverage']);
  if (empty($spclCovrg)) $spclCovrg = $subCovrg+1;
  else $spclCovrg = (int) $spclCovrg;
}
else $spclSubs = array();

$qry = "SELECT subId FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER by subPwd"; // pick a fixed random order
$res = pdo_query($qry);
$subs = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) { 
  $subId = (int) $row[0]; 
  if (in_array($subId, $spclSubs))  // this submission is "special"
       $subs[$subId] = $spclCovrg;
  else $subs[$subId] = $subCovrg;
}
$nReports = array_sum($subs);  // how many reports we need in total


// Simillarly record for each reviewer the maximum number of assignments.
$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & " . FLAG_IS_CHAIR .") ORDER by revPwd";    // pick a fixed random order
$res = pdo_query($qry);
$revs = array();
$committee = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int)$row[0]; 
  $revs[$revId] = 0;
  $committee[$revId] = array(trim($row[1]));
}

// The optional input excludeRevs is a semi-colon-separated list of reviewers
// that should not be assigned any submissions (e.g., you don't want to
// assign submissions to the chair(s)).
if (isset($_POST['cListExclude'])) {
  $exRevs = explode(';', $_POST['cListExclude']);
  $stmt = $db->prepare("UPDATE {$SQLprefix}assignments SET sktchAssgn=0 WHERE revId=? AND sktchAssgn=1");
  for ($i=0; $i<count($exRevs); $i++) {
    $name = trim($exRevs[$i]);
    $revId = match_PCM_by_name($name, $committee);
    if ($revId != -1) { 
      unset($revs[$revId]);
      if (!isset($_POST['keepAssignments'])) {
	$stmt->execute(array($revId));
      }
    }
  }
}

$nReviewers = count($revs);
$load = (int) floor($nReports/$nReviewers);
$missing = $nReports - ($load * $nReviewers);

foreach ($revs as $revId => $x) {
  if ($missing > 0) {
    $revs[$revId] = $load +1;
    $missing--;
  }
  else $revs[$revId] = $load;
}

// Now prepare two 2-dimentional arrays: one for reviewer preferences and
// one for "submission preferences".   

// Heuristic: the submissions on the list of $revId are sroted not just
// by the preferences of $revId, but also (as a secondary key) by how
// compatible the chair thinks that $revId is to the submission. 

// Hack: in order to use rsort() below, we pack both primary and
// secondary keys into one integer. The code below only works as
// long as the secondary key has a range of size less than 100 

$revPrefs = array();
foreach ($revs as $revId => $x) {
  $revPrefs[$revId] = array();
  foreach ($subs as $subId => $y)
    $revPrefs[$revId][$subId] = 300; // that's the default value
}
$subPrefs = array();
foreach ($subs as $subId => $y) {
  $subPrefs[$subId] = array();
  foreach ($revs as $revId => $x) { 
    $subPrefs[$subId][$revId] = 0;   // that's the default value
  }
}


// Get the prefernces from the database and overwrite the above defualt values
$qry = "SELECT revId, subId, pref, compatible, sktchAssgn FROM {$SQLprefix}assignments";
$res = pdo_query($qry);
$curAssign = array();
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $revId = (int)$row['revId'];
  $subId = (int)$row['subId'];

  if (!isset($subs[$subId]) || !isset($revs[$revId])) continue;
  if (!isset($revPrefs[$revId])) $revPrefs[$revId] = array();
  if (!isset($subPrefs[$subId])) $revPrefs[$subId] = array();

  $pref = (int)$row['pref']; // from 0 to 5
  if ($pref < 0 || $pref > 5) $pref = 3;

  $compatible = (int) $row['compatible'];         // -1, 0, or 1
  if ($compatible < -1 || $compatible > 1) $compatible = 0;

  $assign = (int) $row['sktchAssgn'];             // -2,-1, 0, or 1
  if ($assign < -2 || $assign > 1) $assign = 0;

  // record the current assignment
  if (!isset($curAssign[$revId])) $curAssign[$revId] = array();
  $curAssign[$revId][$subId] = $assign;

  // record the preferences
  if ($assign<0) { // conflict-of-interest: assign lowest possible priority
    $revPrefs[$revId][$subId] = $subPrefs[$subId][$revId] = -200000;
  } else if (isset($_POST['keepAssignments']) && $assign==1) {
    $revPrefs[$revId][$subId] = $subPrefs[$subId][$revId] = 200000;
  } else {
    $revPrefs[$revId][$subId] = ($pref*1000) + ($compatible*10);
    $subPrefs[$subId][$revId] = ($compatible*1000) + ($pref*10);
  }
}

// break ties at random
foreach ($revs as $revId => $x) foreach ($subs as $subId => $y) {
  $revPrefs[$revId][$subId] += rand(0,9);
  $subPrefs[$subId][$revId] += rand(0,9);
}

// sort the preference lists of all the reviewers
foreach ($revs as $revId => $x) { arsort($revPrefs[$revId]); }

// prepare for each reviewer and submission an initially empty list of matches.

$revMatches = array();
foreach ($revs as $revId => $x) { 
  $revMatches[$revId] = array();
}

$subMatches = array();
foreach ($subs as $subId => $y) { 
  $subMatches[$subId] = array();
}

// Now we can start the actual algorithm

$done = false;
while (!$done) {
  $done = true;     // unless something new happens, we are done

  foreach ($revs as $revId => $leftToAssign) { // go over all reviewers

    // if not fully booked yet, go over the remaining submissions in the
    //  reviewer's preference list and have the reviewer "propose" to them
    while ($leftToAssign>0 && list($subId, $pref)=each($revPrefs[$revId])){
	// add this submission on the reviewer's match list, 
	// and the reviewer on the submission's match list
	$revMatches[$revId][$subId] = $pref;
	$subMatches[$subId][$revId] = $subPrefs[$subId][$revId];
	$leftToAssign--;
	$done = false;
    }
    $revs[$revId] = $leftToAssign;
  }

  if ($done) break;  // if no new proposals were made, we are done

  foreach ($subs as $subId => $nRevs) { // go over all submissions

    // sort the submission matches and leave only the top $nRevs reviewers
    arsort($subMatches[$subId]);
    foreach($subMatches[$subId] as $revId => $x) {
      if ($nRevs > 0) $nRevs--;
      else { 
	unset($subMatches[$subId][$revId]);
	unset($revMatches[$revId][$subId]);
	$revs[$revId]++;  // reviewer lost one submission, should find another
      }
    }
  }
}

//exit("<pre>\$subMatches[0]=".print_r($subMatches[0], true)."</pre>"); //debug

// The algorithm is done, all that is left is to record the results
$stmt1 = $db->prepare("UPDATE {$SQLprefix}assignments SET sktchAssgn=? WHERE revId=? AND subId=?");
$stmt2 = $db->prepare("INSERT INTO {$SQLprefix}assignments SET revId=?, subId=?, sktchAssgn=1");
foreach ($revs as $revId => $x) foreach ($subs as $subId => $y) {
  // $aOld is -2, -1, 0, 1 or NULL
  $aOld = isset($curAssign[$revId][$subId])? $curAssign[$revId][$subId]: NULL;
  $aNew = isset($revMatches[$revId][$subId]) ? 1 : 0;

  // do not overwrite conflict-of-interest, and (depending on $_POST)
  // maybe also do not overwrite existing assignments
  if ($aOld<0 || (isset($_POST['keepAssignments']) && $aOld==1)) continue;

  if ($aNew == (int)$aOld) continue;    // nothing to update

  if (isset($aOld)) {    // modify existing entry
    $stmt1->execute(array($aNew,$revId,$subId));
  } else if ($aNew==1) { // insert a new entry
    $stmt2->execute(array($revId,$subId));
  }
}
header('Location: assignments.php');
?>
