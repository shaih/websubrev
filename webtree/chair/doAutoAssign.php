<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';
require '../includes/EdmondsKarp.php';
require 'parseAssignments.php';
$cnnct = db_connect();

if (!isset($_POST['specialSubs']))  $_POST['specialSubs'] = '';
if (!isset($_POST['cListExclude'])) $_POST['cListExclude'] = '';
if (!isset($_POST['startFrom']))    $_POST['startFrom'] = 'current';

$cvrg = trim($_POST['subCoverage']);
if (empty($cvrg)) $cvrg = 3;
else $cvrg = (int) $cvrg;

$spclCvrg = trim($_POST['specialCoverage']);
if (empty($spclCvrg)) $spclCvrg = $cvrg+1;
else $spclCvrg = (int) $spclCvrg;

// Record the choices from the auto-assign form
$qry = "excludedRevs=?,specialSubs=?,coverage=$cvrg,spclCvrge=$spclCvrg,startFrom=?";
pdo_query("INSERT IGNORE INTO {$SQLprefix}assignParams SET idx=1,$qry",
	  array($_POST['cListExclude'],$_POST['specialSubs'],$_POST['startFrom']));
pdo_query("UPDATE {$SQLprefix}assignParams SET $qry WHERE idx=1",
	  array($_POST['cListExclude'],$_POST['specialSubs'],$_POST['startFrom']));

// If statrFrom is not 'current', set the starting point for the algorithm
if ($_POST['startFrom']=='scratch') {
  pdo_query("UPDATE {$SQLprefix}assignments SET sktchAssgn=0 WHERE sktchAssgn=1");
}
elseif ($_POST['startFrom']=='file' && isset($_FILES['assignmnetFile'])) {
  parse_assignment_file($_FILES['assignmnetFile']['tmp_name']);
}

// Get the lists of submissions. For each submission we record the
// number of reviewers that should be assigned to it. We assign the
// same number to each submission (3 by default), but "special"
// submissions can be assigned more reviewers. 

// submissions that need more reviewers
$spclSubs = explode(',', $_POST['specialSubs']);// semi-colon separated list
for ($i=0; $i<count($spclSubs); $i++) {
  $spclSubs[$i] = (int) trim($spclSubs[$i]);
}

$res = pdo_query("SELECT subId FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId");
$subs = array();
$subCoverage = array(); // how much coverage a submission needs
$curCoverage = array(); // how much coverage it already has
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = (int) $row[0];
  $subs[] = $subId;
  if (in_array($subId, $spclSubs))  // this submission is "special"
       $subCoverage[$subId] = $spclCvrg;
  else $subCoverage[$subId] = $cvrg;
  $curCoverage[$subId] = 0;         // this will be overwritten later
}
$nSubmissions = count($subs);  // how many submissions we have

// Get the list of reviewers: put them all in the committee() array, and
// put all *except the excluded reviewers and chair* in the revs() array
$res = pdo_query("SELECT name,revId FROM {$SQLprefix}committee ORDER BY revId");
$committee = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int)$row[1];
  if ($revId>0) $committee[$revId] = $row; // to be used with match_PCM_by_name
}

// The optional input excludeRevs is a semi-colon-separated list of
// reviewers that should not be assigned any submissions (e.g., you
// may not want to assign submissions to the chair(s)).
$excluded = chair_ids();
$exRevs = explode(';', $_POST['cListExclude']);
foreach ($exRevs as $name) {
  $revId = match_PCM_by_name($name, $committee);
  if ($revId != -1) $excluded[] = $revId;
}

$revs = array();
$curLoad = array();   // $curLoad[rev] = how many subs this rev already has
foreach ($committee as $revId=>$x) {
  if (in_array($revId, $excluded)) continue;
  $revs[] = $revId;
  $curLoad[$revId] = 0;  // This will be overwritten later
}
$nReviewers = count($revs);

// Prepare a 2-dimentional array for reviewer preferences, assignments
$revPrefs = array();
$curAssign = array(); // $curAssign[rev][sub] is -2,-1,0 or 1
foreach ($revs as $revId) {
  $revPrefs[$revId] = array();
  $curAssign[$revId]= array();
  foreach ($subs as $subId) {  // set defaults values
    $revPrefs[$revId][$subId] = 3;
    $curAssign[$revId][$subId]= 0;
  }
}

