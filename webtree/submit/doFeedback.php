<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$finalFeedback = true;
require 'header.php';

$qry = "SELECT r.comments2authors, r.subId, r.revId, r.feedback, c.email,
  SHA1(CONCAT('".CONF_SALT."',r.subId,r.revId)) alias
  FROM {$SQLprefix}submissions s JOIN {$SQLprefix}reports r USING(subId)
                                 JOIN {$SQLprefix}committee c USING(revId)
  WHERE s.subId=? AND s.status!='Withdrawn'";

$hasFeedback = false;
$res = pdo_query($qry, array($submission['subId']));
$feedback = array(); // initially-empty array of feedbacks
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  if (!empty($row['feedback'])) {
    $hasFeedback = true; // already have feedback, don't process anything
    break;
  }
  $alias = 'fdbk'.$row['alias'];
  if (!empty($row['comments2authors'])) {
    $fBack = trim($_POST[$alias]);
    if (!empty($fBack)) { // store feedback in the row itself
      $row['feedback'] = $fBack;
      $feedback[] = $row; // add this row to the array
    }
  }
}

if ($hasFeedback) {
  header("Location: feedback.php?resubmit=yes"); // feedback already stored
} else if (!empty($feedback)) {
  saveFeedback($feedback);                       // process submitted feedback
  header("Location: feedback.php?success=yes");  // feedback already stored
} else {
  header("Location: feedback.php");              // empty feedback
}
exit();

function saveFeedback($feedback)
{
  global $SQLprefix;
  foreach ($feedback as $review) {
    // store feedback in the database
    $qry = "UPDATE {$SQLprefix}reports SET feedback=? WHERE subId=? AND revId=?";
    pdo_query($qry, array($review['feedback'],$review['subId'],$review['revId']));

    // send feedback by email
    $subject="Review feedback for ".CONF_SHORT." submission ".$review['subId'];
    $msg = $review['feedback'].
      "\n=================================================================\n".
      "Review text below:\n------------------\n".$review['comments2authors'];
    my_send_mail($review['email'],$subject,$msg, chair_emails(),"Send feedback.");
  }
}
?>
