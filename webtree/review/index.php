<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
$preReview=true;      // page is available also before the review peiod
require 'printSubList.php';
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];  // Is it discussion phase for this reviewr?
$pcmFlags= (int) $pcMember[5];
$confName = CONF_SHORT . ' ' . CONF_YEAR;

$links = show_rev_links(2);
$message = show_message();
$phase = $disFlag ? 'Discussion Phase' : 'Individual Review Phase';
if ($disFlag==2) $phase .= ' (limited)';
if (is_chair($revId)) $phase .= ' (Program Chair)';
if (defined('CAMERA_PERIOD')) $phase = 'Read Only';
$legend = '';

// A box listing the current active votes
$qry = "SELECT voteId, voteTitle, deadline FROM {$SQLprefix}votePrms WHERE voteActive=1";
// Before the discussion phase, cannot vote on submissions
if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";
$elections =  pdo_query($qry)->fetchAll(PDO::FETCH_NUM);
if (count($elections) <= 0) $ballotsText='';
else {
  $ballotsText = "You can participate in the current active ballots:\n"
    . "<blockquote><table border=1><tbody>\n"
    . "<tr align=left><th>Title</th><th>Deadline</th>\n";

  foreach ($elections as $row) {
    $voteId = intval($row[0]);
    $voteTitle = trim($row[1]);
    if (empty($voteTitle)) $voteTitle = 'Ballot #'.$voteId;
    else                   $voteTitle = htmlspecialchars($voteTitle);
    $ballotsText .= '<tr><td><a href="vote.php?voteId='.$voteId.'" target="_blank">'
      .$voteTitle.'</a> </td><td>'.htmlspecialchars($row[2])."</td></tr>\n";
  }
  $ballotsText = $ballotsText . "</tbody></table></blockquote>\n\n";
}

// A link to see the results of completed ballots
$qry = "SELECT COUNT(*) FROM {$SQLprefix}votePrms WHERE voteActive=0";
$completed = pdo_query($qry)->fetchColumn();
if ($completed>0) $seeVoteRes = '&nbsp;o&nbsp;&nbsp;<a target=_blank href="voteResults.php">See results of completed votes</a><br/>';
else $seeVoteRes = '';

print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
body { width: 900px; }
h1, h2 { text-align: center; }
div.frame { border-style: inset; }
tr { vertical-align: top; }
</style>
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<title>$confName Review homepage for $revName</title>
<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="toggleImage.js"></script>
</head>
<body>
$message
$links
<hr />

<h1>{$revName}'s Review Page, $confName</h1> <!-- ' -->
<h2>$phase</h2>
Hello $revName. $ballotsText
EndMark;

// Before the review period: only chair can sees things.
if (!is_chair($pcMember[0]) &&
    (PERIOD<PERIOD_SUBMIT ||(PERIOD==PERIOD_SUBMIT &&!USE_PRE_REGISTRATION))){
  print <<<EndMark
The review period has not started yet.
<hr/>
$links
</body>
</html>
EndMark;
 exit();
}

// look for a tar or tgz file with all the submissions
$allSubFile = "tgz";
if (!file_exists(SUBMIT_DIR."/all_in_one.$allSubFile")) { // maybe .zip?
  $allSubFile = "zip";
  if (!file_exists(SUBMIT_DIR."/all_in_one.$allSubFile")){ // or maybe .tar?
    $allSubFile = "tar";
    if (!file_exists(SUBMIT_DIR."/all_in_one.$allSubFile")) // oh, I give up
      $allSubFile = NULL;
  }
}
if (isset($allSubFile)) {
  $allSubFile = '&nbsp;o&nbsp;&nbsp;<a href="download.php?all_in_one='.$allSubFile.'">Download submissions in one file</a><br/>';
}

if (REVPREFS && !$disFlag) {
  $indicatePrefs = '&nbsp;o&nbsp;&nbsp;<a href="prefs.php">Indicate reviewing preferences</a><br />';
}
else $indicatePrefs = '';

if ($disFlag) {
  $watchList = '&nbsp;o&nbsp;&nbsp;<a href="watchList.php">Preferences and watch list</a><br />';
}
else $watchList = '';

// listSubmissionsBox is defined in revFunctions.php
$listSubmissions = listSubmissionsBox($disFlag,$pcmFlags);