// Get the preferences and existing assignments from the database and
// overwrite the above defualt values
$qry = "SELECT revId,subId,pref,compatible,sktchAssgn FROM {$SQLprefix}assignments ORDER BY revId,subId";
$res = pdo_query($qry);
$exRevLoad = 0;   // how much of the load is assigned to the excluded reviewers
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $subId = (int)$row['subId'];
  if (!in_array($subId,$subs)) continue;

  $revId = (int)$row['revId'];
  $assign = (int) $row['sktchAssgn'];    // -2,-1, 0, or 1
  if ($assign < -2 || $assign > 1) $assign = 0;
  elseif ($assign==1) {
    $curCoverage[$subId]++;
    if (in_array($revId, $excluded)) $exRevLoad++;
  }
  if (!in_array($revId, $revs)) continue;

  $pref = (int)$row['pref'];             // from 0 to 5
  $compatible = (int)$row['compatible']; // -1, 0, or 1

  // Use the chair's preference to modify the reviewer's preferences
  if ($compatible<0) $pref-= 2;
  elseif ($compatible>0 && $pref>=3) $pref++;
  if ($assign<0) $pref = 0;           // conflict of interests
  if ($pref < 0) $pref=0;
  elseif ($pref > 6) $pref = 3;         // pref can be upto 6 (if compatible>0)

  // record the current assignment and preference
  if ($assign==1) $curLoad[$revId]++;

  $curAssign[$revId][$subId]= $assign;
  $revPrefs[$revId][$subId] = $pref;
}

// How many reports are needed from non-excluded reviewers
$nReports = array_sum($subCoverage)-$exRevLoad;
$load = (int) floor($nReports/$nReviewers);

/*print "<pre>\n";
print_r($curCoverage);
exit("\n</pre>\n");
*/

// Count how many submissions each reviewer marked at each level
$nRevPrefs = array();
foreach ($revs as $revId)
  $nRevPrefs[$revId] = array(0,0,0,0,0,0);
$nSubPrefs = array();
foreach ($subs as $subId)
  $nSubPrefs[$subId] = array(0,0,0,0,0,0);

foreach($revPrefs as $revId => $pfs) {
  foreach ($pfs as $subId=>$pref) {
    if ($pref==6) $pref=5;       // pref-5 and pref-6 are counted together
    $nRevPrefs[$revId][$pref]++;
    $nSubPrefs[$subId][$pref]++;
  }
  // If a reviewer does not have any pref-5, elevate all the pref-4 to pref-5
  if ($nRevPrefs[$revId][5]==0 && $nRevPrefs[$revId][4]>0) {
    foreach ($pfs as $subId=>$pref) if ($pref==4) {
      $revPrefs[$revId][$subId]=5;
      $nSubPrefs[$subId][4]--;
      $nSubPrefs[$subId][5]++;
    }
    $nRevPrefs[$revId][5] = $nRevPrefs[$revId][4];
    $nRevPrefs[$revId][4] = 0;
  }
}

/* Prepare a flow network source -> reviewers -> submissions -> sink.
 *
 * Each reviewer has four nodes pref6 -> pref5 -> pref4 -> pref3
 *
 * + There is a capacity-1 edge from prefX to every submission that
 *   the reviewer marked with pref X.
 * + The capacity from the source to pref6 and from pref6 to pref5 is
 *   the load that should be added to this reviewer (i.e., the global
 *   load minus the number of papers that are already assigned to him/her).
 * + The capacity from pref5 to pref4 is set to is the load minus
 *   something, to force at least a few assignments with pref-5/6 for
 *   each reviewer. The "something" is set to half the smallest of the
 *   number of pref-5 submissions of this reviewer and the global load,
 *   rounded down. (For reviewers that do not have any pref-5/6, all
 *   their pref-4 are elevated to pref-5.)
 * + The capacity from pref4 to pref3 is set to 20% of the load. However,
 *   it is increased if there is a need to make sure that the total of
 *   pref4-to-pref3 capacity plus the number of pref-5 plus the number
 *   of pref-4 is at least 120% of the total load. 
 * 
 * From each submission there is an edge to the sink with capacity
 * equal the number of reviewers that should be assigned to that
 * submission, minus the number of reviewers that are already assigned
 * to it.
 */

// The sink is node 0, the nodes for the reviewr $revs[$i] are
// 4i+1 (pref6) through 4i+4 (pref3), the node for submission
// $subs[$i] is 4*nReviewers+i+1, and the sink is node number
// 4*nReviewers + nSubmissions+1

