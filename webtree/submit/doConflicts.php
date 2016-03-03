<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the contacts file and utils file
//print_r($_POST);

$subId  = (int)$_POST['subId'];
$subPwd = $_POST['subPwd'];

// check that this submission exists
$qry = "SELECT subID FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=?";
$res=pdo_query($qry,array($subId, $subPwd));
$row= $res->fetch(PDO::FETCH_NUM)
    or exit("<h1>No Such Submission</h1>");

// Record conflicts
// read the current blocked submissions from the database
$qry = "SELECT revId,authConflict FROM {$SQLprefix}assignments WHERE subId=$subId";
$res = pdo_query($qry);

// First get old conflicts (if any), then overwrite with new ones
$oldConflicts = array();
$conflicts = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $conflicts[$row[0]] = $row[1];
  $oldConflicts[$row[0]] = true; // remember that this is old
}

// Add/modify from input
foreach ($_POST['c'] as $revId => $x) {
  $revId = (int)$revId; // just in case
  $reason = $_POST['r'][$revId];
  if (!empty($reason)) $conflicts[$revId] = $reason;
  else                 $conflicts[$revId] = 'unspecified';
}

// Delete old entries that are not specified in the input
foreach ($conflicts as $revId => $reason) {
  if (empty($_POST['c'][$revId]))
    $conflicts[$revId] = NULL;
}

// Record the results back in the database

$instmt = $db->prepare("INSERT IGNORE INTO {$SQLprefix}assignments SET revId=?,subId=$subId, authConflict=?");
$updstmt = $db->prepare("UPDATE {$SQLprefix}assignments SET authConflict=? WHERE revId=? AND subId=$subId");

// First try to insert, then update
foreach ($conflicts as $revId => $reason) {
  $instmt->execute(array($revId,$reason));
  $updstmt->execute(array($reason,$revId));
}

header("Location: receipt.php?subId=$subId&subPwd=$subPwd");
?>
