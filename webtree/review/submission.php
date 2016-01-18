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
$isChair = is_chair($revId);

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

$qry ="SELECT s.title, s.authors, s.abstract, s.category, s.keyWords, s.format,
    s.auxMaterial, a.assign, a.watch, r.revId, s.affiliations
    FROM {$SQLprefix}submissions s
       LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
       LEFT JOIN {$SQLprefix}reports r ON r.revId=? AND r.subId=s.subId
    WHERE s.subId=? AND status!='Withdrawn'";

$submission = 
  pdo_query($qry, array($revId,$revId,$subId))->fetch(PDO::FETCH_ASSOC);
if (!$submission || $submission['assign']<0) {
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
$auxMaterial = $submission['auxMaterial'];
$watch    = (int) $submission['watch'];

$downLoad = '<a href="download.php?subId='.$subId.'" title="download"><img src="../common/download.gif" alt="download" border=0></a>';
if (!empty($auxMaterial)) {
  $downLoad .= '<a href="download.php?subId='.$subId.'&aux=yes" title="Auxiliary Material"><img src="../common/auxi.gif" alt="Aux" border=0></a>';
}

if ($disFlag) {

  // Check for things in the discussion board that the reviewe didn't see yet
  $qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions s, {$SQLprefix}lastPost lp WHERE s.subId=? AND lp.revId=? AND lp.subId=s.subId AND s.lastModified<=lp.lastVisited";
  $res = pdo_query($qry, array($subId,$revId));

  // If there are matches to the query above, it means that there are
  // no new items in the discussoin board. The vars $discussIcon1 and
  // $discussIcon2 are defined in confUtils.php
  $allRead = ($res->fetchColumn()>0);
  $discussText = $allRead ? $discussIcon1 : $discussIcon2;

  $discussLine = "<span class='Discuss'><a href='discuss.php?subId=$subId' target='_blank'>$discussText</a><a href='toggleMarkRead.php?subId=$subId&current=$allRead' class='toggleRead' title='Toggle Read/Unread' ID='toggleRead$subId' rel='$allRead'>&bull;</a></span>";

  if ($watch == 1) {
    $src = '../common/openeye.gif'; $alt = 'W';
    $tooltip = "Click to remove from watch list";
  }
  else {
    $watch = 0; // just making sure
    $src = '../common/shuteye.gif'; $alt = 'X';
    $tooltip = "Click to add to watch list";
  }
  // below is an "old fashioned" use of the rel attribute to keep data
  $toggleWatch = "<a rel='$watch' href='toggleWatch.php?subId={$subId}&current=$watch'><img src='$src' id='toggleWatch$subId' alt='$alt' title='$tooltip' border='0'></a>&nbsp;";

  // get the tags for this submission
  $qry = "SELECT tagName FROM {$SQLprefix}tags WHERE subId=? AND ";
  if ($isChair) $qry .= '(type=? OR type <=0)';
  else          $qry .= 'type IN (?,0)';
  $tagArray = pdo_query($qry,array($subId,$revId))->fetchAll(PDO::FETCH_NUM);
  foreach ($tagArray as $i=> $row) $tagArray[$i] = $row[0];
  $tags = showTags($tagArray, $subId, $isChair); // in revFunctions.php
}
else $toggleWatch = $discussLine = $tags = '';

// The styles are defiend in ../common/review.css, the icons in confUtils.php
if (isset($submission['revId'])) {// PC member already reviewed this submission
  $revStyle = "Revise";
  $revText = $reviseIcon;
} else {
  $revStyle = "Review";
  $revText = $reviewIcon;
}

// If allowing multiple uploaded versions, show a list
$versions = '';
$fmt = empty($submission['format'])? '' : ".".$submission['format'];
$ver = 1;
while (true) {
  $fullName = SUBMIT_DIR."/".$subId."-".$ver.$fmt;
  if (file_exists($fullName)) {
    $versions .= "<li><a href=\"download.php?subId={$subId}&ver={$ver}\">"
      . date("F d Y, H:i:s (T)", filemtime($fullName)). "</a></li>\n";
    $ver++;
  }
  else break;
}
if (!empty($versions)) {
  $versions = "<b>Older Versions (since the deadline):</b><ol>\n".$versions."</ol><hr/>\n";
}

// Show all the conflicts that the authors specified
$conflictLine = '';
if ($isChair) {
  $qry = "SELECT a.authConflict, c.name FROM {$SQLprefix}assignments a, {$SQLprefix}committee c WHERE a.subId=$subId AND a.authConflict>'' AND c.revId=a.revId";
  $res = pdo_query($qry);
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    if (!empty(trim($row[0]))) // check again for non-emptyness
      $conflictLine .= "<li>{$row[1]}: {$row[0]}</li>\n";
  }
}
if (!empty($conflictLine)) {
  $conflictLine = "<h4>Author-specified conflicts:</h4>\n<ul>$conflictLine</ul>\n";
}


$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="toggleImage.js"></script>
<style type="text/css">
h1, h2, h3 {text-align: center;}
div.fixed { font: 14px monospace; width: 90%; }
</style>

<title>Submission $subId: $title</title>
</head>
<body>
$links
<hr />
$tags
<center>
$downLoad
$discussLine
<span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>&nbsp;
$toggleWatch
</center>
<h1>Submission $subId: $title</h1>
$authors

<b>Abstract:</b>
<div class="fixed">$abstract</div>
<br/>

<b>Category/KeyWords:</b> $category / $keyWords

<br/>
$conflictLine
<br/>
<center>
$downLoad
$discussLine
<span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>&nbsp;
$toggleWatch
</center>
<hr/>
$versions
$links
</body>
</html>
EndMark;
?>
