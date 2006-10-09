<?php
// returns 0 when all is well, otherwise an error code
function storeReview($subId, $revId, $subReviewer, $conf, $score, $auxGrades,
		     $authCmnt, $pcCmnt, $chrCmnt, $watch=false, $noUpdt=false)
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

  $score = (int) trim($score);
  if ($score>0 && $score<=MAX_GRADE) {
    $qry .= "    score={$score},\n";
  } else {
    $qry .= "    score=NULL,\n";
  }

  for ($i=0; $i<$nCrit; $i++) {
    $grade = isset($auxGrades["grade_{$i}"]) ?
             ((int) trim($auxGrades["grade_{$i}"])) : 0;
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
    $qry .= "    lastModified=NOW()";
    $qry = "UPDATE reports SET $qry WHERE revId=$revId AND subId=$subId";

    backup_existing_review($subId, $revId, $nCrit, $cnnct);
  } else {
    $noUpdt = false;
    $qry .= "    lastModified=NOW(), whenEntered=NOW()";
    $qry = "INSERT into reports SET revId=$revId, subId=$subId,\n   $qry";
  }
  db_query($qry, $cnnct); // finally, insert or update the report

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
	$qry = "INSERT INTO assignments SET subId=$subId,revId=$revId,watch=1";
	db_query($qry, $cnnct);
      }
    }
  }

  return 0;
}

function backup_existing_review($subId, $revId, $nCrit, $cnnct)
{
  $qry = "SELECT subReviewer, confidence, score,\n";
  for ($i=0; $i<$nCrit; $i++) { // additional evaluation criteria
    $qry .= " grade_{$i},";
  }
  $qry .= "\n comments2authors, comments2committee, comments2chair, lastModified\n";
  $qry .= " FROM reports WHERE subId=$subId AND revId=$revId";
  $res = db_query($qry, $cnnct);
  if (!($review=mysql_fetch_assoc($res))) return; // database error?

  // how many versions are backed-up for this review?
  $qry = "SELECT MAX(version) FROM reportBckp WHERE subId=$subId AND revId=$revId";
  $res=db_query($qry, $cnnct);
  $row = mysql_fetch_row($res);
  $nextVersion = (($row && $row[0])? $row[0] : 0) + 1;

  $values = '';
  if (isset($review['subReviewer'])) {
    $subRev = "'".my_addslashes($review['subReviewer'],$cnnct)."'";
    $values .= "subReviewer=$subRev, ";
  }
  if (isset($review['confidence'])) {
    $conf = (int) $review['confidence'];
    $values .= "confidence=$conf, ";
  }
  if (isset($review['score'])) {
    $score = (int) $review['score'];
    $values .= "score=$score, ";
  }
  for ($i=0; $i<$nCrit; $i++) // additional evaluation criteria
    if (isset($review["grade_$i"])) {
      $grade = (int) $review["grade_$i"];
      $values .= "grade_$i=$grade, ";
    }
  if (isset($review['comments2authors'])) {
    $cmntAthr = "'".my_addslashes($review['comments2authors'],$cnnct)."'";
    $values .= "\n comments2authors=$cmntAthr,";
  }
  if (isset($review['comments2committee'])) {
    $cmntCmte = "'".my_addslashes($review['comments2committee'],$cnnct)."'";
    $values .= "\n comments2committee=$cmntCmte,";
  }
  if (isset($review['comments2chair'])) {
    $cmntChr = "'".my_addslashes($review['comments2chair'],$cnnct)."'";
    $values .= "\n comments2chair=$cmntChr,";
  }

  while (true) { // keep trying until you manage to insert to database
    $qry = "INSERT IGNORE INTO reportBckp
  SET subId=$subId, revId=$revId, $values
  whenEntered='".$review['lastModified']."', version=".$nextVersion;
    $res = mysql_query($qry, $cnnct);
    if ($res && mysql_affected_rows()>0) break; // success
    else $nextVersion++;                        // try again
  }
}
?>