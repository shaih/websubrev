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
<head>
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
  global $categories;
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
  $cnnct = db_connect();
  $qry = "SELECT count(subId) FROM submissions WHERE status!='Withdrawn'";
  $res = db_query($qry, $cnnct);
  $row = mysql_fetch_row($res); 
  $nSubs = $row[0];
  print <<<EndMark
<h3><span style="background-color: red;">Submission Site is Active:</span></h3>
{$reg}Deadline is <big>$ddline</big>
<ul>
<li><a href="tweakSite.php">Tweak Site Settings</a> (email settings, etc.)</li>
</ul>
<ul>
<li>List submissions by <a href="listSubmissions.php">number</a>, 
    $catLink <a href="listSubmissions.php?subOrder=format">format</a>
    ($nSubs submissions so far)</li>
<li><a href="manageSubmissions.php">Manage Parameters</a>
    (deadlines, supported formats, categories, etc.)</li>
<li>$closeLink (<b>deadline is not enforced automatically</b>)</li>
</ul>
<ul>
<li><a href="guidelines.php">Edit the review guidelines page</a></li>
<li><a href="managePCmembership.php">Manage PC membership</a></li>
<li><a href="voting.php">Set-up and manage PC votes</a></li>
</ul>

EndMark;
}


function manage_reviews($period)
{
  if ($period < PERIOD_REVIEW) return;
  if ($period == PERIOD_REVIEW) { 

    if (defined('REVPREFS') && REVPREFS) { $assignHTML = <<<EndMark
Assign submissions to reviewers:
<ul>
<li><a href="autoAssign.php">Automatic assignments</a> (Compute assignments automatically from the reviewers preferences.)</li>
<li><a href="assignments.php">Manual assignments</a> (Use matrix and list interfaces for manual assignment or adjusting the automatic assignment.)</li>
</ul>
The assignments can always be revised and recomputed.
EndMark;
    } else {
      $assignHTML = '<a href="assignments.php">Assign submissions to reviewers</a>';
    }
print <<<EndMark
<h3><span style="background-color: red;">Review Site is Active</span></h3>
<dl>
<dt><strong>Initial set-up</strong>
<dd><a href="archive.php">Create a tar file with all the submission files</a>
<dd><a href="guidelines.php">Edit the review guidelines page</a>
<dd><a href="managePCmembership.php">Manage PC membership</a>
<br /><br />

<dt><strong>Paper assignments</strong>
<dd>Download the submission-list as an <a href="submissionSpreadsheet.php">Excel spreadsheet</a>
<dd><a href="conflicts.php">Edit conflicts</a> (block access to
  submissions due to conflict-of-interests).
  <b>Access is NOT blocked by default</b>
  (<a href="../documentation/chair.html#block">what&prime;s this?</a>)
<dd>$assignHTML
<br/><br/>

<dt><strong>Reviews and decisions</strong>
<dd><a href="overview.php">Overview of all submissions and reviews</a>
<dd><a href="status.php">Set status of submissions</a>
<dd><a href="voting.php">Set-up and manage PC votes</a>
<br /><br />

<dt><strong>Wrap-up</strong>
<dd><a href="../review/listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii" target="_blank">A full list of all the reviews and discussions (text)</a>
<dd><a href="notifications.php">Generate accept/reject letters...</a>
<dd><a href="sendComments.php">Generate comments letters...</a>
<dd><a href="activateCamera.php">Activate Final-submission site...</a>
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
    $mkTOC = '<li><a href="makeTOC.php">Generate a LeTeX file with TOC and author index</a></li>'."\n"
      . '<li><a href="uploadPreface.php">Upload Preface/TOC/Author-index to the server</a><br/><br/></li>';
    $closeIt = '';
  }
  else {
    $hdr = '<h3><span style="background-color: red;">Final Submission Site is Active</span></h3>' . "\nDeadline is <big>$cmrDdline</big>";
    $mkTOC = '';
    $closeIt = '<a href="closeSite.php">Close Final Submission Site</a> (<b>deadline is not enforced automatically</b>)';
  }

  print <<<EndMark
$hdr

<ul>
<li><a href="listSubmissions.php">List of accepted submissions</a></li>
<li><a href="emailAuthors.php">Send email to authors of accepted papers</a>
</li>
<li><a href="invitedTalks.php">Add an invited talk to the program</a></li>
$mkTOC
<li><a href="cameraArchive.php">Create one tar file with all the camera-ready files</a></li>
$allSubFile
</ul>
$closeIt
EndMark;
   }
?>
