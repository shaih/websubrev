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

// Make user-indicated changes before displaying the matrix
if (isset($_POST["saveAssign"])) { // input from matrix interface
  foreach($subArray as $sub) foreach($cmteIds as $revId) {
    $subId = (int) $sub[0];
    $assgn = isset($_POST["a_{$subId}_{$revId}"]) ? 1 : 0;
    
    // do not override a conflict
    if (isset($prefs[$subId][$revId][2])
	&& $prefs[$subId][$revId][2] == -1) $assgn=-1;
    
    if (isset($prefs[$subId][$revId])                 // modify existing entry
	&& (isset($_POST["visible"]) || $prefs[$subId][$revId][2]!=$assgn)) {
      $prefs[$subId][$revId][2] = $assgn;
      $qry = "UPDATE assignments SET sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      $qry .= " WHERE revId='{$revId}' AND subId='{$subId}'";
      db_query($qry, $cnnct);
    }
    
    if (!isset($prefs[$subId][$revId]) && $assgn!=0) {// inser a new entry
      if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }
      $prefs[$subId][$revId] = array(3, 0, $assgn);
      $qry = "INSERT INTO assignments SET revId={$revId}, subId={$subId}, sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      db_query($qry, $cnnct);
    }
  }
  header("Location: assignmentMatrix.php");
}
else if (isset($_POST["manualAssign"])) { // input from list interface
  $newAssignment = array();
  foreach($subArray as $sub) {
    $subId = (int) $sub[0];
    if (!isset($_POST["cList{$subId}"])) continue;
    $newAssignment[$subId] = array();

    $nameList = explode(';', $_POST["cList{$subId}"]);
    $list = '';
    foreach ($nameList as $revName) {
      $revName = trim($revName); if (empty($revName)) continue;
      $revId = match_PCM_by_name($revName, $committee);

      if ($revId==-1 || (isset($prefs[$subId][$revId]) && $prefs[$subId][$revId][2]==-1)) continue;
      $newAssignment[$subId][$revId]=1;

      $list .= $revId . ', ';
      if (!isset($prefs[$subId][$revId])) { // insert new entry
	$prefs[$subId][$revId] = array(3, 0, 1);
	$qry = "INSERT INTO assignments SET revId={$revId}, subId={$subId}, sktchAssgn=1";
	if (isset($_POST["visible"])) $qry .= ", assign=1";
	db_query($qry, $cnnct);
      }
      else if (isset($_POST["visible"]) || $prefs[$subId][$revId][2] != 1) { // update existing entry
	$prefs[$subId][$revId][2] = 1;
	$qry = "UPDATE assignments SET sktchAssgn=1";
	if (isset($_POST["visible"])) $qry .= ", assign=1";
	$qry .= " WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
      }
    }

    // Remove all other assignments to $subId from database and $prefs
    $qry = "UPDATE assignments SET sktchAssgn=0";
    if (isset($_POST["visible"])) $qry .= ", assign=0";
    $qry .= " WHERE subId={$subId} AND revId NOT IN ({$list}0) AND sktchAssgn=1";
    db_query($qry, $cnnct);
    if (isset($prefs[$subId])) foreach ($prefs[$subId] as $revId => $p) {
      if ($p[2]==1 && !isset($newAssignment[$subId][$revId]))
	$prefs[$subId][$revId][2] = 0;
    }
  }
  header("Location: assignmentList.php");
}
?>
