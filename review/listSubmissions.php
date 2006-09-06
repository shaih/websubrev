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

// Prepare a list of submissions that the current member reviewed
$cnnct = db_connect();
$qry = "SELECT subId FROM reports WHERE revId='$revId'";
$res = db_query($qry, $cnnct);
$reviewed = array();
while ($row = mysql_fetch_row($res)) { $reviewed[$row[0]] = true; }

// Get a list of submissions for which this reviewer already saw all
// the discussions/reviews. Everything else is considered "new"
$seenSubs = array();
if ($disFlag) {
  $qry = "SELECT s.subId FROM submissions s, lastPost lp WHERE lp.revId=$revId AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
  $res = db_query($qry, $cnnct);
  while ($row = mysql_fetch_row($res)) { $seenSubs[$row[0]] = true; }
}


// Prepare the ORDER BY clause for submission list (default is by number)
list($order, $heading) = order_clause();
if (empty($order)) { $order = 'subId ASC'; $heading='number';}

// Limit to only assigned submissions?
if (isset($_GET['onlyAssigned'])) {
  $assignedOnly = "AND a.assign=1";
} else {
  $assignedOnly = "";
}

$qry ="SELECT s.subId subId, title, authors, abstract, s.format format,
       status, UNIX_TIMESTAMP(s.lastModified) lastModif, a.assign assign, 
       a.watch watch, s.avg avg, (s.maxGrade-s.minGrade) delta
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

// Display results to the user
$links = show_rev_links(3);
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type"/>
<link rel="stylesheet" type="text/css" href="review.css" />
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
  print "Note: You can click on the eye icons on the left to add/remove submissions from your <a href=\"../documentation/reviewer.html#watch\">watch list</a><br/><br/>\n";
}

$showAbst = isset($_GET['abstract']);

if (count($assigned)>0) {
  print_sub_list($assigned, "Submissions assigned to $revName", 
		 $reviewed, $disFlag, $showAbst);
  print "\n<br />\n";
  $otherName = "Other submissions";
} else { $otherName = ""; }

if (count($others)>0) {
  print_sub_list($others, $otherName, $reviewed, $disFlag, $showAbst);
}

if ($disFlag && (count($assigned)>0 || count($others)>0))
     print show_legend(); // defined in confUtils.php

print "\n<hr />\n{$links}";
?>
</body>
</html>
