<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

$qry ="SELECT s.title, s.authors, s.abstract, s.category, s.keyWords,
       s.format, a.assign, a.watch, r.revId, s.affiliations
    FROM {$SQLprefix}submissions s
       LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
       LEFT JOIN {$SQLprefix}reports r ON r.revId=? AND r.subId=s.subId
    WHERE s.subId=? AND status!='Withdrawn'";

$submission = 
  pdo_query($qry, array($revId,$revId,$subId))->fetch(PDO::FETCH_ASSOC);
if (!$submission || $submission['assign']==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$title    = htmlspecialchars($submission['title']);
if (ANONYMOUS && !is_chair($revId)) { $authors = ''; }
else {
  $affiliations = htmlspecialchars($submission['affiliations']);
  $authors = htmlspecialchars($submission['authors']);

  if (empty($affiliations)) $authors = "<h3>$authors</h3>";
  else $authors = "<h3>$authors<br/><small>$affiliations</small></h3>";
}
$abstract = nl2br(htmlspecialchars($submission['abstract']));
$category = htmlspecialchars($submission['category']);
if (empty($category)) $category = '*';
$keyWords = htmlspecialchars($submission['keyWords']);
$format   = htmlspecialchars($submission['format']);
$watch    = (int) $submission['watch'];

if ($disFlag == 1) { 

  // Check for things in the discussion board that the reviewe didn't see yet
  $qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions s, {$SQLprefix}lastPost lp WHERE s.subId=? AND lp.revId=? AND lp.subId=s.subId AND s.lastModified<=lp.lastVisited";
  $res = pdo_query($qry, array($subId,$revId));

  // If there are matches to the query above, it means that there are
  // no new items in the discussoin board. The vars $discussIcon1 and
  // $discussIcon2 are defined in confUtils.php
  $discussText = ($res->fetchColumn()>0) ? $discussIcon1 : $discussIcon2;

  $discussLine = '<span class="Discuss"><a href="discuss.php?subId='.$subId.'" target="_blank">'.$discussText.'</a></span>';

  $toggleWatch = "<a href=\"toggleWatch.php?subId={$subId}\">\n";
  if ($watch == 1) {
    $src = '../common/openeye.gif'; $alt = 'W';
    $tooltip = "Click to remove from watch list";
  }
  else {
    $src = '../common/shuteye.gif'; $alt = 'X';
    $tooltip = "Click to add to watch list";
  }
  $toggleWatch .= "  <img src=\"$src\" alt=\"$alt\" title=\"$tooltip\" border=\"0\"></a>&nbsp;";
}
else $toggleWatch = $discussLine = '';

// The styles are defiend in ../common/review.css, the icons in confUtils.php
if (isset($submission['revId'])) {// PC member already reviewed this submission
  $revStyle = "Revise";
  $revText = $reviseIcon;
} else {
  $revStyle = "Review";
  $revText = $reviewIcon;
}

// $pageWidth = 720; body { width : {$pageWidth}px; }

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1, h2, h3 {text-align: center;}
div.fixed { font: 14px monospace; width: 90%; }
</style>

<title>Submission $subId: $title</title>
</head>
<body>
$links
<hr />
<center>
<a href="download.php?subId=$subId" title="download"><img src="../common/download.gif" alt="download"
   border=0></a>
$discussLine
<span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>&nbsp;
$toggleWatch
</center>
<h1>Submission $subId: $title</h1>
$authors

<b>Abstract:</b>
<div class="fixed">$abstract</div>
<br />

<b>Category/KeyWords:</b> $category / $keyWords

<br /><br />
<center>
<a href="download.php?subId=$subId" title="download"><img src="../common/download.gif" alt="download"
   border=0></a>
$discussLine
<span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>&nbsp;
$toggleWatch
</center>
<hr />
$links
</body>
</html>
EndMark;
?>
