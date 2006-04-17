<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

/* Show the page to the chair asking for parameters
 *******************************************************************/
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 { text-align: center;}
tr { vertical-align: top; }
</style>
<title>Setup/Manage Voting</title>
</head>
<body>
$links
<hr />
<h1>Setup/Manage Voting</h1>

EndMark;

// Is there a vote in progress?
clearstatcache();
if (file_exists("./review/voteParams.php")) { 
  include "./review/voteParams.php";
  print_vote_results(true, $voteOnSubmissions, $voteTitles);
}
else if (file_exists("./review/voteParams.bak.php")) {
  if (isset($_GET["oldVoteResults"])) {
    include "./review/voteParams.bak.php";
    print_vote_results(false, $voteOnSubmissions, $voteTitles);
    exit("<hr />\n".$links."\n</body></html>\n");
  } else {
    print "<a href=\"voting.php?oldVoteResults=on\">Click here</a> to see results from last vote.\n";
  }
}

$voteInstructions = isset($voteInstructions) ? htmlspecialchars($voteInstructions) : '';
$voteDeadline = isset($voteDeadline) ? htmlspecialchars($voteDeadline) : '';

print <<<EndMark
<h2>Vote Parameters</h2>
There are two types of votes that you can set-up:
<ul>
<li>One is simple "Choose vote"
in which you specify a list of submissions and every PC member needs to
choose some number of them. For example, "Choose up to three of the
submissions in 'Maybe Reject' category to move back to the 'Discuss' pile."
<br /><br /></li>
<li>The other type is a "Grade vote" in which you specify a list of submissions
and every PC member needs to grade these submissions on some scale. For
example, "Grade each of the remaining submission in the 'Discuss' category
on a scale of zero to three."
(Technically, a "Choose vote" is a special case of a "Grade vote" with the
scale being 0-1, but the PC-member user-interface for a "Choose vote" is
slightly simpler than for a "Grade vote".)
</li>
</ul>
In either type of vote, the result that you will see is the sum of votes 
that each submissions received. (In the "Choose vote" this is the number 
of PC members that chose that submission.)<br /><br />

<form action="act-voting.php" enctype="multipart/form-data" method=post>
<h3>Instructions for PC members</h3>
<textarea name="voteInstructions" rows=7 cols=80>$voteInstructions</textarea>
<br />
Vote deadline:
<input type="text" name="voteDeadline" size=90 value="$voteDeadline">

EndMark;

$chooseVote = (isset($voteType) && $voteType=='Choose') ? 'checked="checked"' : '';
$gradeVote = (isset($voteType) && $voteType=='Grade') ? 'checked="checked"' : '';
if (!isset($voteMaxGrade)) $voteMaxGrade='';
if (!isset($voteBudget)) $voteBudget='';

print <<<EndMark
<h3>Vote type</h3>
<input type="radio" name="voteType" value="Choose" $chooseVote>
A simple "Choose vote"
<br />
<input type="radio" name="voteType" value="Grade" $gradeVote>
A "Grade vote" on a scale of 0 to
<input type="text" name="voteMaxGrade" size=1 value=$voteMaxGrade>
(max-garde cannot be more than 9).<br />
<br />
Every PC member has "voting budget" of 
<input type="text" name="voteBudget" size=1 value=$voteBudget> (leave empty
for unlimited budget). For a "Choose vote", the budget is the number of
submissions that the PC member can choose. For a "Grade vote", it
is the sum of all grades that this PC member can assign.

EndMark;

if (isset($voteOnSubmissions)) {
  $vZero = ($voteOnSubmissions===0) ? 'checked="checked"' : '';
  $vOne = ($voteOnSubmissions==1) ? 'checked="checked"' : '';
  $vTwo = (!isset($voteOnSubmissions) || $voteOnSubmissions==2) ? 'checked="checked"' : '';
} else { 
  $vZero = $vOne = $vTwo = '';
}
if (isset($voteTitles) && is_array($voteTitles)) {
  $smiecolon = $voteItemsString = "";
  foreach ($voteTitles as $item) {
    $voteItemsString .= $smiecolon . $item; $smiecolon = "; ";
  }
}
else $voteItemsString = '';

$chkAC = isset($voteOnAC) ? 'checked="checked"' : '';
$chkMA = isset($voteOnMA) ? 'checked="checked"' : '';
$chkDI = isset($voteOnDI) ? 'checked="checked"' : '';
$chkNO = isset($voteOnNO) ? 'checked="checked"' : '';
$chkMR = isset($voteOnMR) ? 'checked="checked"' : '';
$chkRE = isset($voteOnRE) ? 'checked="checked"' : '';