$showReviews = $allReviews = $uploadScores= '';
if ($disFlag) {         // Reviewer in the discussion phase
  $watchedSubs = discussion_phase($revId, empty($ballotsText), $pcmFlags);
  if ($watchedSubs) {
    $legend = show_legend(); // defined in confUtils.php
  }

  // showReviewsBox is defined in revFunctions.php
  $showReviews = "<td style=\"width: 270px;\">\n"
    . showReviewsBox($pcmFlags) . "</td>\n";
  $allReviews = '<br/>
&nbsp;o&nbsp;&nbsp;List all reviews and discussions<br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;in&nbsp;<a
href="listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on">html</a> or <a href="listReviews.php?ignoreWatch=on&amp;withReviews=on&amp;withDiscussion=on&amp;format=ascii">ascii</a>
<br/>';
} else  if (PERIOD==PERIOD_REVIEW) { // Reviewer still in the individual review phase
  individual_review($revId);
  $uploadScores = '<form accept-charset="utf-8" target=_blank action="parseScorecard.php"
enctype="multipart/form-data" method=POST>
Scorecard file: <input type=file size=40 name=scorecard>
<input type=submit value="Upload">
(<a target=_blank href="scorecard.php">what\'s that?</a>)<br/>
<b>Warning:</b> <i>Check that the file does not contain out-of-date
reviews!</i> Upload will overwrite previous reviews. 
</form>';
}

print <<<EndMark
<br/>
<table cellspacing=5 width="100%"><tbody><tr>
<!-- A box that lets the reviewer list submissions in different orders -->
<td style="width: 290px;">
$listSubmissions
</td>

<td><strong>Some other links:</strong><br />
$allSubFile
{$indicatePrefs}{$watchList}
&nbsp;o&nbsp;&nbsp;<a target=_blank href="scorecard.php">Work with scorecard files</a><br/>
&nbsp;o&nbsp;&nbsp;<a href="assignSubreviewer.php">Email subreviewers </a><br/>
$seeVoteRes
&nbsp;o&nbsp;&nbsp;<a href="password.php">Change password</a><br />
$allReviews
<br/>
&nbsp;o&nbsp;&nbsp;<a href="oops.php">Old versions of reviews...</a><br />
</td>

<!-- A box that lets the reviewer list reviews in different orders -->
$showReviews
</tr></tbody></table>
$uploadScores
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
  if (isset($_GET['sentMsg'])) {
    return '<div style="text-align: center; color: red;">Message sent or stored</div>';
  }

  return '';
}

function individual_review($revId)
{
  global $SQLprefix;
  $subs = array();
  $reviewed = array();

  $qry ="SELECT s.subId subId, title, s.format format, status, 
      UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
      a.watch watch, r.revId revId, r.flags revFlags, s.flags flags, s.contact
    FROM {$SQLprefix}submissions s
      INNER JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
      LEFT JOIN {$SQLprefix}reports r ON r.subId=s.subId AND r.revId=?
    WHERE (s.status!='Withdrawn' OR (s.flags & ?)) AND a.assign=1";
  $res = pdo_query($qry, array($revId,$revId,FLAG_IS_GROUP));

  $nReviewed = $total = 0;
  while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    if (isset($row["revId"])) { // already reviewed
      $subId = (int) $row["subId"];
      $notDraft = (int) $row["revFlags"];
      $reviewed[$subId] = $notDraft;
      $nReviewed += $notDraft;
    }
    $subs[] = $row;
    $total++;
  }
  print "So far you reviewed " . $nReviewed
    ." of the " . $total
    . " submissions that were assigned to you.\n";

  // Show reviewer his/her total, just to make him/her happy
  $qry = "SELECT COUNT(1) FROM {$SQLprefix}reports WHERE revId=? AND flags>0";
  $extra = pdo_query($qry, array($revId))->fetchColumn() - $nReviewed;
  if ($extra > 0) print "You also reviewed $extra other submissions. ";

  if ($total>0) {
    print "<br/><br/>\n";
    print_sub_list($subs, "Your assigned submissions", $reviewed);
  }
  else print "<br/>\n";
}

