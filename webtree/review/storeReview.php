<?php
// returns 0 when all is well, otherwise an error code
function storeReview($subId, $revId, $subReviewer, $conf, $score, $auxGrades,
		     $authCmnt, $pcCmnt, $chrCmnt, $slfCmnt, $watch=false, 
		     $saveDraft=false)
{
  global $pcMember;
  global $criteria, $SQLprefix, $db;
  $nCrit = count($criteria);

  if (!isset($subId)) return -1;  // error: submission-id not specified
  if (!isset($revId)) return -2;  // error: reviewer-id not specified

  // Make sure that this submission exists and the reviewer does not have
  // a conflict with it, and check if this is a new review or an update.
  $qry= "SELECT s.subId, a.assign FROM {$SQLprefix}submissions s
  LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
  WHERE s.subId=?";

  $res = pdo_query($qry, array($revId,$subId));
  if (!($row = $res->fetch(PDO::FETCH_NUM)) || $row[1]<0)
    return -3;  // no such submission or reviewer has conflict

  if ($watch) { // add the submission to reviewer's watch list
    $qry ="UPDATE {$SQLprefix}assignments SET watch=1 WHERE subId=? AND revId=?";
    $res = pdo_query($qry, array($subId,$revId));
    if ($res->rowCount()==0) { // insert a new entry to table
      $qry = "INSERT IGNORE INTO {$SQLprefix}assignments SET subId=?,revId=?,watch=1";
      pdo_query($qry, array($subId,$revId));
    }
  }

  // Get the details of the existing review (if any)
  $qry = "SELECT * FROM {$SQLprefix}reports WHERE subId=? AND revId=?";
  $res = pdo_query($qry, array($subId,$revId));
  $oldReview = $res->fetch(PDO::FETCH_ASSOC);

  $qry = "SELECT gradeId,grade FROM {$SQLprefix}auxGrades WHERE subId=? AND revId=? ORDER BY gradeId";
  $res = pdo_query($qry, array($subId,$revId));
  $oldAuxGrades = array();
  while ($row=$res->fetch(PDO::FETCH_NUM)) {
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
  $qry = " flags=?,";
  $prms = array($flags);

  if (!empty($subReviewer)) {
    $qry .= "subReviewer=?,";
    $prms[] = $subReviewer;
  } else {
    $qry .= "subReviewer=NULL,";
  }

  if (isset($conf)) $qry .= "confidence={$conf},";
  else              $qry .= "confidence=NULL,";

  if (isset($score)) $qry .= "score={$score},\n";
  else               $qry .= "score=NULL,\n";

  if (!empty($authCmnt)) {
    $qry .= "comments2authors=?,";
    $prms[] = $authCmnt;
  } else {
    $qry .= "comments2authors=NULL,";
  }

  if (!empty($pcCmnt)) {
    $qry .= "comments2committee=?,";
    $prms[] = $pcCmnt;
  } else {
    $qry .= "comments2committee=NULL,";
  }

  if (!empty($chrCmnt)) {
    $qry .= "comments2chair=?,";
    $prms[] = $chrCmnt;
  } else {
    $qry .= "comments2chair=NULL,";
  }

  if (!empty($slfCmnt)) {
    $qry .= "comments2self=?,";
    $prms[] = $slfCmnt;
  } else {
    $qry .= "comments2self=NULL,";
  }

  if (isset($fileName)) { // attachment
    $qry .= "attachment=?,";
    $prms[] = $fileName;
    $ret = $fileName;
  } else {
   $qry .= "attachment=NULL,";
   $ret = NULL;
  }

  if ($oldReview) {  // existing entry
    if ($cmp2Old === -1) // only change is in comments-to-self
      $qry .= "lastModified=lastModified";
    else
      $qry .= "lastModified=NOW()";

    $qry = "UPDATE {$SQLprefix}reports SET $qry WHERE revId=$revId AND subId=$subId";

    $version = backup_existing_review($subId, $revId, $nCrit);
  } else {
    $qry .= "lastModified=NOW(), whenEntered=NOW()";
    $qry = "INSERT into {$SQLprefix}reports SET revId=$revId, subId=$subId, $qry";
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

  $db->beginTransaction();
  pdo_query($qry, $prms);      // insert/update the review itself
  pdo_query("DELETE FROM {$SQLprefix}auxGrades WHERE subId=? AND revId=?",
	    array($subId,$revId));
  if (!empty($vals))
    pdo_query("INSERT INTO {$SQLprefix}auxGrades (subId,revId,gradeId,grade) VALUES $vals");
  $db->commit();

  // Update the statistics in the submissions table
  $qry = "SELECT AVG(score), MIN(score), MAX(score), SUM(score*confidence), SUM(IF(score IS NULL, 0, confidence)) FROM {$SQLprefix}reports WHERE subId=?";
  $res = pdo_query($qry, array($subId));

  if ($row = $res->fetch(PDO::FETCH_NUM)) { // that had better be the case

    $avg = isset($row[0]) ? $row[0] : "NULL";
    $min = isset($row[1]) ? $row[1] : "NULL";
    $max = isset($row[2]) ? $row[2] : "NULL";
    $wAvg = (isset($row[3]) && isset($row[4])) ?
      ($row[3] / ((float)$row[4])) : "NULL"; 
    if (!isset($wAvg)) $wAvg = "NULL";

    $qry = "UPDATE {$SQLprefix}submissions SET avg={$avg},wAvg={$wAvg},minGrade={$min},maxGrade={$max},lastModified=NOW() WHERE subId=?";
    pdo_query($qry,array($subId));
  }

  // Add this review to change-log for this submission
  $qry = "INSERT INTO {$SQLprefix}changeLog (subId,revId,changeType,description,entered) VALUES (?,?,'Review',?,NOW())";
  pdo_query($qry, array($subId,$revId,$pcMember[1].' uploaded a review'));

  return $ret;
}

function backup_existing_review($subId, $revId, $nCrit)
{
  global $SQLprefix;
  // how many versions are backed-up for this review?
  $qry = "SELECT 1+MAX(version) FROM {$SQLprefix}reportBckp WHERE subId=? AND revId=?";
  $nextVersion = pdo_query($qry,array($subId,$revId))->fetchColumn();
  if (empty($nextVersion)) $nextVersion = 1;

  $qry = "INSERT IGNORE INTO {$SQLprefix}reportBckp SELECT subId, revId, flags, subReviewer, confidence, score, comments2authors, comments2committee, comments2chair, comments2self, attachment, lastModified, $nextVersion FROM {$SQLprefix}reports WHERE subId=? AND revId=?";
  pdo_query($qry, array($subId,$revId));

  $qry = "INSERT IGNORE INTO {$SQLprefix}gradeBckp SELECT subId, revId, gradeId, grade, $nextVersion FROM {$SQLprefix}auxGrades WHERE subId=? AND revId=?";
  pdo_query($qry, array($subId,$revId));

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