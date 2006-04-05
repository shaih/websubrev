<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

// Is there a vote in progress or a previous vote?
clearstatcache();
if (file_exists("./review/voteParams.php")) {
  include "./review/voteParams.php";
  $whatVote = 'Current';
} else if (file_exists("./review/voteParams.bak.php")) {
  include "./review/voteParams.bak.php";
  $whatVote = 'Last';
}
// Get the vote results
$voters = array();
$vItems = array();
$voteResults = array();
$cnnct = db_connect();
if ($voteOnSubmissions) {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote, s.title title, c.name name
    FROM votes v INNER JOIN submissions s ON s.subId=v.subId
                 INNER JOIN committee c ON c.revId=v.revId
    WHERE vote>0 ORDER BY v.subId, v.revId";
} else {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote, c.name name
    FROM votes v INNER JOIN committee c USING(revId)
    WHERE vote>0 ORDER BY v.subId, v.revId";
}
$res = db_query($qry, $cnnct);
while ($row=mysql_fetch_assoc($res)) {
  $vId   = (int)$row['vId'];
  $revId = (int)$row['revId'];

  $title = $voteOnSubmissions ? $row['title'] : $voteTitles[$vId];
  $vItems[$vId] = htmlspecialchars($title);

  $name = explode(' ', $row['name']);
  if (is_array($name)) {
    for ($j=0; $j<count($name); $j++)
      $name[$j] = htmlspecialchars(substr($name[$j], 0, 7)); 
    $voters[$revId] = implode('<br/>', $name);
  }

  if (!isset($voteResults[$vId])) { $voteResults[$vId] = array(); }
  $voteResults[$vId][$revId] = (int)$row['vote'];
}

/* Show a matrix with all the votes in it 
 *******************************************************************/
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 { text-align: center;}
th { font: bold 10px ariel; text-align: center; }
td { text-align: center; }
</style>
<title>$whatVote Vote: Detailed Results</title>
</head>
<body>
$links
<hr />
<h1>$whatVote Vote: Detailed Results</h1>

<center>
<table cellspacing=0 cellpadding=0 border=1><tbody>

EndMark;

$header = "<tr>";
foreach ($voters as $name) { $header .= "  <th>".$name."</th>\n"; }
$header .= "  <th>Num</th>\n";
$header .= "  <th style=\"text-align: left;\">&nbsp;Title</th>\n</tr>\n";

$i = 0;
foreach ($vItems as $vId => $title) {
  if ($i == 0) print $header;
  $i++;
  if ($i > 5) $i = 0;

  print "<tr>";
  foreach ($voters as $revId => $name) {
    if ($voteResults[$vId][$revId]>0) {
      if ($voteType=='Grade')
	   print "  <td>" . $voteResults[$vId][$revId] . "</td>\n";
      else print "  <td>X</td>\n";
    }
    else print "  <td>&nbsp;</td>\n";
  }
  print "  <td style=\"font: bold 12px ariel;\">$vId.</td>\n";
  print "  <td style=\"text-align: left;\">$title</td>\n</tr>\n";
}

print <<<EndMark
</tbody></table>
</center>
<hr />
$links
</body>
</html>

EndMark;
?>
