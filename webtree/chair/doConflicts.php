<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

// Get current preferences/assignments
$cnnct = db_connect();

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId from submissions ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row = mysql_fetch_row($res)) {
  $subId = $row[0];
  $subArray[$subId] = true;
}

$qry = "SELECT revId from committee WHERE !(flags & " . FLAG_IS_CHAIR . ")
   ORDER BY revId";
$res = db_query($qry, $cnnct);
$committee = array();
while ($row = mysql_fetch_row($res)) {
  $revId = $row[0];
  $committee[$revId] = true;
}

$qry = "SELECT subId, revId, sktchAssgn FROM assignments ORDER BY revId, subId";
$res = db_query($qry, $cnnct);
$current = array();
while ($row = mysql_fetch_row($res)) { 
  list($subId, $revId, $assign) = $row;
  if (!isset($current[$revId])) $current[$revId] = array();
  $current[$revId][$subId] = $assign;
}

foreach ($_POST as $nm => $val) {
  if (empty($nm) || trim($val) != "on") continue;
  $nm = explode('_', $nm);
  if ($nm[0] != "b") continue;
  $revId = (int) $nm[1];
  $subId = (int) $nm[2];
  if ($subId <= 0 || $revId <= 0) continue;

  $qry = NULL;
  if (!isset($current[$revId][$subId])) {    // insert new entry
    $qry = "INSERT INTO assignments SET subId ='{$subId}', revId='{$revId}', sktchAssgn=-1, assign=-1";
  }
  else if ($current[$revId][$subId] != -1) { // modify existing entry
    $qry = "UPDATE assignments SET sktchAssgn=-1, assign=-1 WHERE subId ='{$subId}' AND revId='{$revId}'";
  }
  if (isset($qry)) db_query($qry, $cnnct);
}

// Remove from database all the submission blocks that are not
// specified by the $_POST array

foreach ($current as $revId => $pcmList) foreach ($pcmList as $subId => $a) {
  $subId = (int) $subId; $revId = (int) $revId;
  if ($a==-1 && !isset($_POST["b_{$revId}_{$subId}"])) {
    $qry = "UPDATE assignments SET sktchAssgn=0, assign=0 WHERE subId ='{$subId}' AND revId='{$revId}'";
    db_query($qry, $cnnct);
  }
}

header("Location: index.php");
?>
