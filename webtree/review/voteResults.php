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

$qry = "SELECT * FROM votePrms WHERE ";
if ($revId != CHAIR_ID) $qry .= "voteActive=0 AND ";

// If a vote is specified, display results of this vote
if (isset($_GET['voteId']) && $_GET['voteId']>0) {
  $voteId = (int) $_GET['voteId'];
  $qry .= " voteId=$voteId AND ";
}

// Before the discussion phase, cannot see votes on submissions
if ($revId != CHAIR_ID && !$disFlag) $qry .= "(voteFlags&1)!=1 AND ";
$qry .= "TRUE ORDER by voteId DESC";
$res = db_query($qry, $cnnct) or die('Cannot query database</body></html>');
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
  exit("</ul>\n<hr/>\n$links\n</body></html>");
}

// Only one match, display the tally and maybe also the details
$row = mysql_fetch_array($res);
$voteId = intval($row['voteId']);
$active = (isset($row['voteActive'])&& $row['voteActive']>0) ? 'in progress' : 'closed';
$voteType  = isset($row['voteType'])  ? htmlspecialchars($row['voteType']) : 'Choose';
$voteTitle = isset($row['voteTitle']) ? htmlspecialchars($row['voteTitle']):'';
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteBudget= isset($row['voteBudget'])? intval($row['voteBudget']): 0;
$voteOnThese = isset($row['voteOnThese'])? $row['voteOnThese'] : '';
$voteMaxGrade= isset($row['voteMaxGrade'])? intval($row['voteMaxGrade']): 1;
if (empty($voteTitle)) $voteTitle = "Ballot #$voteId";
if ($voteFlags & VOTE_ON_SUBS) $voteTitles = NULL;
else $voteTitles = explode(';', ';'.$voteOnThese);
// the above puts an empty string at index 0 to make the array start at index 1

$noVotes = true;

// Print the voting tally for this vote

if ($voteFlags & VOTE_ON_SUBS) {
  $qry = "SELECT s.subId, SUM(v.vote) sum, title, a.assign
  FROM submissions s, votes v LEFT JOIN assignments a ON a.subId=v.subId AND a.revId=$revId
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

if (empty($html) ||
    ($revId != CHAIR_ID && ($voteFlags & FLAG_SHOW_VOTE_DETAILS)==0)) {
  exit("<hr/>\n{$links}\n</body></html>\n");
}

// Show the details of the vote
// Get the names of PC membes that voted in the current ballot
$voters = array();
$qry = "SELECT c.revId revId, c.name name, COUNT(v.vote) dummy
  FROM committee c, votes v
  WHERE v.voteId=$voteId AND c.revId=v.revId GROUP BY revId ORDER BY revId";
$res = db_query($qry, $cnnct);
while ($row=mysql_fetch_row($res)) {
  $rId = (int)$row[0];
  $name = explode(' ', $row[1]);
  if (is_array($name)) {
    for ($j=0; $j<count($name); $j++)
      $name[$j] = htmlspecialchars(substr($name[$j], 0, 7)); 
    $voters[$rId] = implode('<br/>', $name);
  }
}

// Get the vote results
$vItems = array();
$voteResults = array();
if ($voteFlags & VOTE_ON_SUBS) {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote, s.title title, a.assign assign
  FROM submissions s, votes v LEFT JOIN assignments a ON a.subId=v.subId AND a.revId=$revId
  WHERE v.voteId=$voteId AND s.subId=v.subId AND vote>0
  ORDER BY v.subId, v.revId";
} else {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote
  FROM votes v WHERE v.voteId=$voteId AND vote>0
  ORDER BY v.subId, v.revId";
}

$res = db_query($qry, $cnnct);
while ($row=mysql_fetch_assoc($res)) {
  if (isset($row['assign']) && $row['assign']==-1) continue;

  $vId   = (int)$row['vId'];
  $rId = (int)$row['revId'];

  $title = ($voteFlags & VOTE_ON_SUBS) ? $row['title'] : $voteTitles[$vId];
  $vItems[$vId] = htmlspecialchars($title);

  if (!isset($voteResults[$vId])) { $voteResults[$vId] = array(); }
  $voteResults[$vId][$rId] = (int)$row['vote'];
}

print "<h2>Detailed Results</h2>\n";
print "<table cellspacing=0 cellpadding=0 border=1><tbody>\n";

$header = "<tr>";
foreach ($voters as $name) { $header .= "  <th>".$name."</th>\n"; }
$header .= "  <th>Num</th>\n";
$header .= "  <th style=\"text-align: left;\">&nbsp;Title</th>\n</tr>\n";

$i = 0;
print $header;
foreach ($vItems as $vId => $title) {
  if ($i>0 && $i%6==0) print $header;

  print "<tr>";
  foreach ($voters as $rId => $name) {
    if (isset($voteResults[$vId][$rId]) && $voteResults[$vId][$rId]>0) {
      if ($voteType=='Grade')
	   print "  <td align=center>".$voteResults[$vId][$rId]."</td>\n";
      else print "  <td align=center>X</td>\n";
    }
    else print "  <td>&nbsp;</td>\n";
  }
  print "  <td style=\"font: bold 12px ariel;\">$vId.</td>\n";
  print "  <td style=\"text-align: left;\">$title</td>\n</tr>\n";
  $i++;
}
if ($i==0) { // no non-zero votes
  $nCols = is_array($voters) ? (count($voters)+2) : 2;
  print "<tr><td align=center colspan=$nCols>No non-zero votes yet</td></tr>\n";
}

print <<<EndMark
</tbody></table>
<hr />
$links
</body>
</html>

EndMark;
?>
