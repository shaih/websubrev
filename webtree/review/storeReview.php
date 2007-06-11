<?php
// returns 0 when all is well, otherwise an error code
function storeReview($subId, $revId, $subReviewer, $conf, $score, $auxGrades,
		     $authCmnt, $pcCmnt, $chrCmnt, $slfCmnt,
		     $watch=false, $noUpdt=false, $saveDraft=false)
{
  global $criteria;
  $nCrit = count($criteria);

  if (!isset($subId)) return -1;  // error: submission-id not specified
  if (!isset($revId)) return -2;  // error: reviewer-id not specified

  // Make sure that this submission exists and the reviewer does not have
  // a conflict with it, and check if this is a new review or an update.
  $cnnct = db_connect();
  $qry= "SELECT s.subId, a.assign, r.revId
       FROM submissions s
            LEFT JOIN assignments a ON a.revId=$revId AND a.subId=s.subId
            LEFT JOIN reports r ON r.subId=s.subId AND r.revId=$revId
       WHERE s.subId=$subId";

  $res = db_query($qry, $cnnct);
  if (!($row = mysql_fetch_row($res)) || $row[1]==-1)
    return -3; // no such submission or reviewer has conflict

  if ($saveDraft) {
    $qry = " flags=0,";
  } else {
    $qry = " flags=1,";
  }

  if (!empty($subReviewer)) {
    $qry .= " subReviewer='" .my_addslashes($subReviewer, $cnnct)."',\n";
  } else {
    $qry .= " subReviewer=NULL,\n";
  }

  $conf = (int) trim($conf);
  if ($conf>0 && $conf<=MAX_CONFIDENCE) {
    $qry .= "    confidence={$conf},\n";
  } else {
    $qry .= "    confidence=NULL,\n";
  }

  $score = (int) trim($score);
  if ($score>0 && $score<=MAX_GRADE) {
    $qry .= "    score={$score},\n";
  } else {
    $qry .= "    score=NULL,\n";
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

  $cmnts = trim($slfCmnt);
  if (!empty($cmnts)) {
    $qry .= "    comments2self='" .my_addslashes($cmnts, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2self=NULL,\n";
  }

  if (isset($row[2])) {  // existing entry
    $qry .= "    lastModified=NOW()";
    $qry = "UPDATE reports SET $qry WHERE revId=$revId AND subId=$subId";

    backup_existing_review($subId, $revId, $nCrit, $cnnct);
  } else {
    $noUpdt = false;
    $qry .= "    lastModified=NOW(), whenEntered=NOW()";
    $qry = "INSERT into reports SET revId=$revId, subId=$subId,\n   $qry";
  }
  $vals = $comma = '';
  for ($i=0; $i<$nCrit; $i++) {
    $grade = isset($auxGrades["grade_{$i}"])?((int)$auxGrades["grade_{$i}"]):0;
    $mx = $criteria[$i][1];
    if ($grade<=0 || $grade>$mx) $grade='NULL';
    $vals .= $comma . "($subId, $revId, $i, $grade)";
    $comma = ',';
  }
  // finally, insert or update the report
  mysql_query("BEGIN", $cnnct);
          // same as "START TRANSCTION" but works with older versions of MySQL
  db_query($qry, $cnnct);
  db_query("DELETE FROM auxGrades WHERE subId=$subId AND revId=$revId",$cnnct);
  if (!empty($vals))
    db_query("INSERT INTO auxGrades VALUES $vals", $cnnct);
  mysql_query("COMMIT", $cnnct); // commit changes


  // Update the statistics in the submissions table
  $qry = "SELECT AVG(score), MIN(score), MAX(score),
          SUM(score*confidence), SUM(IF(score IS NULL, 0, confidence))
  FROM reports WHERE subId=$subId";
  $res = db_query($qry, $cnnct);

  if ($row = mysql_fetch_row($res)) { // that had better be the case

    $avg = isset($row[0]) ? $row[0] : "NULL";
    $min = isset($row[1]) ? $row[1] : "NULL";
    $max = isset($row[2]) ? $row[2] : "NULL";
    $wAvg = (isset($row[3]) && isset($row[4])) ?
      ($row[3] / ((float)$row[4])) : "NULL"; 
    if (!isset($wAvg)) $wAvg = "NULL";

    $qry = "UPDATE submissions SET avg={$avg}, wAvg={$wAvg}, minGrade={$min}, maxGrade={$max}, ";
    if ($noUpdt) $qry .= "lastModified=lastModified WHERE subId=$subId";
    else         $qry .= "lastModified=NOW() WHERE subId=$subId";
    db_query($qry, $cnnct);

    // also add the submission to reviewer's watch list is asked to
    if ($watch) {
      $qry = "UPDATE assignments SET watch=1 WHERE subId=$subId AND revId=$revId";
      db_query($qry, $cnnct);
      if (mysql_affected_rows()==0) { // insert a new entry to table
	$qry = "INSERT IGNORE INTO assignments SET subId=$subId,revId=$revId,watch=1";
	db_query($qry, $cnnct);
      }
    }
  }

  return 0;
}

function backup_existing_review($subId, $revId, $nCrit, $cnnct)
{
  // how many versions are backed-up for this review?
  $qry = "SELECT MAX(version) FROM reportBckp WHERE subId=$subId AND revId=$revId";
  $res=db_query($qry, $cnnct);
  $row = mysql_fetch_row($res);
  $nextVersion = (($row && $row[0])? $row[0] : 0) + 1;

  /* The loop below is guarding against an extremely unlikely race
     condition, and it was reported to cause problems in some cases
     (maybe due to a buggy implementation of mysql_affected_rows?),
     so I removed it.
  ********************************************************************
  while (true) { // keep trying until you manage to insert to database
   $qry = "INSERT IGNORE INTO reportBckp SELECT subId, revId, subReviewer, confidence, score, comments2authors, comments2committee, comments2chair, lastModified, $nextVersion FROM reports WHERE subId=$subId AND revId=$revId";
    $res = mysql_query($qry, $cnnct);
    if ($res && mysql_affected_rows()>0) break; // success
    else $nextVersion++;                        // try again
  }
  *******************************************************************/
  $qry = "INSERT IGNORE INTO reportBckp SELECT subId, revId, flags, subReviewer, confidence, score, comments2authors, comments2committee, comments2chair, comments2self, lastModified, $nextVersion FROM reports WHERE subId=$subId AND revId=$revId";
  mysql_query($qry, $cnnct);

  $qry = "INSERT IGNORE INTO gradeBckp SELECT subId, revId, gradeId, grade, $nextVersion FROM auxGrades WHERE subId=$subId AND revId=$revId";
  mysql_query($qry, $cnnct);
}
?>