<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

// Get current preferences/sketch-assignments.
// We maintain an invariant that sktchAssgn==-1,-2 iff assign==-1,-2

// Prepare an array of submissions and an array of PC members
$res = pdo_query("SELECT subId FROM {$SQLprefix}submissions ORDER BY subId");
$subArray = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = $row[0];
  $subArray[$subId] = true;
}

$qry = "SELECT revId FROM {$SQLprefix}committee WHERE !(flags & ". FLAG_IS_CHAIR .") ORDER BY revId";
$res = pdo_query($qry);
$committee = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = $row[0];
  $committee[$revId] = true;
}

// read the current blocked submissions from the database
$qry = "SELECT subId, revId, assign FROM {$SQLprefix}assignments ORDER BY revId, subId";
$res = pdo_query($qry);
$current = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) { 
  list($subId, $revId, $assign) = $row;
  if (!isset($current[$revId])) $current[$revId] = array();
  $current[$revId][$subId] = $assign;
}

$stmt1 = $db->prepare("INSERT INTO {$SQLprefix}assignments SET sktchAssgn=?, assign=?, subId=?, revId=?");
$stmt2 = $db->prepare("UPDATE {$SQLprefix}assignments SET sktchAssgn=?, assign=? WHERE subId=? AND revId=?");
foreach ($_POST['block'] as $revId => $lst) {
  if ($revId<=0 || !isset($committee[$revId])) continue;

  foreach ($lst as $subId => $assign) {
    if ($subId<=0 || !isset($subArray[$subId])) continue;

    if (!isset($current[$revId][$subId]))         // insert new entry
      $stmt1->execute(array($assign,$assign,$subId,$revId));
    else if ($current[$revId][$subId] != $assign) // modify existing entry
      $stmt2->execute(array($assign,$assign,$subId,$revId));
  }
}

// Remove from database all the submission blocks that are not
// specified by the $_POST array

$stmt1 = $db->prepare("UPDATE {$SQLprefix}assignments SET sktchAssgn=0, assign=0 WHERE subId=? AND revId=?");
foreach ($current as $revId => $pcmList) foreach ($pcmList as $subId => $a) {
  if ($a<0 && !isset($_POST["block"][$revId][$subId]))
    $stmt1->execute(array($subId,$revId));
}

header("Location: index.php");
?>
