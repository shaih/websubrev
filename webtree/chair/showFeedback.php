<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();

print <<<EndMark
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
tr {vertical-align: top;}
div {width: 90%;}
.fixed { font: 14px monospace; }
div.indented {position: relative; left: 25px;}
</style>
<title>Feedback on $cName Reviews</title>
</head>
<body>
$links
<hr/>
<h1>Feedback on $cName Reviews</h1>
EndMark;

$qry = "SELECT c.revId, c.name, r.subId, r.feedback, s.title
  FROM {$SQLprefix}committee c JOIN {$SQLprefix}reports r USING(revId)
       JOIN {$SQLprefix}submissions s ON r.subId=s.subId
  WHERE r.feedback IS NOT NULL ORDER by revId, subId";
$res = pdo_query($qry);

$reviewers = array();
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $rId = (int) $row['revId'];
  if (!isset($reviewers[$rId])) $reviewers[$rId] = array($row); // 1st row
  else                          $reviewers[$rId][] = $row;   // other rows
}

foreach ($reviewers as $list) if (!empty($list)) {
  $name = htmlspecialchars($list[0]['name']);
  $cnt = count($list);
  print "<h3>$name ($cnt feedbacks)</h3>\n";
  foreach ($list as $row) {
    print '<p><b>'.$row['subId'].'. '.$row['title']."</b><br/>\n";
    print $row['feedback']."<br/>\n";
    print '<a href="../review/discuss.php?subId='.$row['subId'].'" target="_blank">See discussion board</a>'."\n</p>\n\n";
  }
}
print "<hr/>\n{$links}\n</body></html>\n";
?>
