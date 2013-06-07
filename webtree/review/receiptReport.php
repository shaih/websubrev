<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)
$revId = (int) $pcMember[0];

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

// The chair is allowed to see the reciept of other people's reviews
$isChair = (is_chair($revId)
	    && isset($_GET['revId']) && $revId!=$_GET['revId']);
if ($isChair)  $revId = (int) trim($_GET['revId']);

// Is this a new report or an existing one?
if (isset($_GET['bckpVersion']) && $_GET['bckpVersion']>0) { // an existing one
  $table = "reportBckp";
  $ztable = "gradeBckp";
  $version = (int) $_GET['bckpVersion'];
  $old = '(old version)';
} else {                                                     // a new report
  $table = "reports";
  $ztable = "auxGrades";
  $old = '';
  /*
  $qry = "SELECT 1+MAX(version) FROM {$SQLprefix}reportBckp WHERE subId=? AND revId=?";
  $version = pdo_query($qry, array($subId,$revId))->fetchColumn();
  if (!isset($version)) $version = 1;
  */
}

$qry = "SELECT s.title, c.name, r.subReviewer, r.confidence, r.score,
   r.comments2authors, r.comments2committee, r.comments2chair,
   r.comments2self, r.attachment
FROM {$SQLprefix}submissions s, {$SQLprefix}committee c, {$SQLprefix}$table r
WHERE s.subId=? AND c.revId=? AND r.subId=s.subId AND r.revId=c.revId";

$qry2 = "SELECT gradeId, grade FROM {$SQLprefix}$ztable WHERE subId=? AND revId=?";

if (isset($_GET['bckpVersion']) && $_GET['bckpVersion']>0) {
  $qry .= " AND version=$version";
  $qry2 .= " AND version=$version";
}

$res = pdo_query($qry, array($subId,$revId));
$auxRes = pdo_query($qry2, array($subId,$revId));

if (!($row=$res->fetch(PDO::FETCH_NUM))) {
  exit("<h1>Review Not Found in Database</h1>");
}
$title             = htmlspecialchars($row[0]);
$name              = htmlspecialchars($row[1]);
$subReviewer       = trim($row[2]);
if (!empty($subReviewer)) {
  $subReviewer     = '(' . htmlspecialchars($subReviewer) . ')';
}
$confidence = isset($row[3]) ? ((int) $row[3]) : '*';
$score      = isset($row[4]) ? ((int) $row[4]) : '*';
$comments2authors  = htmlspecialchars($row[5]);
$comments2committee= htmlspecialchars($row[6]);
$comments2chair    = htmlspecialchars($row[7]);
$comments2self    = htmlspecialchars($row[8]);
$attachment = $row[9];
$ext = strtoupper(file_extension($attachment));

$zGrades = array();
while ($row=$auxRes->fetch(PDO::FETCH_NUM)) {
  $gId = (int) $row[0];
  $zGrades[$gId] = $row[1]; // no cast to int, NULL remains NULL
}

if (isset($attachment)) {
  $attachment = "<br/><br/><a href=\"download.php?attachment=$attachment\">$ext attachment</a>\n";
}
else $attachment = '';

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1, h2 { text-align: center;}
.rjust { text-align: right; }
div.fixed { font: 14px monospace; width: 90%;}
</style>

<title>Review for submission $subId</title>
</head>
<body>
$links
<hr />
<h1>Review for submission $subId $old</h1>
<h2>$title</h2>
<h3 style="text-align: center;">$name $subReviewer</h3>

<b>Score:</b> $score, &nbsp;
<b>Confidence:</b> $confidence
EndMark;

$nCrit = is_array($criteria)? count($criteria): 0;
for ($i=0; $i<$nCrit; $i++) {
  $grdName = $criteria[$i][0];
  $grade = isset($zGrades[$i]) ? ((int) $zGrades[$i]) : '*';
  print ", &nbsp;\n<b>$grdName:</b> $grade";
}

print "\n<h3>Comments to Authors</h3>\n";
print '<div class="fixed">'.nl2br($comments2authors).$attachment.'</div>';

print "\n<h3>Comments to Committee</h3>\n";
print '<div class="fixed">'.nl2br($comments2committee).'</div>';

print "<h3>Comments to Chair</h3>\n";
print '<div class="fixed">'.nl2br($comments2chair).'</div>';

if (!$isChair) {
  print "<h3>Notes to myself</h3>\n";
  print '<div class="fixed">'.nl2br($comments2self).'</div>';
}
print <<<EndMark
<hr />
$links
</body>
</html>

EndMark;
?>
