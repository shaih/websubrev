<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;

require 'header.php'; // defines $pcMember=array(id, name, ...)

require 'printSubList.php';
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];
$pcmFlags= (int) $pcMember[5];
$isChair = is_chair($revId);

// Prepare a list of submissions that the current member reviewed
$qry = "SELECT subId, flags FROM {$SQLprefix}reports WHERE revId=?";
$res = pdo_query($qry, array($revId));
$reviewed = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = (int) $row[0];
  $notDraft = (int) $row[1];
  $reviewed[$subId] = $notDraft;
}

// Get a list of tags that this reviewer can see
$tags = array();
$qry = "SELECT tagName,subId FROM {$SQLprefix}tags WHERE type IN ($revId,0) "
  . ($isChair? 'OR type<0 ' : '') . 'ORDER BY subId,tagName';
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $tag = $row[0];
  $subId = $row[1];
  if (!isset($tags[$subId])) $tags[$subId] = array($tag);
  else                       $tags[$subId][] = $tag;
}

// For the chair: check if submissions have conflicts
$conflicts = array();
if ($isChair) {
  // Check for -1 or -2 assignments (conflict or PC-member paper)
  $res = pdo_query("SELECT MIN(assign) conflict, subId FROM {$SQLprefix}assignments GROUP BY subId");
  while ($row = $res->fetch(PDO::FETCH_ASSOC))
    if (isset($row['conflict'])) $conflicts[$row['subId']]= $row['conflict'];
}

// Get a list of submissions for which this reviewer already saw all
// the discussions/reviews. Everything else is considered "new"
$seenSubs = array();
if ($disFlag) {
  $qry = "SELECT s.subId FROM {$SQLprefix}submissions s, {$SQLprefix}lastPost lp WHERE lp.revId=? AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
  $res = pdo_query($qry, array($revId));
  while ($row = $res->fetch(PDO::FETCH_NUM)) { $seenSubs[$row[0]] = true; }
}


// Prepare the ORDER BY clause for submission list (default is by number)
list($order, $heading,$flags) = order_clause();
if (empty($order)) { $order = 'subId ASC'; $heading='number';}

// Limit to only assigned submissions?
if (isset($_GET['onlyAssigned'])) {
  $assignedOnly = "AND a.assign=1";
  $flags |= 16;
} else {
  $assignedOnly = "";
}

if (isset($_GET['onlyDiscussed'])) $flags |= 0x10000;
  // only submissions that I discussed

$opted_in = "";
if (isset($_GET['optedIn'])) {
  $opted_in = "AND s.flags & ".FLAG_IS_CHECKED;
  $flags |= 0x20000; // only opt-in submissions
}

$qry ="SELECT s.subId subId, title, authors, abstract, s.format format,
       status, UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
       a.watch watch, s.avg avg, STD(r.score) stdev, category, AVG(r.confidence) as avgConf, s.flags flags, s.contact contact
    FROM {$SQLprefix}submissions s
         LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
         LEFT JOIN {$SQLprefix}reports r ON r.subId=s.subId ";
if ($disFlag==1)
     $qry .= "WHERE (status!='Withdrawn' OR (s.flags & ".FLAG_IS_GROUP."))";
else $qry .= "WHERE (status!='Withdrawn')";
$qry .= " {$assignedOnly} {$opted_in} GROUP BY subId ORDER BY $order";
$res = pdo_query($qry, array($revId));
$specialSubs = array();
$othersSubs = array();
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  if(isset($_GET['onlyDiscussed'])) {
    if (!has_discussed($revId, $row['subId'])){
      continue;
    }
  }
  $subId = $row['subId'];
  $row['hasNew'] = !isset($seenSubs[$subId]);
  if (isset($tags[$subId])) $row['tags'] = $tags[$subId];
  else                      $row['tags'] = array();

  // If tags are specified, only keep submissions with these tags
  if (!empty($_GET['allTags']) && !allTagsExist($row['tags'],$_GET['allTags']))
    continue; 
  if (!empty($_GET['someTags'])&&!someTagsExist($row['tags'],$_GET['someTags']))
    continue;

  if (isset($conflicts[$subId])) $row['conflict'] = $conflicts[$subId];
  // sanitize for the case of "discuss most"
  if ($disFlag==2 && $row['assign']==1 && !isset($reviewed[$subId])) {
    $row['avg'] = NULL;
    $row['stdev'] = NULL;
    $row['avgConf'] = NULL;
    $row['lastModif'] = NULL;
    $row['noDiscuss'] = true;
    $specialSubs[$subId] = $row;
  }
  else if (($row['assign']>=0) && !has_group_conflict($revId, $row['title']))
    $othersSubs[] = $row; 
}




// Display results to the user
$links = show_rev_links(3);

print <<<EndMark
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<style type="text/css">
h1 { text-align: center; }
.fixed { font: 14px monospace; }
</style>

<title>Submission List (by $heading)</title>
<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="toggleImage.js"></script>
</head>
<body>
$links
<hr />
<h1>Submission List (by $heading)</h1>

EndMark;
if ($disFlag) {
  print "Note: You can click on the eye icons on the left to add/remove submissions from your <a href=\"../documentation/reviewer.html#watch\" target=documentation>watch list</a><br/><br/>\n";
}
$showMore = 0;
if (isset($_GET['showTags'])) {
  $flags |= 32;
}
if (isset($_GET['abstract'])) {
  $flags |= 64;
  $showMore |= 1;
}
if (isset($_GET['category'])) {
  $flags |= 128;
  $showMore |= 2;
}

$otherName = "";
if (count($specialSubs)>0) {
  ksort($specialSubs);
  print_sub_list($specialSubs, "Submissions assigned and not reviewed", 
		 $reviewed, 0, $showMore, false, $revId);
  print "\n<br />\n";
  $otherName = "Other submissions";
}

if (count($othersSubs)>0) {
  print_sub_list($othersSubs, $otherName, 
		 $reviewed, $disFlag, $showMore, false, $revId);
}

if ($disFlag && count($othersSubs)>0)
  print show_legend(); // defined in confUtils.php

print "\n<hr />\n{$links}\n</body>\n</html>\n";

if (isset($_GET['listBox'])) { // remember the flags for next time
  $pcmFlags &= 0xfffcff00;
  $pcmFlags |= $flags;
  pdo_query("UPDATE {$SQLprefix}committee SET flags=? WHERE revId=?",
	    array($pcmFlags,$revId));
}
?>
