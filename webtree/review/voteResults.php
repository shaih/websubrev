<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$revId  = (int) $pcMember[0];
$disFlag = (int) $pcMember[3];
$pcmFlags= (int) $pcMember[5];

$cnnct = db_connect();
$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<style type="text/css">
h1 { text-align: center;}
th { font: bold 10px ariel; text-align: center; }
</style>
<title>Vote Results</title>
</head>
<body>
$links
<hr/>
EndMark;

$qry = "SELECT * FROM votePrms WHERE voteActive=0";
// If a vote is specified, display results of this vote
if (isset($_GET['voteId']) && $_GET['voteId']>0) {
  $voteId = $_GET['voteId'];
  $qry .= " AND voteId=$voteId";
}
// Before the discussion phase, cannot see votes on submissions
if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";
$qry .= " ORDER by voteId DESC";
$res = db_query($qry, $cnnct) or die('No results found</body></html>');
if (mysql_num_rows($res)<= 0) die('No results found</body></html>');

// If more than one vote matches, display a list of them
if (mysql_num_rows($res) > 1) {
  print "<h1>Vote Results</h1>\nChoose the vote results to see\n<ul>\n";
  while ($row = mysql_fetch_array($res)) {
    $voteId = intval($row['voteId']);
    if (isset($row['voteTitle'])) $voteTitle=htmlspecialchars($row['voteTitle']);
    else $voteTitle = "Ballot #".$voteId;
    print "<li><a href=\"voteResults.php?voteId=$voteId\">$voteTitle</a></li>\n";
  }
  print "</ul>\n<hr/>\n$links\n</body></html>";
}

$row = mysql_fetch_array($res);
$voteId = intval($row['voteId']);
$voteTitle = isset($row['voteTitle']) ? htmlspecialchars($row['voteTitle']):'';
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteOnThese = isset($row['voteOnThese'])? $row['voteOnThese'] : '';
if (empty($voteTitle)) $voteTitle = "Ballot #$voteId";
if ($voteFlags & VOTE_ON_SUBS) $voteTitles = NULL;
else $voteTitles = explode(';', ';'.$voteOnThese);
// the above puts an empty string at index 0 to make the array start at index 1

$noVotes = true;

// Print the voting tally for this vote

if ($voteFlags & VOTE_ON_SUBS) {
  $qry = "SELECT s.subId, SUM(v.vote) sum, title, a.assign
  FROM submissions s, votes v LEFT JOIN assignments a ON a.subId=v.subID AND a.revId=$revId
  WHERE v.voteId=$voteId AND s.subId=v.subId
  GROUP BY s.subId ORDER BY sum DESC, s.subId ASC";
  $res = db_query($qry, $cnnct);

  $voteItems = array();
  while ($row=mysql_fetch_row($res)) {
    if ($row[1] < 0 || $row[3]==-1) continue;
    $noVotes = false;
    $voteItems[] = $row;
  }
} else {
  $qry = "SELECT subId, SUM(vote) sum FROM votes
  WHERE voteId=$voteId GROUP BY subId ORDER BY sum DESC, subId ASC";
  $res = db_query($qry, $cnnct);

  $voteItems = array();
  while ($row=mysql_fetch_row($res)) {
    if ($row[1] < 0) continue;
    $noVotes = false;

    $vId = (int) $row[0];
    $voteItems[] = array($vId, $row[1], $voteTitles[$vId]);
  }
}

if ($noVotes) die("No Results Recorded");

print "<h1>Results: $voteTitle</h1>\n";
$html = '';
foreach ($voteItems as $vItem) {
  $itemId = (int) $vItem[0];
  $tally  = (int) $vItem[1];
  $title  = isset($vItem[2]) ? htmlspecialchars($vItem[2]) : '';
  if ($tally==0) continue;
  if ($voteFlags & VOTE_ON_SUBS) $title = '<a href="../review/submission.php?subId='.$itemId.'">'.$title .'</a>';

  $html .= "<tr><td align=center>". $tally ."</td>";
  $html .= "<td>$itemId.</td><td>$title</td>\n";
  $html .= "</tr>\n";
}

if (!empty($html)) {
  print "<table><tbody>\n<tr>\n  <th>Tally</th><th>Num</th>"
	."<th style=\"text-align: left;\">&nbsp; Title</th>\n</tr>\n";
  print $html;
  print "</tbody></table>\n";
}
else print "No non-zero votes.<br/><br/>\n";

print <<<EndMark
<hr />
$links
</body>
</html>

EndMark;
?>
