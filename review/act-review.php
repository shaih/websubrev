<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];

if (defined('CAMERA_PERIOD'))
   exit("<h1>Site closed: cannot post new reviews</h1>");

if (isset($_POST['subId'])) { $subId = (int) trim($_POST['subId']); }
else exit("<h1>No Submission specified</h1>");

// Make sure that this submission exists and the reviewer does not have
// a conflict with it, and check if this is a new review or an update.
$cnnct = db_connect();
$qry= "SELECT s.subId, a.assign, r.revId
       FROM submissions s
            LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId
            LEFT JOIN reports r ON r.subId=s.subId AND r.revId='$revId'
       WHERE s.subId='$subId'";

$res = db_query($qry, $cnnct);
if (!($row = mysql_fetch_row($res))
    || $row[1]==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$subReviewer = trim($_POST['subRev']);
if (!empty($subReviewer)) {
  $qry = " subReviewer='" .my_addslashes($subReviewer, $cnnct)."',\n";
} else {
  $qry = " subReviewer=NULL,\n";
}

$conf = (int) trim($_POST['conf']);
if ($conf>0 && $conf<=MAX_CONFIDENCE) {
  $qry .= "    confidence={$conf},\n";
} else {
  $qry .= "    confidence=NULL,\n";
}

$grade = (int) trim($_POST['grade']);
if ($grade>0 && $grade<=MAX_GRADE) {
  $qry .= "    grade={$grade},\n";
} else {
  $qry .= "    grade=NULL,\n";
}

for ($i=0; $i<count($criteria); $i++) {
  $grade = (int) trim($_POST["grade_{$i}"]);
  $mx = $criteria[$i][1];
  if ($grade>0 && $grade<=$mx) {
    $qry .= "    grade_{$i}={$grade},\n";
  } else {
    $qry .= "    grade_{$i}=NULL,\n";
  }
}

$cmnts = trim($_POST['comments2authors']);
if (!empty($cmnts)) {
  $qry .= "    comments2authors='" .my_addslashes($cmnts, $cnnct) ."',\n";
} else {
  $qry .= "    comments2authors=NULL,\n";
}

$cmnts = trim($_POST['comments2PC']);
if (!empty($cmnts)) {
  $qry .= "    comments2committee='" .my_addslashes($cmnts, $cnnct) ."',\n";
} else {
  $qry .= "    comments2committee=NULL,\n";
}

$cmnts = trim($_POST['comments2chair']);
if (!empty($cmnts)) {
  $qry .= "    comments2chair='" .my_addslashes($cmnts, $cnnct) ."',\n";
} else {
  $qry .= "    comments2chair=NULL,\n";
}

$qry .= "    whenEntered=NOW()";

if (isset($row[2])) {  // existing entry
  $qry = "UPDATE reports SET " . $qry
    . "\n  WHERE revId=$revId AND subId=$subId";
} else {
  $qry = "INSERT into reports SET revId=$revId, subId=$subId,\n   ".$qry;
}
db_query($qry, $cnnct);

// Update the statistics in the submissions table
$qry = "SELECT AVG(grade), MIN(grade), MAX(grade),
        SUM(grade*confidence), SUM(IF(grade IS NULL, 0, confidence))
  FROM reports WHERE subId='$subId'";
$res = db_query($qry, $cnnct);

if ($row = mysql_fetch_row($res)) { // that better be the case

  $avg = isset($row[0]) ? $row[0] : "NULL";
  $min = isset($row[1]) ? $row[1] : "NULL";
  $max = isset($row[2]) ? $row[2] : "NULL";
  $wAvg = (isset($row[3]) && isset($row[4])) ?
    ($row[3] / ((float)$row[4])) : "NULL"; 
  if (!isset($wAvg)) $wAvg = "NULL";

  $qry = "UPDATE submissions SET avg={$avg}, wAvg={$wAvg}, minGrade={$min}, maxGrade={$max}, lastModified=NOW() WHERE subId='$subId'";
  db_query($qry, $cnnct);
}

header("Location: receipt-report.php?subId={$subId}&revId={$revId}");
?>
