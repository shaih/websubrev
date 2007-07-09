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

// Prepare a list of submissions that the current member reviewed
$cnnct = db_connect();
$qry = "SELECT subId, flags FROM reports WHERE revId='$revId'";
$res = db_query($qry, $cnnct);
$reviewed = array();
while ($row = mysql_fetch_row($res)) {
  $subId = (int) $row[0];
  $notDraft = (int) $row[1];
  $reviewed[$subId] = $notDraft;
}

// Get a list of submissions for which this reviewer already saw all
// the discussions/reviews. Everything else is considered "new"
$seenSubs = array();
if ($disFlag) {
  $qry = "SELECT s.subId FROM submissions s, lastPost lp WHERE lp.revId=$revId AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
  $res = db_query($qry, $cnnct);
  while ($row = mysql_fetch_row($res)) { $seenSubs[$row[0]] = true; }
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

$qry ="SELECT s.subId subId, title, authors, abstract, s.format format,
       status, UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
       a.watch watch, s.avg avg, (s.maxGrade-s.minGrade) delta, category
    FROM submissions s
         LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId
    WHERE status!='Withdrawn' {$assignedOnly}
    ORDER BY $order";
    $res = db_query($qry, $cnnct);

$assigned = array();
$others = array();
while ($row = mysql_fetch_assoc($res)) {
  $subId = $row['subId'];
  $row['hasNew'] = !isset($seenSubs[$subId]);
  if ($row['assign']==1 && !isset($_GET['ignoreAssign']))
    $assigned[] = $row;
  else if ($row['assign']!=-1)
    $others[] = $row; 
}
if (isset($_GET['ignoreAssign'])) $flags |= 32;

// Display results to the user
$links = show_rev_links(3);
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
h1 { text-align: center; }
.fixed { font: 14px monospace; }
</style>

<title>Submission List (by $heading)</title>
</head>
<body>
$links
<hr />
<h1>Submission List (by $heading)</h1>

EndMark;
if ($disFlag) {
  print "Note: You can click on the eye icons on the left to add/remove submissions from your <a href=\"../documentation/reviewer.html#watch\" target=documentation>watch list</a><br/><br/>\n";
}

if (isset($_GET['abstract'])) {
  $flags |= 64;
  $showMore |= 1;
}
if (isset($_GET['category'])) {
  $flags |= 128;
  $showMore |= 2;
}

if (count($assigned)>0) {
  print_sub_list($assigned, "Submissions assigned to $revName", 
		 $reviewed, $disFlag, $showMore);
  print "\n<br />\n";
  $otherName = "Other submissions";
} else { $otherName = ""; }

if (count($others)>0) {
  print_sub_list($others, $otherName, $reviewed, $disFlag, $showMore);
}

if ($disFlag && (count($assigned)>0 || count($others)>0))
     print show_legend(); // defined in confUtils.php

print "\n<hr />\n{$links}\n</body>\n</html>\n";

if (isset($_GET['listBox'])) { // remember the flags for next time
  $pcmFlags &= 0xffffff00;
  $pcmFlags |= $flags;
  db_query("UPDATE committee SET flags=$pcmFlags WHERE revId=$revId", $cnnct);
}
?>
