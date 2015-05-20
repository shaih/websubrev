<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
require 'storeReview.php';
$revId =  (int) $pcMember[0];
$revEmail = $pcMember[2];
$disFlag= (int) $pcMember[3];
$pcmFlags=(int) $pcMember[5];

$subId = (int) $_POST['subId'];

if (defined('CAMERA_PERIOD')) {
   exit("<h1>Site closed: cannot post new reviews</h1>");
}

// The chair can edit anyone's review
$notMine = (isset($_POST['revId'])
	    && intval($_POST['revId']) != $revId
	    && is_chair($revId));
if ($notMine) $revId = intval($_POST['revId']);
else {
  $saveDraft = isset($_POST['draft']);
  $add2watch = (!$disFlag || isset($_POST['add2watch']));
}

// Returns either an error code, the attachment name (if any), or NULL
$ret = storeReview($subId, $revId, $_POST['subRev'], $_POST['conf'],
		   $_POST['score'], $_POST, $_POST['comments2authors'],
		   $_POST['comments2PC'], $_POST['comments2chair'],
		   $_POST['comments2self'], $add2watch, $saveDraft);

if ($ret===-1) exit("<h1>No Submission specified</h1>");
if ($ret===-3) exit("<h1>Submission does not exist or reviewer has a conflict</h1>");

$attachment = ($ret < 0) ? NULL : $ret;
if (isset($_POST['emilReview'])) {
  sendReviewByEmail($revEmail, $subId, $_POST['subRev'], 
		    $_POST['conf'], $_POST['score'], $_POST,
		    $_POST['comments2authors'], $_POST['comments2PC'],
		    $_POST['comments2chair'], $_POST['comments2self'], 
		    $saveDraft, $attachment);
  $flags = $pcmFlags | FLAGS_EML_MY_REPORT;
}
else $flags = $pcmFlags & (~FLAGS_EML_MY_REPORT);

// update the "send reviews by email" flag
if (($flags != $pcmFlags) && !$notMine) {
  pdo_query("UPDATE {$SQLprefix}committee SET flags=? WHERE revId=?", array($flags,$revId));
}

header("Location: receiptReport.php?subId={$subId}&revId={$revId}");
exit();

function sendReviewByEmail($revEmail, $subId,
			   $subRev, $conf, $score, $auxGrades,
			   $athrCmnts, $PCCmnts, $chrCmnts, $slfCmnts, 
			   $saveDraft, $attachment)
{
  global $criteria;
  $confName = CONF_SHORT.' '.CONF_YEAR;
  $errMsg = "Send back review for submission {$subId} to {$revEmail}";

  $subject = "Review of $confName submission $subId";
  $msg = "";
  if (isset($subRev) && !empty($subRev)) {
    $msg.= "Subreviewer: $subRev\n";
  }
  $msg.= "Score: ".(($score>0 && $score<=MAX_GRADE)? $score : '*')."\n";
  $msg.= "Confidence: ".(($conf>0 && $conf<=MAX_GRADE)? $conf : '*')."\n";

  $nCrit = count($criteria);
  for ($i=0; $i<$nCrit; $i++) {
    $grade = isset($auxGrades["grade_{$i}"])?((int)$auxGrades["grade_{$i}"]):0;
    $mx = $criteria[$i][1];
    if ($grade<=0 || $grade>$mx) $grade='*';
    $msg.= $criteria[$i][0].": $grade\n";
  }

  if (isset($athrCmnts) && !empty($athrCmnts)) {
    $msg .= "\nComments to authors:\n\n";
    $msg .= wordwrap($athrCmnts, 78);
  }
  if (isset($attachment) && !empty($attachment)) {
    $msg .= "\n\nAttachment $attachment stored with the review";
  }
  if (isset($PCCmnts) && !empty($PCCmnts)) {
    $msg .= "\n\nComments to committee:\n\n";
    $msg .= wordwrap($PCCmnts, 78);
  }
  if (isset($chrCmnts) && !empty($chrCmnts)) {
    $msg .= "\n\nComments to chair:\n\n";
    $msg .= wordwrap($chrCmnts, 78);
  }
  if (isset($slfCmnts) && !empty($slfCmnts)) {
    $msg .= "\n\nNotes to myself:\n\n";
    $msg .= wordwrap($slfCmnts, 78);
  }

  my_send_mail($revEmail, $subject, $msg, NULL, $errMsg); 
}
?>