print <<<EndMark
<h3>What is included in this vote?</h3>
<input type="radio" name="voteOnSubmissions" value=1 $vOne>
Include all submissions.
<br />
<input type="radio" name="voteOnSubmissions" value=2 $vTwo>
Include only the submissions that are specified below
(<a href="../documentation/chair.html#votes">more info</a>):<br/>
<table><tbody>
<tr><td></td>
    <td style="text-align: right;">&nbsp; &nbsp;submissions in the:</td>
    <td><input type="checkbox" name="voteOnAC" $chkAC>'Accept' category,</td>
    <td><input type="checkbox" name="voteOnMA" $chkMA>'Maybe Accept' category,</td>
</tr>
<tr><td></td><td></td>
    <td><input type="checkbox" name="voteOnDI" $chkDI>'Discuss' category,</td>
    <td><input type="checkbox" name="voteOnNO" $chkNO>'None' category,</td>
</tr>
<tr><td></td><td></td>
    <td><input type="checkbox" name="voteOnMR" $chkMR>'Maybe Reject' category,</td>
    <td><input type="checkbox" name="voteOnRE" $chkRE>'Reject' category</td>
</tr>
<tr><td> &nbsp; &nbsp; </td>
    <td style="text-align: right;">..and also these submission IDs:</td>
    <td colspan=2><input type="text" name="voteOnThese" size=80><br />
                  (comma-separated list of submission-IDs)</td>
</tr>
</tbody></table>
<br />
<input type="radio" name="voteOnSubmissions" value=0 $vZero>
Vote on things other than submissions (e.g., invited speaker):
<textarea name="voteItems" rows=5 cols=80>$voteItemsString</textarea><br />
A <b>semi-colon separated</b> list of items to vote on. For example, to let
the PC members choose their main course for the PC dinner, you can use a line
such as <tt>"Maine Lobster; Australian barramundi; Squab breast; Medallions of
Millbrook venison; Lamb rack 'au sautoir'"</tt>.
<br/><br/>
<input type="hidden" name="voteParams" value="set">
<input type="submit" value="Set/Change Vote Parameters"><br /><br />
<input type="checkbox" name="voteReset" checked="checked">Reset vote results
(This will erase all recorded votes and start a new vote from scratch. You
should only uncheck this if you want to modify the vote parameters while the
 vote is in progress.)

</form>
EndMark;

print <<<EndMark
<hr />
$links
</body>
</html>

EndMark;

function print_vote_results($inProgress, $voteOnSubmissions, $voteTitles)
{
  $cnnct = db_connect();
  $noVotes = true;

  if ($voteOnSubmissions) {
    $qry = "SELECT s.subId, SUM(v.vote) sum, title
    FROM submissions s INNER JOIN votes v USING(subId)
    GROUP BY s.subId ORDER BY sum DESC, s.subId ASC";
    $res = db_query($qry, $cnnct);

    $voteItems = array();
    while ($row=mysql_fetch_row($res)) {
      if ($row[1] == 0) continue;
      else $noVotes = false;
      $voteItems[] = $row;
    }
  } else {
    $qry = "SELECT subId, SUM(vote) sum
    FROM votes GROUP BY subId ORDER BY sum DESC, subId ASC";
    $res = db_query($qry, $cnnct);

    $voteItems = array();
    while ($row=mysql_fetch_row($res)) {
      if ($row[1] == 0) continue;
      else $noVotes = false;

      $vId = (int) $row[0];
      $voteItems[] = array($vId, $row[1], $voteTitles[$vId]);
    }
  }

  if ($noVotes) {
    print $inProgress ?
      "<h2>Vote in Progress, No Results Yet</h2>\n" :
      "<h2>No Vote in Progress, No Results Recorded</h2>\n" ;
  }
  else {
    if ($inProgress) print "<h2>Vote in Progress, Current Results:</h2>\n";
    else             print "<h2>Results From Last Vote</h2>\n";

    print "<table><tbody>\n<tr>
  <th>Tally</th><th>Num</th><th style=\"text-align: left;\">&nbsp; Title</th>\n</tr>\n";

    foreach ($voteItems as $vItem) {
      $itemId = (int) $vItem[0];
      $tally  = (int) $vItem[1];
      $title  = isset($vItem[2]) ? htmlspecialchars($vItem[2]) : '';
      if ($tally==0) continue;
      if ($voteOnSubmissions)
	$title = '<a href="../review/submission.php?subId='.$itemId.'">'
	  . $title . '</a>';

      print "<tr><td style=\"text-align: center;\">". $tally ."</td>";
      print "<td style=\"text-align: center;\">$itemId.</td><td>$title</td>\n";
      print "</tr>\n";
    }
    print "</tbody></table>\n";
    print "All other items (if any) got zero votes. <a href=\"voteDetails.php\">See detailed results.</a><br/><br/>\n";
  }
  if ($inProgress) { print <<<EndMark
<form action="act-voting.php" enctype="multipart/form-data" method=post>
<input type="hidden" name="voteClose" value="yes">
<input type="submit" value="Close this vote">
</form>

EndMark;
  }
}
?>