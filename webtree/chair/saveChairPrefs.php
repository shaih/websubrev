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

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 from {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$subArray = pdo_query($qry)->fetchAll(PDO::FETCH_NUM);

$qry = "SELECT revId, name from {$SQLprefix}committee WHERE revId NOT IN(".implode(", ", chair_ids()).") ORDER BY revId";
$res = pdo_query($qry);
$committee = array();
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]));
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}

// read current chair-preferences from database
$curPrefs = array();
$qry = "SELECT revId, subId, compatible FROM {$SQLprefix}assignments WHERE revId NOT IN(".implode(", ", chair_ids()).") ORDER BY subId, revId";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
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
    $stmt1 = $db->prepare("INSERT INTO {$SQLprefix}assignments SET revId=?, subId=?, compatible=?");
    $stmt2 = $db->prepare("UPDATE {$SQLprefix}assignments SET compatible=? WHERE revId=? AND subId=?");
    foreach ($x as $revName) {
      $revName = trim($revName); if (empty($revName)) continue;
      $revId = match_PCM_by_name($revName, $committee);
      if ($revId == -1) continue;

      $prefs[$subId][$revId] = $compatible;
      if (!isset($curPrefs[$subId][$revId])) {     // inser a new entry
	$stmt1->execute(array($revId,$subId,$compatible));
      }
      else if ($curPrefs[$subId][$revId] != $compatible) {// modify entry
	$stmt2->execute(array($compatible,$revId,$subId));
	$curPrefs[$subId][$revId] = $compatible;
      }
    }
  }
  // entries in $curPrefs but not in $prefs should be set to 0 in database
  $stmt1 = $db->prepare("UPDATE {$SQLprefix}assignments SET compatible=0 WHERE revId=? AND subId=?");
  foreach($curPrefs as $subId => $revList)
    foreach ($revList as $revId => $compatible)
      if ($compatible != 0 && !isset($prefs[$subId][$revId])) {
	$stmt1->execute(array($revId,$subId));
      }
}
header("Location: assignChairPrefs.php");
?>
