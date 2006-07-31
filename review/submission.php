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
       s.format, a.assign, a.watch, r.revId
    FROM submissions s
       LEFT JOIN assignments a ON a.revId=$revId AND a.subId=s.subId
       LEFT JOIN reports r ON r.revId='$revId' AND r.subId=s.subId
    WHERE s.subId=$subId AND status!='Withdrawn'";

$cnnct = db_connect();
$res = db_query($qry, $cnnct);
if (!($submission = mysql_fetch_assoc($res))
    || $submission['assign']==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}
$title    = htmlspecialchars($submission['title']);
$authors  = ANONYMOUS ? '' :
       '<h3>'.htmlspecialchars($submission['authors']).'</h3>';
$abstract = nl2br(htmlspecialchars($submission['abstract']));
$category = htmlspecialchars($submission['category']);
if (empty($category)) $category = '*';
$keyWords = htmlspecialchars($submission['keyWords']);
$format   = htmlspecialchars($submission['format']);
$watch    = (int) $submission['watch'];

if ($disFlag == 1) { 

  // Check for things in the discussion board that the reviewe didn't see yet
  $qry = "SELECT 1 FROM submissions s, lastPost lp WHERE s.subId=$subId AND lp.revId=$revId AND lp.subId=$subId AND s.lastModified<=lp.lastVisited";
  $res = db_query($qry, $cnnct);

  // If there are matches to the query above, it means that there are
  // no new items in the discussoin board. The vars $discussIcon1 and
  // $discussIcon2 are defined in confUtils.php
  $discussText = (mysql_num_rows($res)>0) ? $discussIcon1 : $discussIcon2;

  $toggleWatch = "<a href=\"toggleWatch.php?subId={$subId}\">\n";
  if ($watch == 1) {
    $src = 'openeye.gif'; $alt = 'W';
    $tooltip = "Click to remove from watch list";
  }
  else {
    $src = 'shuteye.gif'; $alt = 'X';
    $tooltip = "Click to add to watch list";
  }
  $toggleWatch .= "  <img src=\"$src\" alt=\"$alt\" title=\"$tooltip\" border=\"0\"></a>&nbsp;";
}
else $toggleWatch = $discussText = '';

// The styles are defiend in review.css, the icon constants in confUtils.php
if (isset($submission['revId'])) {// PC member already reviewed this submission
  $revStyle = "Revise";
  $revText = $reviseIcon;
} else {
  $revStyle = "Review";
  $revText = $reviewIcon;
}
$subFile = '../'.SUBMIT_DIR."/$subId.$format";

// $pageWidth = 720; body { width : {$pageWidth}px; }

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

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
<a href="$subFile" title="download"><img src="download.gif" alt="download"
   border=0></a>
<span class="Discuss"><a href="discuss.php?subId=$subId" target="_blank">$discussText</a></span>
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
<a href="$subFile" title="download"><img src="download.gif" alt="download"
   border=0></a>
<span class="Discuss"><a href="discuss.php?subId=$subId" target="_blank">$discussText</a></span>
<span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>&nbsp;
$toggleWatch
</center>
<hr />
$links
</body>
</html>
EndMark;
?>
