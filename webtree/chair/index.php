<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD==PERIOD_SETUP) {
  print "<h1>Site Not Customized Yet</h1>\n";
  exit ('Go to the <a href="customize.php">customization page</a>'.".\n");
} 
$cName = CONF_NAME.' ('.CONF_SHORT.' '.CONF_YEAR.')';
$links = show_chr_links(1);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
div.inactive { float: right; border-style: inset; }
</style>
<title>Submission and Review Site Administration</title>
</head>
<body>
$links
<hr />
<h1>Submission and Review Site Administration</h1>
<h2>$cName</h2>

EndMark;

switch (PERIOD) {
  case PERIOD_PREREG:
  case PERIOD_SUBMIT:
    manage_submissions(PERIOD);
    break;

 case PERIOD_REVIEW:
    print "<div class=\"inactive\">\n";
    manage_submissions(PERIOD);
    print "</div>\n";
    manage_reviews(PERIOD);
    break;

 default:
    print "<div class=\"inactive\">\n";
    manage_submissions(PERIOD);
    manage_reviews(PERIOD);
    print "</div>\n";
    manage_final_version(PERIOD);
}

print "<hr />\n{$links}\n{$footer}\n";
exit("</body>\n</html>\n");


function manage_submissions($period)
{
  global $categories, $SQLprefix;

  if (is_array($categories) && count($categories)>0) {
    $catLink='<a href="listSubmissions.php?subOrder=category">category</a>, ';
  }
  else $catLink = '';
  if ($period>PERIOD_REVIEW) return;
  if ($period==PERIOD_REVIEW) {
    print <<<EndMark
<b><big>&nbsp;Submission Site is Closed</big></b><br/>
&nbsp;&nbsp;o&nbsp;&nbsp;Submission list by <a href="listSubmissions.php">number</a>, $catLink<br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="listSubmissions.php?subOrder=status">status</a>, <a href="listSubmissions.php?subOrder=format">format</a>&nbsp;
(use to revise&nbsp;or<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;withdraw submissions)&nbsp;<br/>
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="../submit/submit.php">Submit a paper</a> (after the deadline)<br/>
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="tweakSite.php">Tweak site settings</a><br/>
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="emailAuthors.php">Send email to authors</a>
EndMark;
    return; 
  }

  // Otherwise: pre-registration/submission period
  if (($period==PERIOD_PREREG) && USE_PRE_REGISTRATION) {
    $ddline =  utcDate('r (T)', REGISTER_DEADLINE);
    $reg = "Registration ";
    $closeLink = '<a href="closePrereg.php">Close pre-registration...</a>';
  } else {
    $ddline = utcDate('r (T)', SUBMIT_DEADLINE);
    $reg = "";
    $closeLink = '<a href="closeSubmissions.php">Close Submissions and Activate Review Site...</a>';
  }
  $res = pdo_query("SELECT count(subId) FROM {$SQLprefix}submissions WHERE status!='Withdrawn'");
  $nSubs = $res->fetchColumn(); 

  print <<<EndMark
<h3><span style="background-color: red;">Submission Site is Active:</span></h3>
{$reg}Deadline is <big>$ddline</big>
<ul>
<li>List submissions by <a href="listSubmissions.php">number</a>, 
    $catLink <a href="listSubmissions.php?subOrder=format">format</a>
    ($nSubs submissions so far)</li>
<li><a href="manageSubmissions.php">Manage Parameters</a>
    (deadlines, supported formats, categories, etc.)</li>
<li><a href="emailAuthors.php">Send email to authors</a></li>
<li>$closeLink (<b>deadline is not enforced automatically</b>)</li>
</ul>
<ul>
<li><a href="guidelines.php">Edit the review guidelines page</a></li>
<li><a href="managePCmembership.php">Manage PC membership</a></li>
<li><a href="voting.php">Set-up and manage PC votes</a></li>
</ul>
<ul>
<li><a href="tweakSite.php">Tweak Site Settings</a> (email settings, etc.)</li>
</ul>
		

EndMark;
}


