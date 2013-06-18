<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$subArray = $res->fetchAll(PDO::FETCH_NUM);

$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & ". FLAG_IS_CHAIR. ") ORDER BY revId";
$res = pdo_query($qry);
$committee = array();
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]), 0);
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}
$cmteIds = array_keys($committee);

// Make sure that there is a record for each (revId,subId) pair

// Get the assignment preferences
$qry = "SELECT revId, subId, pref, compatible, sktchAssgn, assign FROM {$SQLprefix}assignments";
$res = pdo_query($qry);
$prefs = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) { 
  list($revId, $subId, $pref, $compatible, $assign, $assign2) = $row; 
  if (!isset($prefs[$subId])) $prefs[$subId] = array();

  $prefs[$subId][$revId] = array($pref, $compatible, $assign, $assign2);
}

// Make user-indicated changes
if (isset($_POST["saveAssign"])) { // input from matrix interface
  $qry = "UPDATE {$SQLprefix}assignments SET sktchAssgn=?";
  if (isset($_POST["visible"])) $qry .= ", assign=?";
  $qry .= " WHERE revId=? AND subId=?";
  $stmtUpdt = $db->prepare($qry);

  $qry = "INSERT INTO {$SQLprefix}assignments SET revId=?,subId=?,sktchAssgn=?";
  if (isset($_POST["visible"])) $qry .= ",assign=?";
  $stmtInsrt = $db->prepare($qry);

  foreach($subArray as $sub) foreach($cmteIds as $revId) {
    $subId = (int) $sub[0];
    $assgn = isset($_POST["a_{$subId}_{$revId}"]) ? 1 : 0;

    // do not override a conflict
    if (isset($prefs[$subId][$revId][2])
	&& $prefs[$subId][$revId][2]<0) $assgn=$prefs[$subId][$revId][2];

    if (isset($prefs[$subId][$revId])) {  // modify existing entry
      if (($prefs[$subId][$revId][2]==$assgn) &&      // nothing changed
	  ($prefs[$subId][$revId][3]==$assgn || !isset($_POST["visible"])))
	continue;

      $prms = isset($_POST["visible"])? 
	array($assgn,$assgn,$revId,$subId): array($assgn,$revId,$subId);
      $stmtUpdt->execute($prms);
    }
    
    if (!isset($prefs[$subId][$revId]) && $assgn!=0) {// inser a new entry
      if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }
      $prefs[$subId][$revId] = array(3, 0, $assgn, $assgn);

      $prms = isset($_POST["visible"])? 
	array($revId,$subId,$assgn,$assgn): array($revId,$subId,$assgn);

      $stmtInsrt->execute($prms);
    }
  }
  header("Location: assignmentMatrix.php");
}
else if (isset($_POST["manualAssign"])) { // input from list interface
  $qry = "UPDATE {$SQLprefix}assignments SET sktchAssgn=1";
  if (isset($_POST["visible"])) $qry .= ", assign=1";
  $qry .= " WHERE revId=? AND subId=?";
  $stmtUpdt = $db->prepare($qry);

  $qry = "INSERT INTO {$SQLprefix}assignments SET revId=?,subId=?,sktchAssgn=1";
  if (isset($_POST["visible"])) $qry .= ",assign=1";
  $stmtInsrt = $db->prepare($qry);

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

      if ($revId==-1 || (isset($prefs[$subId][$revId]) && $prefs[$subId][$revId][2]<0)) continue;
      $newAssignment[$subId][$revId]=1;

      $list .= $revId . ', ';
      if (!isset($prefs[$subId][$revId])) { // insert new entry
	$prefs[$subId][$revId] = array(3,0,1,1);
	$stmtInsrt->execute(array($revId,$subId));
      }
      else if ($prefs[$subId][$revId][2] != 1 ||  // update existing entry
	       ($prefs[$subId][$revId][3]!= 1 && isset($_POST["visible"]))) {
	$prefs[$subId][$revId][2] = 1;
	if (isset($_POST["visible"])) $prefs[$subId][$revId][3] = 1;
	$stmtUpdt->execute(array($revId,$subId));
      }
    }

    // Remove all other assignments to $subId from database and $prefs
    $qry = "UPDATE {$SQLprefix}assignments SET sktchAssgn=0";
    if (isset($_POST["visible"])) $qry .= ", assign=0";
    $qry .= " WHERE subId=? AND revId NOT IN ({$list}0) AND sktchAssgn=1";
    pdo_query($qry, array($subId));
    if (isset($prefs[$subId])) foreach ($prefs[$subId] as $revId => $p) {
      if ($p[2]==1 && !isset($newAssignment[$subId][$revId]))
	$prefs[$subId][$revId][2] = 0;
    }
  }
  header("Location: assignmentList.php");
}
?>
