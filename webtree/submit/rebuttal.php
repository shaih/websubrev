<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$allow_rebuttal = true;
$require_author = true;
require 'header.php';

$cnnct = db_connect();

$confName = CONF_SHORT . ' ' . CONF_YEAR;

$max_rebuttal = defined("MAX_REBUTTAL") ? MAX_REBUTTAL : 3000;
$rebDeadline = defined('REBUTTAL_DEADLINE') ? 
  ('The rebuttal website will remain open until '
   . date('Y-n-j G:i e', REBUTTAL_DEADLINE) . '<br/><br/>') : "";
$rebuttal = $submission["rebuttal"];
$warnings = array();

if(isset($_POST['rebuttal'])) {
  if(strlen($_POST['rebuttal']) > $max_rebuttal+100) {
    $warnings[] = "Rebuttal is too long.";
    $rebuttal = $_POST['rebuttal'];
  }
  else {
    $qry = "UPDATE submissions SET rebuttal='".
      my_addslashes($_POST['rebuttal'])."' 
     WHERE subId='".$submission["subId"]."'";
    db_query($qry, $cnnct);
    header("Location: rebuttal.php?success=true");
    exit;
  }
}

if(isset($_GET['success'])) {
  $warnings[] = "<h2>Rebuttal Saved</h2>";
}

$qry = "SELECT comments2authors, status,
    confidence, score, attachment
  FROM submissions s LEFT JOIN reports r USING(subId)
  WHERE s.subId = '".$submission['subId']."' AND s.status!='Withdrawn'
  ORDER by s.subId, r.lastModified";

$res = db_query($qry, $cnnct);

$comments = array();
while($row = mysql_fetch_assoc($res)) {
  $row['comments2authors'] = str_replace("\n", "<br />", $row['comments2authors']);
  $comments[] = $row;
}

function render_comment($comment) {
  global $submission;
  
  $download = "";
  if($comment['attachment']) {
    $download = "<br /><a href='download.php?attachement={$comment['attachment']}&subId={$submission['subId']}&subPwd={$submission['subPwd']}'>Download Attachment</a>";
  }
  return <<<EndMark
  <li style="margin-top:20px;">
    {$comment["comments2authors"]}
    $download
  </li>
EndMark;
}

$comments = "<ol>".implode("\n", array_map("render_comment", $comments))."</ol";

$warnings = !empty($warnings) ? "<div style='font-color:red;'>".implode("<br />", $warnings)."</div>" : "";

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>

<script type="text/javascript" src="../common/validate.js"></script>
  <title>Author Rebuttal $confName </title>
</head>

<body>
  <h1>Rebuttal for Submission {$submission["subId"]}</h1>
  <hr/>
  Dear Author:<br><br>

Below are the preliminary reports on your submission. Please read them carefully and provide, using the interface provided at the bottom of this page, responses to questions asked and any comments you may have on the reviews. Your response must not be more than 3000 characters long.<br><br>

$rebDeadline
During that time, you will be able to enter and continually edit your responses. Please note that the review database is active: While we will try to keep it unchanged during the rebuttal period, some small local changes might occur. This is normal.<br><br>

Please keep in mind that the main purpose of this process is to help the program committee improve the quality and accuracy of the reviewing and decision process, by having you point out potential omissions and mistakes (both conceptual and technical) in the reviews. A secondary purpose is to give you early feedback on their submission, thus allowing you more time to improve their work.<br><br>

Consequently, the response will best focus on factual errors, misconceptions, or omissions in the reviews, as well as answering any questions posed by the reviewers. New discoveries and results by the authors will be considered relevant only insofar as they help put the submission or reviews in context. No additional credit will be given to new results that are not reported in the submission.<br><br>

No decisions have been made yet. These are preliminary reviews submitted by the PC members, without any coordination between them. Thus, there may be inconsistencies. The reviews will be updated to take into account the author's responses and discussions of the program committee members. We may also find it necessary to solicit additional reviews.<br><br>

Two final notes: First, you are not required to respond. If you feel the reviews are accurate and the reviewers have not asked any questions, then you should not respond. If you do, conciseness and clarity will be highly appreciated and most effective.<br><br>

Also, please understand that the PC members are doing their best to understand your work in a very limited amount of time. This means that mistakes are bound to happen, and very rarely is any malice involved. So please try to be polite and constructive. Also, please keep in mind that your response will be seen by all PC members who do not have a conflict of interests with your submission.<br><br>

Sincerely,<br>
$confName Program Chair(s)<br>

  <h2>Comments</h2>
  $comments
  <hr />
<form action="rebuttal.php" enctype="multipart/form-data" method="post">
  $warnings
  <label>Rebuttal To Comments: ($max_rebuttal character limit)</label><br/>
  <textarea name="rebuttal" rows="10" cols="80">$rebuttal</textarea> 
  <br />
  <input type="submit" value="Save">
</form>
<hr />
</body>
</html>
EndMark;
