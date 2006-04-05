<?php
/* Web Submission and Review Software, version 0.51
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
if ($revId==CHAIR_ID && isset($_GET['revId'])) {
  $revId = (int) trim($_GET['revId']);
}

$cnnct = db_connect();
$qry = "SELECT s.title, c.name, r.subReviewer, r.confidence, r.grade,\n";

if (is_array($criteria)) {
  $qry .= "    ";
  $nCrit = count($criteria);
  for ($i=0; $i<$nCrit; $i++) { $qry .= "r.grade_{$i}, "; }
  $qry .= "\n";
}
else {$nCrit = 0;}

$qry .= "    r.comments2authors, r.comments2committee, r.comments2chair\n";
$qry .= "  FROM submissions s, committee c, reports r
  WHERE s.SubId={$subId} AND c.revId={$revId}
        AND r.SubId={$subId} AND r.revId={$revId}";

$res = db_query($qry, $cnnct);
if (!($row=mysql_fetch_row($res))) {
  exit("<h1>Review Not Found in Database</h1>");
}

$title      = htmlspecialchars($row[0]);
$name       = htmlspecialchars($row[1]);
$subReviewer= trim($row[2]);
if (!empty($subReviewer)) {
  $subReviewer = '(' . htmlspecialchars($subReviewer) . ')';
}
$confidence = isset($row[3]) ? ((int) $row[3]) : '*';
$grade      = isset($row[4]) ? ((int) $row[4]) : '*';

if ($nCrit > 0) {
  $zGrades = array();
  for ($i=0; $i<$nCrit; $i++)
    $zGrades[$i] = isset($row[5+$i]) ? ((int) $row[5+$i]) : '*';
}
$comments2authors  = htmlspecialchars($row[5+$nCrit]);
$comments2committee= htmlspecialchars($row[6+$nCrit]);
$comments2chair    = htmlspecialchars($row[7+$nCrit]);

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

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
<h1>Review for submission $subId</h1>
<h2>$title</h2>
<h3 style="text-align: center;">$name $subReviewer</h3>

<b>Grade:</b> $grade, &nbsp;
<b>Confidence:</b> $confidence
EndMark;

for ($i=0; $i<$nCrit; $i++) {
  $grdName = $criteria[$i][0];
  $grade = $zGrades[$i];
  print ", &nbsp;\n<b>$grdName:</b> $grade";
}

print "\n<h3>Comments to Authors</h3>\n";
print '<div class="fixed">'.nl2br($comments2authors).'</div>';

print "\n<h3>Comments to Committee</h3>\n";
print '<div class="fixed">'.nl2br($comments2committee).'</div>';

print "<h3>Comments to Chair</h3>\n";
print '<div class="fixed">'.nl2br($comments2chair).'</div>';

print <<<EndMark
<hr />
$links
</body>
</html>

EndMark;
?>
