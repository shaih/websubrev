<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'printSubList.php';
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];

$links = show_rev_links(2);
$message = show_message();
$phase = $disFlag ? 'Discussion Phase' : 'Individual Review Phase';
if (defined('CAMERA_PERIOD')) $phase = 'Read Only';
$legend = '';

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<link rel="stylesheet" type="text/css" href="review.css" />
<style type="text/css">
body { width: 800px; }
h1, h2 { text-align: center; }
div.frame { border-style: inset; }
tr { vertical-align: top; }
</style>
<title>Review homepage for $revName</title>
</head>
<body>
$message
$links
<hr />

<h1>{$revName}'s Review Page</h1> <!-- ' -->
<h2>$phase</h2>
Hello $revName. 
EndMark;

// look for a tar or tgz file with all the submissions
$allSubFile = SUBMIT_DIR."/all_in_one.tgz";
if (!file_exists($allSubFile)) {   // maybe .zip rather than .tzg?
  $allSubFile = SUBMIT_DIR."/all_in_one.zip";
  if (!file_exists($allSubFile)) { // or perhaps jusr .tar?
    $allSubFile = SUBMIT_DIR."/all_in_one.tar";
    if (!file_exists($allSubFile)) $allSubFile = NULL; // oh, I give up
  }
}

if (isset($allSubFile)) {
  $allSubFile = '&nbsp;o&nbsp;&nbsp;<a href="../'.$allSubFile.'">Download submissions in one file</a><br />';
}

if (REVPREFS && !$disFlag) {
  $indicatePrefs = '&nbsp;o&nbsp;&nbsp;<a href="prefs.php">Indicate reviewing preferences</a><br />';
}
else $indicatePrefs = NULL;

$listSubmissions = "<td style=\"width: 265px;\">\n"
  . listSubmissionsBox($disFlag) . "</td>\n";

if (!$disFlag) { // Reviewer still in the individual review phase
  individual_review($revId);
  $showReviews = $allReviews = '';
} else {         // Reviewer in the discussion phase
  $watchedSubs = discussion_phase($revId);
  if ($watchedSubs) {
    $legend = show_legend(); // defined in confUtils.php
  }

  $showReviews = "<td style=\"width: 270px;\">\n"
    . showReviewsBox() . "</td>\n";
  $allReviews = '<br/>
&nbsp;o&nbsp;&nbsp;List all reviews and discussions<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;in&nbsp;<a
href="listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on">html</a> or <a href="listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii">ascii</a>
<br/>';

}

print <<<EndMark
<br/>
<table cellspacing=5 width="100%"><tbody><tr>
<!-- A box that lets the reviewer list submissions in different orders -->
$listSubmissions

<td><strong>Some other links:</strong><br />
$allSubFile
$indicatePrefs
&nbsp;o&nbsp;&nbsp;<a href="password.php">Change password</a><br />
$allReviews
</td>

<!-- A box that lets the reviewer list reviews in different orders -->
$showReviews
</tr></tbody></table>
$legend
<hr/>
$links
$footer
</body>
</html>

EndMark;

function show_message()
{
  if (isset($_GET['newPwd']) && $_GET['newPwd']=='ok') {
    return '<div style="text-align: center; color: red;">Password successfully changed</div>';
  }

  return '';
}

