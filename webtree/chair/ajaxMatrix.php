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

// Update Information Given to Script

if(isset($_POST['changes']) && is_array($_POST['changes'])) {
  // Prepare an array of submissions and an array of PC members
  $qry = "SELECT subId, title, 0 from submissions WHERE status!='Withdrawn' ORDER BY subId";
  $res = db_query($qry, $cnnct);
  $subArray = array();
  while ($row = mysql_fetch_row($res)) {
    $row[1] = htmlspecialchars($row[1]);
    $subArray[] = $row;
  }
  
  $qry = "SELECT revId, name from committee WHERE !(flags & ". FLAG_IS_CHAIR. ") ORDER BY revId";
  $res = db_query($qry, $cnnct);
  $committee = array();
  $nameList = $sep = '';
  while ($row = mysql_fetch_row($res)) {
    $revId = (int) $row[0];
    $committee[$revId] = array(trim($row[1]), 0);
    $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
    $sep = ",\n    ";
  }
  $cmteIds = array_keys($committee);

  // Make sure that there is a record for each (revId,subId) pair

  // Get the assignment preferences
  $qry = "SELECT revId, subId, pref, compatible, sktchAssgn FROM assignments";
  $res = db_query($qry, $cnnct);
  $prefs = array();
  while ($row = mysql_fetch_row($res)) { 
    list($revId, $subId, $pref, $compatible, $assign) = $row; 
    if (!isset($prefs[$subId])) $prefs[$subId] = array();
    
    $prefs[$subId][$revId] = array($pref, $compatible, $assign);
  }
  
  foreach($subArray as $sub) foreach($cmteIds as $revId) {
    $subId = (int) $sub[0];
    if(!isset($_POST['changes']["chk_{$subId}_{$revId}"]))
      continue;
    
    $assgn = $_POST['changes']["chk_{$subId}_{$revId}"];
    
    // do not override a conflict
    if (isset($prefs[$subId][$revId][2])
	&& $prefs[$subId][$revId][2] == -1) $assgn=-1;
    
    if (isset($prefs[$subId][$revId])  // modify existing entry
	&& (isset($_POST["visible"]) || $prefs[$subId][$revId][2]!=$assgn)){
      $prefs[$subId][$revId][2] = $assgn;
      $qry = "UPDATE assignments SET sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      $qry .= " WHERE revId='{$revId}' AND subId='{$subId}'";
      db_query($qry, $cnnct);
    }
    
    if (!isset($prefs[$subId][$revId]) && $assgn!=0) {// insert a new entry
      if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }
      $prefs[$subId][$revId] = array(3, 0, $assgn);
      $qry = "INSERT INTO assignments SET revId={$revId}, subId={$subId}, sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      db_query($qry, $cnnct);
    }
  }
}

// Fetch Current Information

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 from submissions WHERE status!='Withdrawn'
  ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
$minSubId = null;
while ($row = mysql_fetch_row($res)) {
  if (!isset($minSubId)) $minSubId = $row[0];
  $row[1] = htmlspecialchars($row[1]);
  $subArray[] = $row;
  $maxSubId = $row[0];
}
$nSubmissions = count($subArray);
$numHdrIdx=(2+intval(($nSubmissions-1)/6));

$qry = "SELECT revId, name from committee WHERE flags & ".FLAG_IS_CHAIR." = 0 ORDER BY revId";

$res = db_query($qry, $cnnct);
$committee = array();
$minRevId = null;
$nameList = $sep = '';
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  if (!isset($minRevId)) $minRevId = $revId;
  $committee[$revId] = array(trim($row[1]), 0, 0, 0);
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}
$maxRevId = $revId;
$cmteIds = array_keys($committee);

// Get the assignment preferences
$qry = "SELECT revId, subId, pref, compatible, sktchAssgn FROM assignments";
$res = db_query($qry, $cnnct);
$prefs = array();
while ($row = mysql_fetch_row($res)) { 
  list($revId, $subId, $pref, $compatible, $assign) = $row; 
  if (!isset($prefs[$subId]))  $prefs[$subId] = array();
  
  $prefs[$subId][$revId] = array(
                                 "pref"=>$pref, 
                                 "compatible"=>$compatible, 
                                 "assign"=>$assign
                                 );
}

// Compute the load for PC members and cover for submissions
foreach ($subArray as $i=>$sub) { 
  $subId = $sub[0];
  foreach ($committee as $revId => $pcm) {
    if (isset($prefs[$subId][$revId])) {
      $prf = $prefs[$subId][$revId]['pref'];
      $assgn=$prefs[$subId][$revId]['assign'];
      if ($assgn==1) {
	$subArray[$i][2]++;
	$committee[$revId][1]++;
	if ($prf>3) $committee[$revId][2]++;
	else if ($prf<3) $committee[$revId][2]--;
      }
      if ($prf>3) $committee[$revId][3]++;
    }
  }
}

// Compute the happiness level of reviewers
$happiness = array();
foreach ($committee as $revId=>$pcm) {
  $avg1 = ($pcm[1]>0) ?  // average pref of assigned submissions
          (((float)$pcm[2]) / $pcm[1]) : NULL;
  $avg2 = ($pcm[3]>0) ?  // average assign of prefrd submissions
    (((float)$pcm[2]) / $pcm[3]) : NULL;
  $happy = NULL;
  if (isset($avg1) && isset($avg2)) {
     $happy = round(max($avg1,$avg2)*100);
     if ($happy<0) $happy=0;
     else if ($happy>100) $happy=100;
  }
  $happiness[$revId] = $happy;
}

header("Content-Type: application/json");
header("Cache-Control: no-cache");

echo json_encode(
                 array(
                       "happiness"=>$happiness,
                       "committee"=>$committee,
                       "prefs"=> $prefs
                       )
                 );
