<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true;
require 'header.php';

// a basic "node" class to be able to do Depth-First-Search
class matrixEntry {
  var $assigned = 0; // -1 is conflict, +1 is assigned, 0 is neither
  var $reviewed = false;
  var $posts    = false;
}

if (defined('CAMERA_PERIOD')) exit("<h1>Review Site is Closed</h1>");

// Get assignments and reviews
$cnnct = db_connect();
$qry = "SELECT s.subId, c.revId, r.whenEntered, a.assign
  FROM submissions s CROSS JOIN committee c
    LEFT JOIN reports r ON r.subId=s.subId AND r.revId=c.revId
    LEFT JOIN assignments a ON a.revId=c.revId AND a.subId=s.subId
  WHERE s.status!='Withdrawn'
  ORDER BY s.subId, c.revId";
$res = db_query($qry, $cnnct);
$subRevs = array();
while ($row = mysql_fetch_row($res)) { 
  list($subId, $revId, $whenEntered, $assign) = $row; 
  if (!isset($subRevs[$subId])) $subRevs[$subId] = array();
  $subRevs[$subId][$revId] = new matrixEntry();
  $subRevs[$subId][$revId]->assigned = (isset($assign) ? $assign : 0);
  $subRevs[$subId][$revId]->reviewed = isset($whenEntered);
}

$qry = "SELECT subId,revId,COUNT(postId) FROM posts GROUP BY subId,revId";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) {
  list($subId, $revId, $nPosts) = $row; 
  if ($nPosts==0) continue; // no posts
  if (!isset($subRevs[$subId])) $subRevs[$subId] = array();
  $subRevs[$subId][$revId]->posts = true;
}

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, status from submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
$statuses = array('Accept'         => 0,
		  'Maybe Accept'   => 0,
		  'Needs Discussion'=> 0,
		  'None'           => 0,
		  'Perhaps Reject' => 0,
		  'Reject'         => 0);
while ($row = mysql_fetch_row($res)) {
  $subArray[] = $row;
  $stts = $row[2];
  $statuses[$stts]++;
}

$qry = "SELECT revId, name, 0, 0, 0, canDiscuss from committee ORDER BY revId";
$res = db_query($qry, $cnnct);
$committee = array();
while ($row = mysql_fetch_row($res)) { $committee[] = $row; }

/*********************************************************************/
/******* Now we can display the assignments matrix to the user *******/
/*********************************************************************/

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
h1 { text-align:center; }
th { font: bold 10px ariel; text-align: center; }
td { text-align: center; }
</style>

<title>Overview of Submissions and Reviews</title>
</head>

<body>
$links
<hr />
<h1>Overview of Submissions and Reviews</h1>

<!-- <h2>Submission Status Summary</h2> -->
<center>

EndMark;

print status_summary($statuses);

print <<<EndMark
At the bottom of this page you can <a href="#progress">set the discuss flag</a>
for PC members.
</center>
<br/>
<!-- <h2>Review Details</h2> -->
<span style="vertical-align: bottom;">
<b>Legend:</b> &nbsp;&nbsp;
<img src="../common/check4.GIF" alt="(+)" height=20> Assigned,&nbsp;&nbsp; 
<img src="../common/check2.GIF" alt="(+)" height=20> Reviewed,&nbsp;&nbsp; 
<img src="../common/check1.GIF" alt="(+)" height=22> Participates in discussion,&nbsp;&nbsp;
<img src="../common/stop.GIF" alt="(+)" height=16> Conflict
</span>
<br /><br />
<table cellspacing=0 cellpadding=0 border=1><tbody>

EndMark;

$cmte = array();
foreach ($committee as $pcm) {
  $name = explode(' ', $pcm[1]);
  if (is_array($name)) for ($j=0; $j<count($name); $j++) { 
    $name[$j] = substr($name[$j], 0, 7); 
  }
  $cmte[] = array($pcm[0], implode('<br />', $name));
}

