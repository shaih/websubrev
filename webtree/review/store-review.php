<?php
// returns 0 when all is well, otherwise an error code
function storeReview($subId, $revId, $subReviewer, $conf, $grade, $auxGrades,
		     $authCmnt, $pcCmnt, $chrCmnt, $watch=false, $noUpdt=false)
{
  global $criteria;

  if (!isset($subId)) return -1;
  if (!isset($revId)) return -2;

  // Make sure that this submission exists and the reviewer does not have
  // a conflict with it, and check if this is a new review or an update.
  $cnnct = db_connect();
  $qry= "SELECT s.subId, a.assign, r.revId
       FROM submissions s
            LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId
            LEFT JOIN reports r ON r.subId=s.subId AND r.revId='$revId'
       WHERE s.subId='$subId'";

  $res = db_query($qry, $cnnct);
  if (!($row = mysql_fetch_row($res)) || $row[1]==-1) return -3;

  if (!empty($subReviewer)) {
    $qry = " subReviewer='" .my_addslashes($subReviewer, $cnnct)."',\n";
  } else {
    $qry = " subReviewer=NULL,\n";
  }

  $conf = (int) trim($conf);
  if ($conf>0 && $conf<=MAX_CONFIDENCE) {
    $qry .= "    confidence={$conf},\n";
  } else {
    $qry .= "    confidence=NULL,\n";
  }

  $grade = (int) trim($grade);
  if ($grade>0 && $grade<=MAX_GRADE) {
    $qry .= "    score={$grade},\n";
  } else {
    $qry .= "    score=NULL,\n";
  }

  for ($i=0; $i<count($criteria); $i++) {
    $grade = isset($auxGrades["grade_{$i}"]) ? ((int) trim($auxGrades["grade_{$i}"])) : 0;
    $mx = $criteria[$i][1];
    if ($grade>0 && $grade<=$mx) {
      $qry .= "    grade_{$i}={$grade},\n";
    } else {
      $qry .= "    grade_{$i}=NULL,\n";
    }
  }

  $cmnts = trim($authCmnt);
  if (!empty($cmnts)) {
    $qry .= "    comments2authors='" .my_addslashes($cmnts, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2authors=NULL,\n";
  }

  $cmnts = trim($pcCmnt);
  if (!empty($cmnts)) {
    $qry .= "    comments2committee='" .my_addslashes($cmnts, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2committee=NULL,\n";
  }

  $cmnts = trim($chrCmnt);
  if (!empty($cmnts)) {
    $qry .= "    comments2chair='" .my_addslashes($cmnts, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2chair=NULL,\n";
  }

  if (isset($row[2])) {  // existing entry
    if ($noUpdt) $qry .= "    lastModified=lastModified"; // don't update
    else         $qry .= "    lastModified=NOW()";
    $qry = "UPDATE reports SET $qry WHERE revId=$revId AND subId=$subId";
  } else {
    $noUpdt = false;
    $qry .= "    whenEntered=NOW()";
    $qry = "INSERT into reports SET revId=$revId, subId=$subId,\n   $qry";
  }
  db_query($qry, $cnnct);

  // Update the statistics in the submissions table
  $qry = "SELECT AVG(score), MIN(score), MAX(score),
          SUM(score*confidence), SUM(IF(score IS NULL, 0, confidence))
  FROM reports WHERE subId='$subId'";
  $res = db_query($qry, $cnnct);

  if ($row = mysql_fetch_row($res)) { // that better be the case

    $avg = isset($row[0]) ? $row[0] : "NULL";
    $min = isset($row[1]) ? $row[1] : "NULL";
    $max = isset($row[2]) ? $row[2] : "NULL";
    $wAvg = (isset($row[3]) && isset($row[4])) ?
      ($row[3] / ((float)$row[4])) : "NULL"; 
    if (!isset($wAvg)) $wAvg = "NULL";

    $qry = "UPDATE submissions SET avg={$avg}, wAvg={$wAvg}, minGrade={$min}, maxGrade={$max}, ";
    if ($noUpdt) $qry .= "lastModified=lastModified WHERE subId='$subId'";
    else         $qry .= "lastModified=NOW() WHERE subId='$subId'";
    db_query($qry, $cnnct);

    // also add the submission to reviewer's watch list is asked to
    if ($watch) {
      $qry = "UPDATE assignments SET watch=1 WHERE subId=$subId AND revId=$revId";
      db_query($qry, $cnnct);
    }
  }

  return 0;
}
?>
