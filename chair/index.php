<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

$needsAuthentication = true; 
require 'header.php';
$cName = CONF_NAME.' ('.CONF_SHORT.' '.CONF_YEAR.')';
$links = show_chr_links(1);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
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

if (!defined('REVIEW_PERIOD'))      $period = 1; // submissions
else if (!defined('CAMERA_PERIOD')) $period = 2; // review
else                                $period = 3; // camera-ready

switch ($period) {
  case 1:
    manage_submissions($period);
    break;

 case 2:
    print "<div class=\"inactive\">\n";
    manage_submissions($period);
    print "</div>\n";
    manage_reviews($period);
    break;

 default:
    print "<div class=\"inactive\">\n";
    manage_submissions($period);
    manage_reviews($period);
    print "</div>\n";
    manage_final_version($period);
}

print "<hr />\n{$links}\n{$footer}\n";
exit("</body>\n</html>\n");


function manage_submissions($period)
{
  if ($period>2) return;
  if ($period==2) {
    print <<<EndMark
<b><big>&nbsp;Submission Site is Closed</big></b><br/>
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="listSubmissions.php">Submission list by number</a>,<br/>
&nbsp;&nbsp;o&nbsp;&nbsp;by&nbsp;<a href="listSubmissions.php?subOrder=category">category</a>, <a href="listSubmissions.php?subOrder=status">status</a>, <a href="listSubmissions.php?subOrder=format">format</a>&nbsp;<br/>
&nbsp;(use to withdraw/revise submissions)&nbsp;

EndMark;
    return; 
  }

  $subDdline = utcDate('r (T)', SUBMIT_DEADLINE);
  $cnnct = db_connect();
  $qry = "SELECT count(subId) FROM submissions WHERE status!='Withdrawn'";
  $res = db_query($qry, $cnnct);
  $row = mysql_fetch_row($res); 
  $nSubs = $row[0];
  print <<<EndMark
<h3><span style="background-color: red;">Submission Site is Active:</span></h3>
Deadline is <big>$subDdline</big>
<ul>
<li>List submissions by <a href="listSubmissions.php">number</a>, 
    <a href="listSubmissions.php?subOrder=category">category</a>,
    or <a href="listSubmissions.php?subOrder=format">format</a>
    ($nSubs submissions so far)</li>
<li><a href="manage-submission-site.php">Manage Parameters</a>
    (deadlines, supported formats, categories, etc.)</li>
<li><a href="close-submissions.php">Close Submissions and Activate Review
     Site...</a> (<b>deadline is not enforced automatically</b>)</li>
</ul>
<ul>
<li><a href="guidelines.php">Edit the review guidelines page</a></li>
<li><a href="manage-review-site.php">Manage PC membership</a></li>
<li><a href="voting.php">Set-up and manage PC votes</a></li>
</ul>

EndMark;
}


function manage_reviews($period)
{
  if ($period < 2) return;
  if ($period == 2) { 

    if (defined('REVPREFS') && REVPREFS) { $assignHTML = <<<EndMark
Assign submissions to reviewers:
<ul>
<li><a href="auto-assign.php">Automatic assignments</a> (Compute assignments automatically from the reviewers preferences.)</li>
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
<dd><a href="manage-review-site.php">Manage PC membership</a>
<br /><br />

<dt><strong>Paper assignments</strong>
<dd>Download the submission-list as an <a href="submissionSpreadsheet.php">Excel spreadsheet</a>
<dd><a href="conflicts.php">Edit conflicts</a> (Use to block access to
  submissions due to conflict-of-interests, etc.)
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
<dd><a href="manage-finalSubmit-site.php">Activate Final-submission site...</a>
</dl>

EndMark;
  } else { // $period > 2
print <<<EndMark
<b><big>&nbsp;Review Site is Closed</big></b><br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="../review/listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii">List all reviews/discussions (text)</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="voting.php">Manage PC votes</a><br />

EndMark;
 
    if (!defined('SHUTDOWN')) print <<<EndMark
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="status.php">Set status of submissions</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="notifications.php">Send accept/reject letters...</a>&nbsp;&nbsp;<br />
&nbsp;&nbsp;o&nbsp;&nbsp;<a href="sendComments.php">Send comments...</a>&nbsp;&nbsp;<br />

EndMark;
  }
}

function manage_final_version($period)
{
  $cmrDdline = utcDate('r (T)', CAMERA_DEADLINE);
  if ($period < 3) return;

  // look for a tar or tgz file with all the submissions
  $allSubFile = SUBMIT_DIR."/final/all_in_one.tgz";
  if (!file_exists($allSubFile)) {   // maybe .zip rather than .tzg?
    $allSubFile = SUBMIT_DIR."/final/all_in_one.zip";
    if (!file_exists($allSubFile)) { // or perhaps jusr .tar?
      $allSubFile = SUBMIT_DIR."/final/all_in_one.tar";
      if (!file_exists($allSubFile)) $allSubFile = NULL; // oh, I give up
    }
  }

  if (isset($allSubFile)) {
    $allSubFile = '<li><a href="../'.$allSubFile.'">Download all camera-ready archives in one file</a></li>';
  }

  if (defined('SHUTDOWN')) {
    $hdr = '<h3>Final Submission Site is Closed</h3>';
    $mkTOC = '<li><a href="makeTOC.php">Generate a LeTeX file with TOC and author index</a></li>';
    $closeIt = '';
  }
  else {
    $hdr = '<h3><span style="background-color: red;">Final Submission Site is Active</span></h3>' . "\nDeadline is <big>$cmrDdline</big>";
    $mkTOC = '';
    $closeIt = '<a href="close-site.php">Close Final Submission Site</a> (<b>deadline is not enforced automatically</b>)';
  }

  print <<<EndMark
$hdr

<ul>
<li><a href="listSubmissions.php">List of accepted submissions</a></li>
<li><a href="cameraArchive.php">Create one tar file with all the camera-ready files</a>
</li>
$allSubFile
$mkTOC
</ul>
$closeIt
EndMark;
   }
?>
