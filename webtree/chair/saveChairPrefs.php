<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

// Get the assignment preferences
$cnnct = db_connect();

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 from submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row = mysql_fetch_row($res)) { $subArray[] = $row; }

$qry = "SELECT revId, name from committee WHERE revId NOT IN(".implode(", ", chair_ids()).") ORDER BY revId";
$res = db_query($qry, $cnnct);
$committee = array();
$nameList = $sep = '';
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]));
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}

// read current chair-preferences from database
$curPrefs = array();
$qry = "SELECT revId, subId, compatible FROM assignments WHERE revId NOT IN(".implode(", ", chair_ids()).") ORDER BY subId, revId";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  $subId = (int) $row[1];
  $compatible = (int) $row[2];
  if (!isset($curPrefs[$subId])) $curPrefs[$subId] = array();
  $curPrefs[$subId][$revId] = $compatible;
}

// If user specified preferences, use them to update the preferences table.
if (isset($_POST["saveChairPrefs"])) {
  $prefs = array();
  foreach($_POST as $nm =>  $val) {
    $val = trim($val);

    // Look for fields with names yes<nnn> or no<nnn> (<nnn> is subisision-num)
    if (strncmp($nm, "cListYes", 8)==0) {
      $compatible = 1;
      $subId = (int) substr($nm, 8);
    } else if (strncmp($nm, "cListNo", 7)==0) {
      $compatible = -1;
      $subId = (int) substr($nm, 7);
    }
    else continue;

    if (($subId <= 0) || empty($val)) continue;
    if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }

    $x = explode(';', $val); // $val is a semi-colon-separated list
    foreach ($x as $revName) {
      $revName = trim($revName); if (empty($revName)) continue;
      $revId = match_PCM_by_name($revName, $committee);
      if ($revId == -1) continue;

      $prefs[$subId][$revId] = $compatible;
      if (!isset($curPrefs[$subId][$revId])) {     // inser a new entry
	$qry = "INSERT INTO assignments SET "
	  . "revId='{$revId}', subId='{$subId}', compatible={$compatible}";
	db_query($qry, $cnnct);
      }
      else if ($curPrefs[$subId][$revId] != $compatible) {// modify entry
	$qry = "UPDATE assignments SET compatible={$compatible} "
	  . "WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
	$curPrefs[$subId][$revId] = $compatible;
      }
    }
  }
  // entries in $curPrefs but not in $prefs should be set to 0 in database
  foreach($curPrefs as $subId => $revList)
    foreach ($revList as $revId => $compatible)
      if ($compatible != 0 && !isset($prefs[$subId][$revId])) {
	$qry = "UPDATE assignments SET compatible=0 "
	  . "WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
      }
}
header("Location: assignChairPrefs.php");
?>
