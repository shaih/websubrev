<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$voteId = intval($_GET['voteId']);
if ($voteId <= 0) die("<h1>Vote-ID must be specified</h1>");

$res = pdo_query("SELECT * FROM {$SQLprefix}votePrms WHERE voteId=?", array($voteId));
$row = $res->fetch();

$active = (isset($row['voteActive'])&& $row['voteActive']>0) ? 'in progress' : 'closed';
$voteType  = isset($row['voteType'])  ? htmlspecialchars($row['voteType']) : 'Choose';
$voteTitle = isset($row['voteTitle']) ? htmlspecialchars($row['voteTitle']):'';
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteBudget= isset($row['voteBudget'])? intval($row['voteBudget']): 0;
$voteOnThese = isset($row['voteOnThese'])? $row['voteOnThese'] : '';
$voteMaxGrade= isset($row['voteMaxGrade'])? intval($row['voteMaxGrade']): 1;
if (empty($voteTitle)) $voteTitle = "Ballot #$voteId";
if ($voteFlags & VOTE_ON_SUBS)
     $voteTitles = NULL;
else $voteTitles = explode(';', ';'.$voteOnThese);
// the above puts an empty sting at index 0 to make the array start at index 1

// Get the names of PC membes that voted in the current ballot
$voters = array();
$qry = "SELECT c.revId revId, c.name name, COUNT(v.vote) dummy
  FROM {$SQLprefix}committee c, {$SQLprefix}votes v
  WHERE v.voteId=? AND c.revId=v.revId GROUP BY revId ORDER BY revId";
$res = pdo_query($qry, array($voteId));
while ($row=$res->fetch(PDO::FETCH_NUM)) {
  $revId = (int)$row[0];
  $name = explode(' ', $row[1]);
  if (is_array($name)) {
    for ($j=0; $j<count($name); $j++)
      $name[$j] = htmlspecialchars(substr($name[$j], 0, 7)); 
    $voters[$revId] = implode('<br/>', $name);
  }
}

// Get the vote results
$vItems = array();
$voteResults = array();
if ($voteFlags & VOTE_ON_SUBS) {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote, s.title title
  FROM {$SQLprefix}votes v, {$SQLprefix}submissions s
  WHERE v.voteId=? AND s.subId=v.subId AND vote>0
  ORDER BY v.subId, v.revId";
} else {
  $qry = "SELECT v.subId vId, v.revId revId, v.vote vote
  FROM {$SQLprefix}votes v WHERE v.voteId=? AND vote>0
  ORDER BY v.subId, v.revId";
}
$res = pdo_query($qry, array($voteId));
while ($row=$res->fetch(PDO::FETCH_ASSOC)) {
  $vId   = (int)$row['vId'];
  $revId = (int)$row['revId'];

  $title = ($voteFlags & VOTE_ON_SUBS) ? $row['title'] : $voteTitles[$vId];
  $vItems[$vId] = htmlspecialchars($title);

  if (!isset($voteResults[$vId])) { $voteResults[$vId] = array(); }
  $voteResults[$vId][$revId] = (int)$row['vote'];
}
//if (isset($voters) && is_array($voters))
//  ksort($voters);

/* Show a matrix with all the votes in it 
 *******************************************************************/
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center;}
th { font: bold 10px ariel; text-align: center; }
</style>
<title>Results: $voteTitle ($active)</title>
</head>
<body>
$links
<hr/>
<h1>Results: $voteTitle ($active)</h1>

EndMark;

// Print the voting tally for this vote
summaryResults($voteId,($voteFlags & VOTE_ON_SUBS), $voteTitles);

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
  foreach ($voters as $revId => $name) {
    if (isset($voteResults[$vId][$revId]) && $voteResults[$vId][$revId]>0) {
      if ($voteType=='Grade')
	   print "  <td align=center>".$voteResults[$vId][$revId]."</td>\n";
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


function summaryResults($voteId, $voteOnSubmissions, $voteTitles)
{
  global $SQLprefix;
  $noVotes = true;

  if ($voteOnSubmissions) {
    $qry = "SELECT s.subId, SUM(v.vote) sum, title FROM {$SQLprefix}submissions s, {$SQLprefix}votes v WHERE v.voteId=? AND s.subId=v.subId GROUP BY s.subId ORDER BY sum DESC, s.subId ASC";

    $res = pdo_query($qry, array($voteId));

    $voteItems = array();
    while ($row=$res->fetch(PDO::FETCH_NUM)) {
      if ($row[1] < 0) continue;
      $noVotes = false;
      $voteItems[] = $row;
    }
  } else {
    $qry = "SELECT subId, SUM(vote) sum FROM {$SQLprefix}votes WHERE voteId=? GROUP BY subId ORDER BY sum DESC, subId ASC";
    $res = pdo_query($qry, array($voteId));

    $voteItems = array();
    while ($row=$res->fetch(PDO::FETCH_NUM)) {
      if ($row[1] < 0) continue;
      $noVotes = false;

      $vId = (int) $row[0];
      $voteItems[] = array($vId, $row[1], $voteTitles[$vId]);
    }
  }

  if ($noVotes) {
    die("<h2>No Results Recorded</h2></body></html>\n");
  }
  else {
    print "<h2>Tally</h2>\n";

    $html = '';
    foreach ($voteItems as $vItem) {
      $itemId = (int) $vItem[0];
      $tally  = (int) $vItem[1];
      $title  = isset($vItem[2]) ? htmlspecialchars($vItem[2]) : '';
      if ($tally==0) continue;
      if ($voteOnSubmissions)
	$title = '<a href="../review/submission.php?subId='.$itemId.'">'
	  . $title . '</a>';

      $html .= "<tr><td align=center>". $tally ."</td>";
      $html .= "<td>$itemId.</td><td>$title</td>\n";
      $html .= "</tr>\n";
    }

    if (!empty($html)) {
      print "<table><tbody>\n<tr>\n  <th>Tally</th><th>Num</th>"
	."<th style=\"text-align: left;\">&nbsp; Title</th>\n</tr>\n";
      print $html;
      print "</tbody></table>\n";
      print "All other items (if any) got zero votes.<br/><br/>\n";
    }
    else print "No non-zero votes yet.<br/><br/>\n";
  }
}
?>
