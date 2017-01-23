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

$confName = CONF_SHORT . ' ' . CONF_YEAR;

$max_rebuttal = defined("MAX_REBUTTAL") ? MAX_REBUTTAL : 3000;
$rebDeadline = defined('REBUTTAL_DEADLINE') ? 
  ('Rebuttal is open until '. date('r', REBUTTAL_DEADLINE)) : "";
$rebuttal = $submission["rebuttal"];

$qry = "SELECT flags FROM {$SQLprefix}submissions WHERE subId=?";
$res = pdo_query($qry, array($submission['subId']));
$flags = $res->fetchColumn();

if ($flags & FLAG_FINAL_REBUTTAL) {  // If rebuttal already "finalized" then exit
  exit("<h1>Rebuttal was finalized and can no longer be modified</h1>");
}
if (isset($_POST['finalize'])) {     // mark rebttal as final
  $flags |= FLAG_FINAL_REBUTTAL;
}

if(isset($_POST['rebuttal'])) {
  $rebuttal = trim($_POST['rebuttal']);
  if (($len=strlen($_POST['rebuttal'])) > $max_rebuttal+100) {
    exit("Rebuttal is too long ($len characters), only $max_rebuttal characters are allowed.");
  }
  else {
    pdo_query("UPDATE {$SQLprefix}submissions SET rebuttal=?, flags=? WHERE subId=?",
	      array($rebuttal, $flags, $submission["subId"]));
    header("Location: rebuttal.php");
    exit();
  }
}

$rebuttalBox = nl2br(htmlspecialchars($rebuttal));
if (!empty($rebuttal)) {
  $rebuttalBox =<<<EndMark
<h4>Current rebuttal text:</h4>
<div style="width: 90%; margin-left: auto; margin-right: auto; background: lightgrey; margin-bottom: 1.5em;">$rebuttalBox</div>
EndMark;
}

if(isset($_GET['success'])) {
  $warnings[] = "<h2>Rebuttal Saved</h2>";
}

$qry = "SELECT comments2authors, status, confidence, score, attachment
  FROM {$SQLprefix}submissions s LEFT JOIN {$SQLprefix}reports r USING(subId)
  WHERE s.subId = ? AND s.status!='Withdrawn'
  ORDER by s.subId, r.lastModified";

$res = pdo_query($qry, array($submission['subId']));
$comments = array();
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $row['comments2authors'] 
    = str_replace("\n", "<br/>\n", htmlspecialchars($row['comments2authors']));
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
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
  <title>Author Rebuttal $confName </title>
</head>

<body>
<h1>Rebuttal for Submission {$submission["subId"]}</h1>
<center>$rebDeadline</center>

$rebuttalBox

Dear Author:

<p>Below are the preliminary reports on your submission. Please read them carefully and provide your responses to questions asked and any comments you may have on the reviews, using the interface provided at the bottom of this page. 
  You are not required to respond, if you feel the reviews are accurate and the reviewers have not asked any questions, then you should not respond. If you do respond, conciseness and clarity will be highly appreciated and most effective.
<b>Your response must not be more than $max_rebuttal characters long.</b></p>

<p>During the rebuttal period you can enter and continually edit your responses, but the reviewers will not be able to see your rebuttal until you check the box for "Finalize my rebuttal". <b>Once you submitted a rebuttal with that box checked, you will lose access to this page and will not be able to modify your rebuttal anymore!</b>
Please note that the review site is active, and while we generally try to keep the reviews unchanged during this period, sometimes they may still change.</p>

<p>Please keep in mind that the main purpose of this process is to help the program committee improve the quality and accuracy of the reviewing and decision process, by having you point out potential omissions and mistakes (both conceptual and technical) in the reviews. A secondary purpose is to give you early feedback on their submission, thus allowing you more time to improve your work.</p>

<p>Consequently, the response will best focus on factual errors, misconceptions, or omissions in the reviews, as well as answering any questions posed by the reviewers. New discoveries and results by the authors will be considered relevant only insofar as they help put the submission or reviews in context. No additional credit will be given to new results that are not reported in the submission.</p>

<p>No decisions have been made yet. These are preliminary reviews submitted by the PC members, without any coordination between them. Thus, there may be inconsistencies. The reviews will be updated to take into account the author's responses and discussions of the program committee members. We may also find it necessary to solicit additional reviews.</p> <!-- ' -->

<p>Please understand that the PC members are doing their best to understand your work in a very limited amount of time. This means that mistakes are bound to happen, and very rarely is any malice involved. So please try to be polite and constructive. Also, please keep in mind that your response will be seen by all PC members who do not have a conflict of interests with your submission.</p>

<p>Sincerely,<br>
$confName Program Chair(s)</p>

<h2>Comments</h2>
$comments
<hr/>
<form action="rebuttal.php" enctype="multipart/form-data" method="post" accept-charset="utf-8">
  $warnings
  <label>Rebuttal To Comments: ($max_rebuttal character limit)</label><br/>
  <textarea name="rebuttal" rows="10" cols="80">$rebuttal</textarea> 
  <br />
  <input type="submit" value="Save">
  <input type="checkbox" name="finalize"> Finalize my rebuttal. (You will not be able to modify it anymore!)
</form>
<hr />
</body>
</html>
EndMark;