$caps = array(0=>array());
$firstSub = 4*$nReviewers +1;

for ($i=0; $i<$nReviewers; $i++) {
  $revId = $revs[$i];

  // Record the capacity from the source to the pref-5/6 reviewer nodes
  $caps[0][4*$i+1] = $caps[4*$i+1][4*$i+2] = $load - $curLoad[$revId];

  // Record the capacity from the reviewer to submissions
  for ($j=0; $j<$nSubmissions; $j++) {
    $subId = $subs[$j];
    if (empty($curAssign[$revId][$subId])) {
      if ($revPrefs[$revId][$subId]==6) $caps[4*$i+1][$firstSub+$j] = 1;
      elseif ($revPrefs[$revId][$subId]==5) $caps[4*$i+2][$firstSub+$j] = 1;
      elseif ($revPrefs[$revId][$subId]==4) $caps[4*$i+3][$firstSub+$j] = 1;
      elseif ($revPrefs[$revId][$subId]==3) $caps[4*$i+4][$firstSub+$j] = 1;
    }
  }
}

// Record the capacity from the submission nodes to the sink
for ($j=0; $j<$nSubmissions; $j++) {
  $subId = $subs[$j];
  $caps[$firstSub+$j][$firstSub+$nSubmissions]
    = $subCoverage[$subId] - $curCoverage[$subId];
  if ($caps[$firstSub+$j][$firstSub+$nSubmissions] < 0)
    $caps[$firstSub+$j][$firstSub+$nSubmissions] = 0;
}

// Find the maximum flow in this network
$flow = array();
$m = maximum_flow($flow, $caps, 0, $firstSub+$nSubmissions);

// Add capacity from pref-5 to pref-4 nodes
for ($i=0; $i<$nReviewers; $i++) {
  $revId = $revs[$i];
  $delta=floor(($load<$nRevPrefs[$revId][5]? $load: $nRevPrefs[$revId][5])/2);
  $caps[4*$i+2][4*$i+3] = $caps[0][4*$i+1] - $delta;
}
$m += maximum_flow($flow, $caps, 0, $firstSub+$nSubmissions);

// Add capacity from pref-4 to pref-3 nodes
for ($i=0; $i<$nReviewers; $i++) {
  $revId = $revs[$i];
  $caps[4*$i+3][4*$i+4] = floor($load/5);
  if ($caps[4*$i+3][4*$i+4] < 
      $load*6/5-$nRevPrefs[$revId][5]-$nRevPrefs[$revId][4]) {
    $caps[4*$i+3][4*$i+4]
      = floor($load*6/5)-$nRevPrefs[$revId][5]-$nRevPrefs[$revId][4];
  }
}
$m += maximum_flow($flow, $caps, 0, $firstSub+$nSubmissions);

// If we still do not have full coverage then increase the load
if ($m < $nReports) for ($i=0; $i<$nReviewers; $i++) {
  $caps[0][4*$i+1]++;
  $caps[4*$i+1][4*$i+2]++;
  $caps[4*$i+2][4*$i+3]++;
  $caps[4*$i+3][4*$i+4]++;
  $m += maximum_flow($flow, $caps, 0, $firstSub+$nSubmissions);
}

// The algorithm is done, all that is left is to record the results
$stmtInsrt= $db->prepare("INSERT IGNORE INTO {$SQLprefix}assignments SET revId=?, subId=?, sktchAssgn=?");
$stmtUpdt = $db->prepare("UPDATE {$SQLprefix}assignments SET sktchAssgn=? WHERE revId=? AND subId=?");
for ($i=0; $i<$nReviewers; $i++) {
  $revId = $revs[$i];
  for ($j=0; $j<$nSubmissions; $j++) {
    $subId = $subs[$j];
    $aOld = isset($curAssign[$revId][$subId])? $curAssign[$revId][$subId]: NULL; // -1, 0, or 1

    $aNew = (!empty($flow[4*$i+1][$firstSub+$j])
	     || !empty($flow[4*$i+2][$firstSub+$j])
	     || !empty($flow[4*$i+3][$firstSub+$j])
	     || !empty($flow[4*$i+4][$firstSub+$j]));

    // do not overwrite conflict-of-interest
    if ($aNew && $aOld==0) {
      $stmtInsrt->execute(array($revId,$subId,$aNew)); // insert if not there
      $stmtUpdt->execute(array($aNew,$revId,$subId));  // update
    }
  }
}
header('Location: assignmentMatrix.php');
?>
