<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$finalFeedback = true;
require 'header.php';

$confName = CONF_SHORT . ' ' . CONF_YEAR;
if (isset($_GET['success']))
  $savedMessage = "<h3>Feedback Saved</h3>";
else if (isset($_GET['resubmit']))
  $savedMessage = "<h3>Feedback already provided, cannot send again</h3>";
else
  $savedMessage = "";

$ddline = 'Deadline is '.utcDate('r (T)',FEEDBACK_DEADLINE); // when is the deadline
$timeleft = show_deadline(FEEDBACK_DEADLINE);  // how much time is left

print <<<EndMark
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
  <title>Author Feedback $confName</title>
</head>

<body>
$savedMessage
<h1>Author Feedback for Submission {$submission["subId"]}</h1>
<h3 class=timeleft>$ddline<br/>
$timeleft</h3>

Dear Author:

<p>Below are the reviews of your submission, you can use this form to provide feedback on these reviews, for example if you feel that some reviews are particularly good or particularly poor. Your feedback on each review will be visible only to the program-committee member who wrote this review and to the program chair(s). No feedback is required, providing it is purely optional. You can only use this form once, it will de-activate once you submit it to the server.</p>

<p>Sincerely,<br>
$confName Program Chair(s)</p>

<hr/>
<form action="doFeedback.php" enctype="multipart/form-data" method="post" accept-charset="utf-8">
EndMark;

$qry = "SELECT comments2authors,status,confidence,score,attachment,revId,feedback,SHA1(CONCAT('".CONF_SALT."',r.subId,r.revId)) alias
  FROM {$SQLprefix}submissions s LEFT JOIN {$SQLprefix}reports r USING(subId)
  WHERE s.subId = ? AND s.status!='Withdrawn'
  ORDER by alias";

$hasFeedback = false;
$res = pdo_query($qry, array($submission['subId']));
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $review = str_replace("\n", "<br/>\n", htmlspecialchars($row['comments2authors']));
  if (!empty($row['attachment'])) {
    $review .= "<br/>\n<a href='download.php?attachement={$row['attachment']}&subId={$submission['subId']}&subPwd={$submission['subPwd']}'>Download Attachment</a>";
  }
  $alias = $row['alias'];
  if (!empty($review)) {
    $fBack = '';
    if (!empty($row['feedback'])) {
      $fBack = htmlspecialchars($row['feedback']);
      $hasFeedback = true;
    }
    print "<h3>Review $alias</h3><p>{$review}</p>\n";
    print "<b>Feedback:</b><br/>\n";
    print "<textarea name='fdbk{$alias}' cols='80' rows='10'>$fBack</textarea>\n<hr/>\n";
  }
}
if (!$hasFeedback) print "<input type='submit' value='Submit Feedback'>\n";
print "</form><hr/>\n</body></html>";
?>
