<?php
// returns 0 when all is well, otherwise an error code
function storeReview($subId, $revId, $subReviewer, $conf, $score, $auxGrades,
		     $authCmnt, $pcCmnt, $chrCmnt, $slfCmnt, $watch=false, 
		     $saveDraft=false)
{
  global $pcMember;
  global $criteria;
  $nCrit = count($criteria);

  if (!isset($subId)) return -1;  // error: submission-id not specified
  if (!isset($revId)) return -2;  // error: reviewer-id not specified

  // Make sure that this submission exists and the reviewer does not have
  // a conflict with it, and check if this is a new review or an update.
  $cnnct = db_connect();
  $qry= "SELECT s.subId, a.assign FROM submissions s
  LEFT JOIN assignments a ON a.revId=$revId AND a.subId=s.subId
  WHERE s.subId=$subId";

  $res = db_query($qry, $cnnct);
  if (!($row = mysql_fetch_row($res)) || $row[1]==-1)
    return -3;  // no such submission or reviewer has conflict

  if ($watch) { // add the submission to reviewer's watch list
    $qry ="UPDATE assignments SET watch=1 WHERE subId=$subId AND revId=$revId";
    db_query($qry, $cnnct);
    if (mysql_affected_rows()==0) { // insert a new entry to table
      $qry = "INSERT IGNORE INTO assignments SET subId=$subId,revId=$revId,watch=1";
      db_query($qry, $cnnct);
    }
  }

  // Get the details of the existing review (if any)
  $qry = "SELECT * FROM reports WHERE subId=$subId AND revId=$revId";
  $res = db_query($qry, $cnnct);
  $oldReview = mysql_fetch_assoc($res);

  $qry = "SELECT gradeId,grade FROM auxGrades
  WHERE subId=$subId AND revId=$revId ORDER BY gradeId";
  $res = db_query($qry, $cnnct);
  $oldAuxGrades = array();
  while ($row=mysql_fetch_row($res)) {
    $gId = (int) $row[0];
    $oldAuxGrades[$gId] = (int) $row[1];
  }
  
  // Store the attachment (if any)
  $fileName = NULL;
  if (isset($_FILES["attach{$subId}"])
      && $_FILES["attach{$subId}"]['error']==0) {
    $fileName = trim($_FILES["attach{$subId}"]['name']);
    $ext = file_extension($fileName);

    $fileName = sha1(uniqid(CONF_SALT . $subId . $revId));
    $fileName = "R{$subId}" . alphanum_encode(substr($fileName, 0, 12));
    if (!empty($ext)) $fileName .= ".$ext";
    $tmpFile = $_FILES["attach{$subId}"]['tmp_name'];
    $fullName = SUBMIT_DIR."/attachments/$fileName";

    if (!move_uploaded_file($tmpFile, $fullName)) {
      error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fullName) failed\n", 3, LOG_FILE);
      $fileName = NULL;
    }

    // special case: a text attachment is stored inline
    if ($ext=='txt' && filesize($fullName)<10000){ // filesize is sanity check
      $content = file_get_contents($fullName);
      if (empty($authCmnt)) $authCmnt = $content;
      else $authCmnt .= "\n=============================================\n\n"
	  .$content;

      $fileName = NULL;
    }

  } else if (isset($_POST["keepAttach{$subId}"])) { // use existing attachment
    $fileName = is_array($oldReview) ? $oldReview['attachment'] : NULL;
  }

  $flags = $saveDraft ? 0 : 1;

  $conf = (int) trim($conf);
  if ($conf<=0 || $conf>MAX_CONFIDENCE) $conf = NULL;

  $score = (int) trim($score);
  if ($score<=0 || $score>MAX_GRADE)   $score = NULL;

  $authCmnt = trim($authCmnt);
  $pcCmnt = trim($pcCmnt);
  $chrCmnt = trim($chrCmnt);
  $slfCmnt = trim($slfCmnt);

  $newAuxGrades = array();
  for ($i=0; $i<$nCrit; $i++) {
    $grade = isset($auxGrades["grade_{$i}"])?((int)$auxGrades["grade_{$i}"]):0;
    if ($grade<=0 || $grade>$criteria[$i][1]) $grade=NULL;
    $newAuxGrades[$i]=$grade;
  }
  
  // Check if anything changed vs. the stored review (if any)
  $cmp2Old = compareReview($oldReview,$subReviewer,$conf,$score,
		     $authCmnt,$pcCmnt,$chrCmnt,$slfCmnt,
		     $flags,$fileName,$oldAuxGrades,$newAuxGrades);
  if (!$cmp2Old)
    return -4; // review is identical to what's already stored in database
 

  // Prepare the query to update the review
  $qry = " flags=$flags,";

  if (!empty($subReviewer)) {
    $qry .= " subReviewer='" .my_addslashes($subReviewer, $cnnct)."',\n";
  } else {
    $qry .= " subReviewer=NULL,\n";
  }

  if (isset($conf)) $qry .= "    confidence={$conf},\n";
  else              $qry .= "    confidence=NULL,\n";

  if (isset($score)) $qry .= "    score={$score},\n";
  else               $qry .= "    score=NULL,\n";

  if (!empty($authCmnt)) {
    $qry .= "    comments2authors='" .my_addslashes($authCmnt, $cnnct)."',\n";
  } else {
    $qry .= "    comments2authors=NULL,\n";
  }

  if (!empty($pcCmnt)) {
    $qry .= "    comments2committee='" .my_addslashes($pcCmnt, $cnnct)."',\n";
  } else {
    $qry .= "    comments2committee=NULL,\n";
  }

  if (!empty($chrCmnt)) {
    $qry .= "    comments2chair='" .my_addslashes($chrCmnt, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2chair=NULL,\n";
  }

  if (!empty($slfCmnt)) {
    $qry .= "    comments2self='" .my_addslashes($slfCmnt, $cnnct) ."',\n";
  } else {
    $qry .= "    comments2self=NULL,\n";
  }

  if (isset($fileName)) { // 
   $qry .= "    attachment='$fileName',\n";
   $ret = "$fileName";
  } else {
   $qry .= "    attachment=NULL,\n";
   $ret = NULL;
  }

  if ($oldReview) {  // existing entry
    if ($cmp2Old === -1) // only change is in comments-to-self
      $qry .= "    lastModified=lastModified";
    else
      $qry .= "    lastModified=NOW()";

    $qry = "UPDATE reports SET $qry WHERE revId=$revId AND subId=$subId";

    $version = backup_existing_review($subId, $revId, $nCrit, $cnnct);
  } else {
    $qry .= "    lastModified=NOW(), whenEntered=NOW()";
    $qry = "INSERT into reports SET revId=$revId, subId=$subId,\n   $qry";
    $version = 1;
  }

  // prepare the query to update auxiliary grades
  $vals = $comma = '';
  foreach ($newAuxGrades as $i => $grade) {
    if (!isset($grade)) $grade='NULL';
    $vals .= $comma . "($subId,$revId,$i,$grade)";
    $comma = ',';
  }

  // finally, insert or update the report

  // "BEGIN" is "START TRANSCTION" but works with older versions of MySQL
  mysql_query("BEGIN", $cnnct);
  db_query($qry, $cnnct);      // insert/update the review itself
  db_query("DELETE FROM auxGrades WHERE subId=$subId AND revId=$revId",$cnnct);
  if (!empty($vals))
    db_query("INSERT INTO auxGrades (subId,revId,gradeId,grade) VALUES $vals",
	     $cnnct);
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

    $qry = "UPDATE submissions SET avg={$avg},wAvg={$wAvg},minGrade={$min},maxGrade={$max},lastModified=NOW() WHERE subId=$subId";
    db_query($qry, $cnnct);
  }

  // Add this review to change-log for this submission
  $name = mysql_real_escape_string($pcMember[1],$cnnct);
  $qry = "INSERT INTO changeLog (subId,revId,changeType,description,entered)
  VALUES ($subId,$revId,'Review','$name uploaded a review',NOW())";
  db_query($qry, $cnnct);

  return $ret;
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
  $qry = "INSERT IGNORE INTO reportBckp SELECT subId, revId, flags, subReviewer, confidence, score, comments2authors, comments2committee, comments2chair, comments2self, attachment, lastModified, $nextVersion FROM reports WHERE subId=$subId AND revId=$revId";
  mysql_query($qry, $cnnct);

  $qry = "INSERT IGNORE INTO gradeBckp SELECT subId, revId, gradeId, grade, $nextVersion FROM auxGrades WHERE subId=$subId AND revId=$revId";
  mysql_query($qry, $cnnct);

  return $nextVersion;
}


// Returns true if the new report is different from the old one (or if
// the old report does not exist). The only exception is if the new
// report differs from the old one only in comments-to-self, in which
// case this function returns the number -1 (rather than boolean true)

function compareReview($oldReview,$subReviewer,$conf,$score,
		       $authCmnt,$pcCmnt,$chrCmnt,$slfCmnt,
		       $flags,$attach,$oldAuxGrades,$newAuxGrades)
{
  if (!isset($oldReview) || !is_array($oldReview)) return true;
  if (strcmp($oldReview['subReviewer'], $subReviewer)) return true;
  if ($oldReview['confidence']!=$conf
      || $oldReview['score']  !=$score
      || $oldReview['flags']  !=$flags) return true;
  if (strcmp($oldReview['comments2authors'],  $authCmnt)) return true;
  if (strcmp($oldReview['comments2committee'],$pcCmnt))   return true;
  if (strcmp($oldReview['comments2chair'],    $chrCmnt))  return true;
  if (strcmp($oldReview['attachment'],        $attach))   return true;
  if ($oldAuxGrades != $newAuxGrades) return true;
  if (strcmp($oldReview['comments2self'],     $slfCmnt))  return -1;

  return false;
}
?>