function individual_review($revId)
{
  $subs = array();
  $reviewed = array();
  $cnnct = db_connect();

  $qry ="SELECT s.subId subId, title, s.format format, status, 
      UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
      a.watch watch, r.revId revId
    FROM submissions s
      INNER JOIN assignments a ON a.revId={$revId} AND a.subId=s.subId
      LEFT JOIN reports r ON r.subId=s.subId AND r.revId={$revId}
    WHERE s.status!='Withdrawn' AND a.assign=1";
  $res = db_query($qry, $cnnct);

  $nReviewed = $total = 0;
  while ($row = mysql_fetch_assoc($res)) {
    if (isset($row["revId"])) { // already reviewed
      $subId = (int) $row["subId"];
      $reviewed[$subId] = true;
      $nReviewed++;
    }
    $subs[] = $row;
    $total++;
  }
  print "So far you reviewed " . $nReviewed
    ." of the " . $total
    . " submissions that were assigned to you.\n";

  // Show reviewer his/her total, just to make him/her happy
  $qry = "SELECT COUNT(1) FROM reports WHERE revId={$revId}";
  $res = db_query($qry, $cnnct);
  $row = mysql_fetch_row($res);
  $extra = ((int) $row[0]) - $nReviewed;
  if ($extra > 0) print "You also reviewed $extra other submissions. ";

  if ($total>0) {
    print "<br/><br/>\n";
    print_sub_list($subs, "Your assigned submissions", $reviewed);
  }
  else print "<br/>\n";
}

function discussion_phase($revId)
{
  global $discussIcon1, $discussIcon2;

  $cnnct = db_connect();
  print votingText($cnnct);

  // Get a list of submissions for which this reviewer already saw all
  // the discussions/reviews. Everything else is considered "new"
  $qry = "SELECT s.subId FROM submissions s JOIN lastPost lp ON lp.revId=$revId AND s.subId=lp.subId WHERE s.lastModified<=lp.lastVisited";
  $res = db_query($qry, $cnnct);
  $seenSubs = array();
  while ($row = mysql_fetch_row($res)) { $seenSubs[$row[0]] = true; }

  // a list of submissions that this reviewer reviewed
  $reviewed = array();
  $qry = "SELECT subId FROM reports WHERE revId={$revId}";
  $res = db_query($qry, $cnnct);
  $reviewed = array();
  while ($row = mysql_fetch_row($res)) { $reviewed[$row[0]] = true; }

  $qry ="SELECT s.subId subId, title, s.format format, status,
      UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
      a.watch watch, s.wAvg avg
    FROM submissions s, assignments a
    WHERE status!='Withdrawn' AND a.revId={$revId}
      AND a.subId=s.subId AND a.watch=1
    ORDER BY s.subId";
  $res = db_query($qry, $cnnct);

  $needsDiscussion = 0;
  $subs = array();
  while ($row = mysql_fetch_assoc($res)) {
    $subId = $row['subId'];
    $row['hasNew'] = !isset($seenSubs[$subId]);
    $subs[] = $row;
    if ($row['status']=='Needs Discussion') $needsDiscussion++;
  }

  if (count($subs)>0) {
    if ($needsDiscussion > 0) { // show list of submissions needing discussion

      if ($needsDiscussion==1) {  // singular vs plural in English
	$require = "requires";
	$needsDiscussion = 'one';
      }
      else { $require = "require"; }

      print "The chair indicated that " . $needsDiscussion 
	. " of the submissions on your watch list " . $require 
	. " additional discussion.\n";
    }
    print "<br/><br/>\n";
    print_sub_list($subs, "Submissions on your watch list", $reviewed, true);
    return true;
  }
  else print '<br/>';
  return false;
}

function votingText($cnnct)
{
  $res = db_query("SELECT voteId, voteTitle, deadline FROM votePrms WHERE voteActive=1", $cnnct);
  if (mysql_num_rows($res)<= 0) return '';

  $html = "You can participate in the current active ballots:\n";
  $html .= "<blockquote><table border=1><tbody>\n"
    . "<tr align=left><th>Title</th><th>Deadline</th>\n";
  
  while ($row=mysql_fetch_row($res)) {
    $voteId = intval($row[0]);
    $voteTitle = trim($row[1]);
    if (empty($voteTitle)) $voteTitle = 'Ballot #'.$voteId;
    else                   $voteTitle = htmlspecialchars($voteTitle);
    $html .= '<tr><td><a href="vote.php?voteId='.$voteId.'" target="_blank">'
      .$voteTitle.'</a> </td><td>'.htmlspecialchars($row[2])."</td></tr>\n";
  }
  return ($html . "</tbody></table></blockquote>\n\n");
}
?>
