<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
$preReview=true;      // page is available also before the review peiod

require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];
$disFlag= (int) $pcMember[3];

$links = show_rev_links();

// Get the parameters of the current active vote.

$voteId = intval($_GET['voteId']);
if ($voteId <= 0) die("<h1>Vote-ID must be specified</h1>");

$cnnct = db_connect();
$qry = "SELECT * from votePrms WHERE voteId=$voteId AND voteActive=1";

// Before the discussion phase, cannot vote on submissions
if (!$disFlag) $qry .= " AND (voteFlags&1)!=1";

$res = db_query($qry,$cnnct);
$row = mysql_fetch_array($res)
     or die("<h1>No Active vote with Vote-ID $voteId</h1>");

$voteType  = isset($row['voteType'])
     ? htmlspecialchars($row['voteType']) : 'Choose';
$voteTtl = isset($row['voteTitle']) ? htmlspecialchars($row['voteTitle'])
                                    : 'Ballot #'.$voteId;
$voteFlags = isset($row['voteFlags']) ? intval($row['voteFlags']) : 0;
$voteBudget= isset($row['voteBudget'])? intval($row['voteBudget']): 0;
$voteOnThese = isset($row['voteOnThese'])? $row['voteOnThese'] : '';
$voteMaxGrade= isset($row['voteMaxGrade'])? intval($row['voteMaxGrade']): 1;
$vInstructions = isset($row['instructions']) ? htmlspecialchars($row['instructions']) : '';
if (!empty($vInstructions)) {
  $vInstructions = "<b>Instructions:</b> ".nl2br($vInstructions);
}
$vDeadline = isset($row['deadline']) ? htmlspecialchars($row['deadline']) : '';
if (!empty($vDeadline)) {
  $vDeadline = "You need to submit your vote by <b>".$vDeadline."</b>.";
}

// Get the items to vote on (with the current votes of this reviewer if any)
if ($voteFlags & VOTE_ON_SUBS) { // voting on submissions
  if ($voteFlags & VOTE_ON_ALL)
    $where = "s.status!='Withdrawn'";
  else 
    $where = "false";

  if ($voteFlags & VOTE_ON_RE) {
    $where .= " OR status='Reject'"; }
  if ($voteFlags & VOTE_ON_MR) {
    $where .= " OR status='Perhaps Reject'"; }
  if ($voteFlags & VOTE_ON_NO) {
    $where .= " OR status='None'"; }
  if ($voteFlags & VOTE_ON_DI) {
    $where .= " OR status='Needs Discussion'"; }
  if ($voteFlags & VOTE_ON_MA) {
    $where .= " OR status='Maybe Accept'"; }
  if ($voteFlags & VOTE_ON_AC) {
    $where .= " OR status='Accept'"; }
  if (!empty($voteOnThese)) {
    $where .= " OR s.subId IN (".numberlist($voteOnThese).")"; }

  $qry = "SELECT s.subId, title, vote FROM submissions s
  LEFT JOIN votes v ON v.voteId=$voteId AND v.revId=$revId AND v.subId=s.subId
  WHERE $where
  ORDER by s.subId";

  $res = db_query($qry, $cnnct);

  $voteItems = array();
  while ($row=mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $voteItems[$subId] = array($row[1], $row[2]);
  }
}
else {                           // voting on "other things"
  $voteItems = array();
  $voteTitles = explode(';', $voteOnThese);
  foreach ($voteTitles as $title) {
    $title = trim($title);
    if (!empty($title)) 
    $voteItems[] = array($title, NULL);
  }

  $qry = "SELECT subId, vote FROM votes WHERE voteId=$voteId AND revId=$revId ORDER by subId";
  $res = db_query($qry, $cnnct);
  while ($row=mysql_fetch_row($res)) {
    $itemId = (int) $row[0];
    $voteItems[$itemId][1] = (int) $row[1];
  }
}

// If user has cast a vote - record it
if (isset($_POST["votes"]) && is_array($_POST["votes"])) {
  $voteSum = array_sum($_POST["votes"]);
  if ($voteBudget > 0 && $voteSum > $voteBudget) {
    if ($voteType=='Grade')
      exit ("<h1>Sum of all gardes cannot exceed $voteBudget</h1>");
    else
      exit ("<h1>Cannot choose more than $voteBudget items</h1>");
  }
  
  foreach($voteItems as $itemId => $vItem) {
    $itemId = (int) $itemId;
    $theVote = isset($_POST["votes"][$itemId]) ? intval($_POST["votes"][$itemId]) : 0;
    if ($theVote<0) $theVote = 0;
    else if ($theVote>$voteMaxGrade) $theVote = $voteMaxGrade;

    if ($theVote!=$vItem[1]) {
      if (!isset($vItem[1])) { // insert a new entry
	$qry = "INSERT INTO votes SET voteId=$voteId, revId=$revId, subId=$itemId, vote=$theVote";
      } else {                // modify existing entry
	$qry = "UPDATE votes SET vote=$theVote WHERE voteId=$voteId AND revId=$revId AND subId=$itemId";
      }
      db_query($qry, $cnnct);
      $voteItems[$itemId][1] = $theVote;
    }
  }
  $voteRecorded = "Your vote was recorded.";
}
else $voteRecorded = "";


if ($voteType=='Grade') {  // reviewrs grade the submissions
  $voteHdr = "";
  for ($i=0; $i<=$voteMaxGrade; $i++) { $voteHdr .= "<th>$i</th>"; }
} else {                  // reviewrs just choose submissions
  $voteHdr = "<th>Choose</th>";
}
$voteHdr .= "<th>Num</th><th style=\"text-align: left;\"> &nbsp;Title</th>";

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<style type="text/css">
h1, td { text-align: center; }
tr { vertical-align: top; }
</style>
<title>Voting Page: $voteTtl</title>
</head>
<body>
$links
<hr />
$voteRecorded

<h1>Voting Page: $voteTtl</h1> 
$vInstructions<br/>
<br/>
$vDeadline<br/>
<br/>
<form action="vote.php?voteId=$voteId" enctype="multipart/form-data" method=post>
<input type=reset>
<table><tbody>
<tr>
  $voteHdr
</tr>

EndMark;

foreach ($voteItems as $itemId => $vItem) {
  $title = htmlspecialchars($vItem[0]);
  if ($voteFlags & VOTE_ON_SUBS)
    $title = '<a href="submission.php?subId='.$itemId.'">'.$title.'</a>';

  print "<tr>\n";
  if ($voteType=='Grade') {  // reviewr grades the submissions
    for ($i=0; $i<=$voteMaxGrade; $i++) {
      $chk = (isset($vItem[1]) && $vItem[1]==$i) ? 'checked="checked"' : '';
      print "  <td><input type=\"radio\" name=\"votes[$itemId]\" value={$i}"
	. " $chk title=\"$i\"></td>\n";
    }
  } else {                  // reviewr just chooses submissions
    $chk = (isset($vItem[1]) && $vItem[1]>0) ? 'checked="checked"' : '';
    print "  <td><input type=\"checkbox\" name=\"votes[$itemId]\" value=1 $chk>"
      . "</td>\n";
  }
  print "  <td>$itemId.</td><td style=\"text-align: left;\">$title</td>\n";
  print "</tr>\n";
}
print <<<EndMark
</tbody></table>
<input type="submit" value="Submit Vote">
</form>
Vote early, vote often: if you change your mind you can return to this page
anytime before the vote closes to change your vote. PC members can't see each
other's vote, but the chair sees a complete picture of who voted for what.
<hr />
$links
</body>
</html>

EndMark;
?>
