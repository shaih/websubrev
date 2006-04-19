<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication=true;
require 'voteParams.php';
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];
$links = show_rev_links();

$cnnct = db_connect();

if ($voteOnSubmissions) {
  $where = $or = "";
  if (isset($voteOnAC)) { $where .= "$or status='Accept'"; $or = " OR"; }
  if (isset($voteOnMA)) { $where .= "$or status='Maybe Accept'"; $or = " OR"; }
  if (isset($voteOnDI)) { $where .= "$or status='Needs Discussion'"; $or = " OR"; }
  if (isset($voteOnNO)) { $where .= "$or status='None'"; $or = " OR"; }
  if (isset($voteOnMR)) { $where .= "$or status='Perhaps Reject'"; $or = " OR"; }
  if (isset($voteOnRE)) { $where .= "$or status='Reject'"; $or = " OR"; }
  if (isset($voteOnThese)) { $where .= "$or s.subId IN ($voteOnThese)"; }
  if (empty($where)) { $where = "s.status!='Withdrawn'"; }

  $qry = "SELECT s.subId, title, vote FROM submissions s
  LEFT JOIN votes v ON v.subId=s.subId AND v.revId=$revId
  WHERE $where
  ORDER by s.subId";

  $res = db_query($qry, $cnnct);

  $voteItems = array();
  while ($row=mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $voteItems[$subId] = array($row[1], $row[2]);
  }
}
else {
  $voteItems = array();
  foreach ($voteTitles as $vId => $title)
    $voteItems[$vId] = array($title, NULL);

  $qry = "SELECT subId, vote FROM votes WHERE revId=$revId ORDER by subId";
  $res = db_query($qry, $cnnct);
  while ($row=mysql_fetch_row($res)) {
    $vId = (int) $row[0];
    $voteItems[$vId][1] = (int) $row[1];
  }
}

// If user has cast a vote - record it
if (isset($_POST["votes"]) && is_array($_POST["votes"])) {
  $voteSum = array_sum($_POST["votes"]);
  if (isset($voteBudget) && $voteBudget > 0 && $voteSum > $voteBudget) {
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
	$qry = "INSERT INTO votes SET subId={$itemId}, revId={$revId}, "
	  . "vote=$theVote";
      } else {                // modify existing entry
	$qry = "UPDATE votes SET vote=$theVote" 
	  . " WHERE subId={$itemId} AND revId={$revId}";
      }
      db_query($qry, $cnnct);
      $voteItems[$itemId][1] = $theVote;
    }
  }
  $voteRecorded = "Your vote was recorded.";
}
else $voteRecorded = "";


if ($voteType=='Grade') {  // reviewrs grade the submissions
  $voteTitle = "";
  for ($i=0; $i<=$voteMaxGrade; $i++) { $voteTitle .= "<th>$i</th>"; }
} else {                  // reviewrs just choose submissions
  $voteTitle = "<th>Choose</th>";
}
$voteTitle .= "<th>Num</th><th style=\"text-align: left;\"> &nbsp;Title</th>";

if (!empty($voteDeadline)) {
  $voteDeadline = "You need to submit your vote by "
    . htmlspecialchars($voteDeadline) . ".<br /><br />";
}
if (!empty($voteInstructions)) {
  $voteInstructions = "<b>Instructions:</b><pre>"
    . htmlspecialchars(wordwrap($voteInstructions)) . "</pre>";
}

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<style type="text/css">
h1, td { text-align: center; }
tr { vertical-align: top; }
</style>
<title>Voting Page</title>
</head>
<body>
$links
<hr />
$voteRecorded

<h1>Voting Page</h1> 
$voteDeadline
$voteInstructions

<form action="vote.php"  enctype="multipart/form-data" method=post>
<table><tbody>
<tr>
  $voteTitle
</tr>

EndMark;

foreach ($voteItems as $itemId => $vItem) {
  $title = htmlspecialchars($vItem[0]);
  if ($voteOnSubmissions)
    $title = '<a href="submission.php?subId='.$itemId.'">'.$title.'</a>';

  print "<tr>\n";
  if ($voteType=='Grade') {  // reviewr grades the submissions
    for ($i=0; $i<=$voteMaxGrade; $i++) {
      $chk = (isset($vItem[1]) && $vItem[1]==$i) ? 'checked="checked"' : '';
      print "  <td><input type=\"radio\" name=\"votes[$itemId]\" value={$i}"
	. " $chk title=\"$i\"></td>\n";
    }
  } else {                  // reviewr just chooses submissions
    $chk = $vItem[1] ? 'checked="checked"' : '';
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
<hr />
$links
</body>
</html>

EndMark;
?>