function discussion_phase($revId, $extraSpace, $pcmFlags)
{
  global $discussIcon1, $discussIcon2, $disFlag, $SQLprefix;
  $isChair = is_chair($revId);

  // Get a list of tags that this reviewer can see
  $tags = array();
  $qry = "SELECT tagName,subId FROM {$SQLprefix}tags WHERE ";
  if ($isChair) $qry .= "type=$revId OR type<=0";
  else          $qry .= "type IN ($revId,0)";

  $res = pdo_query($qry);
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $tag = $row[0];
    $subId = $row[1];
    if (!isset($tags[$subId])) $tags[$subId] = array($tag);
    else                       $tags[$subId][] = $tag;
  }

  // Get a list of submissions for which this reviewer already saw all
  // the discussions/reviews. Everything else is considered "new"
  $qry = "SELECT s.subId FROM {$SQLprefix}submissions s, {$SQLprefix}lastPost lp WHERE lp.revId=? AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
  $res = pdo_query($qry, array($revId));
  $seenSubs = array();
  while ($row = $res->fetch(PDO::FETCH_NUM)) { $seenSubs[$row[0]] = true; }

  // a list of submissions that this reviewer reviewed
  $reviewed = array();
  $qry = "SELECT subId, flags FROM {$SQLprefix}reports WHERE revId=?";
  $res = pdo_query($qry, array($revId));
  $reviewed = array();
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $row[0];
    $notDraft = (int) $row[1];
    $reviewed[$subId] = $notDraft;
  }

  // Get some statistics and other information
  $stdev = array();
  $res = pdo_query("SELECT STD(score) std, subId FROM {$SQLprefix}reports GROUP BY subId");
  while ($row = $res->fetch(PDO::FETCH_ASSOC))
    if (isset($row['std'])) $stdev[$row['subId']] = $row['std'];

  $conflicts = array();
  if ($isChair) {
    // Check for -1 or -2 assignments (conflict or PC-member paper)
    $res = pdo_query("SELECT MIN(assign) conflict, subId FROM {$SQLprefix}assignments GROUP BY subId");
    while ($row = $res->fetch(PDO::FETCH_ASSOC))
      if (isset($row['conflict'])) $conflicts[$row['subId']]= $row['conflict'];
  }

  // Determine ordering of watch-list submissions
  if ($pcmFlags & FLAG_ORDER_REVIEW_HOME) {
    $order = ($pcmFlags & 8)? 'status,' : '';
    switch ($pcmFlags % 8) {
    case 1:
      $order .= 'lastModif DESC, '; // sorted by modification date
      break;
    case 2:
      $order .= 's.wAvg DESC, ';    // sorted by weighted average
    default:
      break;
    }
  }
  else $order = '';                 // sorted by submission number

  $order .= 's.subId';
  $qry ="SELECT s.subId subId, title, s.format format, status,
      UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
      a.watch watch, s.wAvg avg, s.flags flags, s.contact
    FROM {$SQLprefix}submissions s, {$SQLprefix}assignments a
    WHERE (status!='Withdrawn' OR (s.flags & ?)) AND a.revId=? AND a.subId=s.subId AND a.watch=1 AND a.assign>=0 ORDER BY $order";
  $res = pdo_query($qry, array(FLAG_IS_GROUP,$revId));

  $needsDiscussion = 0;
  $subs = array();
  $subs2 = array();
  while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $subId = $row['subId'];
    $row['hasNew'] = !isset($seenSubs[$subId]);
    if (isset($tags[$subId])) $row['tags'] = $tags[$subId];
    if (isset($stdev[$subId])) $row['stdev'] = $stdev[$subId];
    if (isset($conflicts[$subId])) $row['conflict'] = $conflicts[$subId];
    if ($row['status']=='Needs Discussion') $needsDiscussion++;
    // sanitize for the case of "discuss most"
    if ($disFlag==2 && $row['assign']==1 && !isset($reviewed[$subId])) {
      $row['avg'] = NULL;
      $row['lastModif'] = '';
      $row['noDiscuss'] = true;
      $subs2[$subId] = $row;
    }
    else $subs[] = $row;
  }

  ksort($subs2);
  foreach($subs2 as $sb) $subs[] = $sb; // concatenate the arrays

  if (count($subs)>0) {
    if ($needsDiscussion > 0 && $disFlag==1) {

      if ($needsDiscussion==1) {  // singular vs plural in English
	$require = "requires";
	$needsDiscussion = 'one';
      }
      else { $require = "require"; }

      print "The chair indicated that $needsDiscussion of the submissions"
	. " on your watch list $require additional discussion.<br/><br/>\n";
    }
    else if ($extraSpace) print "<br/><br/>\n";
    print_sub_list($subs, "Submissions on your watch list",
		   $reviewed, $disFlag, 0, true, $revId);
    return true;
  }
  return false;
}

function votingText($disFlag)
{
  global $SQLprefix;
  $qry = "SELECT voteId, voteTitle, deadline FROM {$SQLprefix}votePrms WHERE voteActive=1";
  // Before the discussion phase, cannot vote on submissions
  if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";
  $elections = pdo_query($qry)->fetchAll(PDO::FETCH_NUM);
  if (count($elections)<= 0) return '';

  $html = "You can participate in the current active ballots:\n";
  $html .= "<blockquote><table border=1><tbody>\n"
    . "<tr align=left><th>Title</th><th>Deadline</th>\n";
  
  foreach ($elections as $row) {
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
