<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication=true;
require 'header.php';  // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];

// Get the current preferences of the reviewer
$qry = "SELECT subId, pref FROM {$SQLprefix}assignments WHERE revId=? ORDER BY subId";
$res = pdo_query($qry, array($revId));
$current = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = $row[0];
  $current[$subId] = (int) $row[1];
}

// We expect the $_POST array to have the structure { subNNN => X },
// where NNN is the submission-ID and X is an integer between 0 and 5

$change = array();
foreach($_POST as $name => $value) {
  if (strncmp($name, "sub", 3)!=0 || !isset($value))
    continue;

  $subId = (int) substr($name, 3);
  if ($subId<=0) continue;

  $value = (int) $value;
  if ($value < 0 || $value > 5) continue;

  // If the specified value differs than the current one,
  // mark this for modification
  if (isset($current[$subId])) {
    if ($current[$subId] != $value) $change[$subId] = $value;
  }
  else $change[$subId] = $value;
}

$stmt1 = $db->prepare("UPDATE {$SQLprefix}assignments SET pref=? WHERE subId=? AND revId=?");
$stmt2 = $db->prepare("INSERT INTO {$SQLprefix}assignments SET subId=?, revId=?, pref=?");
foreach($change as $subId => $value) {
  if (isset($current[$subId])) { // modify existing entry
    $stmt1->execute(array($value,$subId,$revId));
  }
  else {                         // insert a new entry
    $stmt2->execute(array($subId,$revId,$value));
  }
}
return_to_caller('prefs.php');
?>