function manage_reviews($period)
{
  global $SQLprefix;
  if ($period < PERIOD_REVIEW) return;
  if ($period == PERIOD_REVIEW) { 

    $assignHTML = '<a href="assignments.php">Assign submissions to reviewers...</a>';
    // Check if there are any submissions that needs to be purged
    $purgeLink = '';
    if (USE_PRE_REGISTRATION) {
      $qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions WHERE status!='Withdrawn' AND format IS NULL";
      if (pdo_query($qry)->fetchColumn()>0) $purgeLink = '<a href="purgeNonSubmissions.php">Purge submissions that did not upload a submission file</a><br/>';
    }

    // Check if there are email-to-authors that needs approval
    $approveEmails = '';
    if (defined('SEND_POSTS_BY_EMAIL') && SEND_POSTS_BY_EMAIL) {
      $qry = "SELECT COUNT(*) FROM {$SQLprefix}misc WHERE type=1";
      if (pdo_query($qry)->fetchColumn()>0) 
	$approveEmails = '<a href="approveEmails.php">Approve email messages to authors</a><br/>';
    }

print <<<EndMark
<h3><span style="background-color: red;">Review Site is Active</span></h3>
<dl>
<dt><strong>Initial set-up</strong></dt>
<dd><a href="archive.php">Create a tar file with all the submission files</a></dd>
<dd><a href="guidelines.php">Edit the review guidelines page</a></dd>
<dd><a href="managePCmembership.php">Manage PC membership</a></dd>
<dd>$purgeLink<br/></dd>

<dt><strong>Paper assignments</strong></dt>
<dd>Download the submission-list as a <a href="submissionSpreadsheet.php">TSV file</a> (can be used in spreadsheets such as Excel)</dd>
<dd><a href="conflicts.php">Edit conflicts</a> (block access to
  submissions due to conflict-of-interests).
  <b>Access is NOT blocked by default</b>
  (<a href="../documentation/chair.html#block">what&prime;s this?</a>)</dd>
<dd>$assignHTML</dd>
<dd><a href="groups.php">Add a group paper discussion</a></dd>
<dd><br/></dd>

<dt><strong>Reviews and decisions</strong></dt>
<dd><a href="overview.php">Overview of all submissions and reviews</a></dd>
<dd><a href="status.php">Set status of submissions</a></dd>
<dd><a href="voting.php">Set-up and manage PC votes</a></dd>
<dd><br/></dd>
		
<dt><strong>Reviewer comments</strong></dt>
<dd><a href="rebuttal.php">Open/close Rebuttal</a>
    (<b>deadline is not enforced automatically</b>)</dd>
<dd><a href="sendComments.php">Generate comments letters...</a></dd>
<dd>$approveEmails<br/></dd>

<dt><strong>Wrap-up</strong>
<dd><a href="../review/listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii" target="_blank">A full list of all the reviews and discussions (text)</a>
<dd><a href="notifications.php">Generate accept/reject letters...</a></dd>
<dd><a href="activateCamera.php">Activate Final-submission site...</a></dd>
<dd><a href="guidelines.php?what=camera">Edit camera-ready instructions</a></dd>
</dl>

EndMark;
  } else { // $period > PERIOD_REVIEW
print <<<EndMark
<b><big>&nbsp;Review Site is Closed</big></b><br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="../review/listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii">List all reviews/discussions (text)</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="voting.php">Manage PC votes</a><br />

EndMark;
 
    if ($period<PERIOD_FINAL) print <<<EndMark
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="status.php">Set status of submissions</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="notifications.php">Send accept/reject letters...</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="sendComments.php">Send comments...</a>&nbsp;&nbsp;<br />

EndMark;
  }
}

function manage_final_version($period)
{
  $cmrDdline = utcDate('r (T)', CAMERA_DEADLINE);
  if ($period < PERIOD_CAMERA) return;

  // look for a tar or tgz file with all the submissions
  $allSubFile = "tgz";
  if (!file_exists(SUBMIT_DIR."/final/all_in_one.$allSubFile")) { // .zip?
    $allSubFile = "zip";
    if (!file_exists(SUBMIT_DIR."/final/all_in_one.$allSubFile")){ // or .tar?
      $allSubFile = "tar";
      if (!file_exists(SUBMIT_DIR."/final/all_in_one.$allSubFile"))
	$allSubFile = NULL;                                   // oh, I give up
    }
  }
  if (isset($allSubFile)) {
    $allSubFile = '<li><a href="../review/download.php?final=yes&amp;all_in_one='.$allSubFile.'">Download all camera-ready archives in one file</a></li>';
  }

  if ($period==PERIOD_FINAL) {
    $hdr = '<h3>Final Submission Site is Closed</h3>';
    $closeIt = $editInstructions = '';
    $uploadTOC = '<li><a href="uploadPreface.php">Upload Preface/TOC/Author-index to the server</a></li>';
    $cryptoDB = (defined('IACR'))? '<li><a href="cryptoDB.php">Upload '.CONF_SHORT.' '.CONF_YEAR.' to CryptoDB</a></li>' : '';
  } else {
    $hdr = '<h3><span style="background-color: red;">Final Submission Site is Active</span></h3>' . "\nDeadline is <big>$cmrDdline</big> <a href=\"manageSubmissions.php\">change it</a>";
    $editInstructions = '<li><a href="guidelines.php?what=camera">Edit camera-ready instructions</a></li>';
    $closeIt = '<a href="closeSite.php">Close Final Submission Site</a> (<b>deadline is not enforced automatically</b>)';
    $uploadTOC = $cryptoDB = '';
  }

  $feedback = '';
  if (!is_null(FEEDBACK_DEADLINE)) {
    $feedback = '<li><a href="showFeedback.php">See Authors\' Feedback on the Reviews</a></li>';
  }
  print <<<EndMark
$hdr

<ul>
$editInstructions
<li><a href="listSubmissions.php">List of accepted submissions</a></li>
<li><a href="emailAuthors.php">Send email to authors</a>
</li>
<li><a href="invitedTalks.php">Add an invited talk to the program</a></li>
<li><a href="cameraArchive.php">Create one tar file with all the camera-ready files</a></li>
$allSubFile
<li><a href="makeTOC.php">Generate a LaTeX file with TOC and author index</a></li>
$feedback
$uploadTOC
$cryptoDB
</ul>
$closeIt
EndMark;
   }
?>