$header = "<tr>  <th><big>Num</big></th>\n";
foreach ($cmte as $pcm) { $header .= "  <th>".$pcm[1]."</th>\n"; }
$header .= "  <th><big>Num</big></th>\n";
$header .= "  <th><big>Title&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</big></th>\n";
$header .= "  <th><big>#revs</big></th>\n";
$header .= "</tr>\n";
print $header;

$n = count($committee);
$count = 0;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  $nRevs = 0;
  print "<tr><td>{$subId}</td>\n";
  foreach ($committee as $j => $pcm) {
    $revId = $pcm[0];
    $entry = '&nbsp;';
    if (isset($subRevs[$subId][$revId])) {
      $assgn = $subRevs[$subId][$revId]->assigned;
      $rvewd = $subRevs[$subId][$revId]->reviewed;
      $posts = $subRevs[$subId][$revId]->posts;
      if ($assgn==-1)   // conflict
	$entry = '<img src="../common/stop.GIF" alt="(X)" height=18>';
      else {
	// Compute the symbol to display (check1.GIF through check7.GIF)
	$chkSign = 0;
	if ($assgn) $chkSign += 4;
	if ($rvewd) $chkSign += 2;
	if ($posts) $chkSign += 1;

	// Set the alternative text
	if ($rvewd || $posts) $alt = '(+)';
	else if ($assgn) $alt = '(-)';

	// Set the link to follow when clicking on the check symbol
	if ($posts) 
	  $lnk="../review/discuss.php?subId={$subId}#start";
	else if ($rvewd)
	  $lnk="../review/receiptReport.php?subId={$subId}&amp;revId={$revId}";
	else $lnk = NULL;

	// Set the entry HTML
	if ($chkSign) {
	  $entry = '<img src="../common/check'.$chkSign.'.GIF" alt="'.$alt
	    .'" height=20 border=0>';
	  if (isset($lnk))
	    $entry = '<a href="'.$lnk.'" target=_blank>'.$entry.'</a>';
	}
      }

      // Update the statistics
      if ($assgn==1) $committee[$j][2]++;   // PCM assigned to this submission
	

      if ($rvewd) { // review entered
	$nRevs++;
	if ($assgn==1) $committee[$j][3]++; // assigned and reviewed 
	else           $committee[$j][4]++; // reviewed but not assigned
      }
    }
    print "  <td>$entry</td>\n";
  }
  print "  <td>{$subId}</td>\n";
  print "  <td style=\"text-align: left; font: italic 14px ariel;\">\n";
  print "    <a href=\"../review/submission.php?subId={$subId}\">{$sub[1]}</a></td>\n";
  print "  <td><center>{$nRevs}</center></td>\n";
  print "</tr>\n";
  if ($count >= 6) {
    print $header;
    $count = 0;
  }
  else $count++;
}


print <<<EndMark
</tbody></table>
<br /><br />
<h2><a name="progress">Program Committee Progress Summary</a></h2>
<form action=setDiscussFlags.php enctype="multipart/form-data" method=post>
<table><tbody>
<tr style="font-weight: bold;"><td>Reviewer</td>
  <td>Can discuss &nbsp; </td>
  <td>Reviewed/Assigned &nbsp; </td><td>+Extra</td></tr>

EndMark;

foreach ($committee as $pcm) {
  $chk = $pcm[5] ? 'checked="checked"' : '';
  print <<<EndMark
<tr><td style="text-align: left;">{$pcm[1]}</td>
  <td><center><input type="checkbox" name="dscs[{$pcm[0]}]" $chk></center></td>
  <td>{$pcm[3]} of {$pcm[2]} </td>
  <td>+ {$pcm[4]}</td>
</tr>

EndMark;
}

print <<<EndMark
</tbody></table>
<input type="hidden" name="setDiscussFlags" value="on">
<input type="submit" value="Set Flags">
</form>
<br /><br />
<hr />
$links
</body>
</html>

EndMark;
?>
