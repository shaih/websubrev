<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true;
require 'header.php';

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
  if (!isset($subRevs[$subId])) 
    $subRevs[$subId] = array();
  $subRevs[$subId][$revId] = array(isset($whenEntered), $assign);
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

// Make user-indicated changes before displaying the matrix
if (isset($_POST["setDiscussFlags"])) foreach ($committee as $j => $pcm) {
  $revId = (int) $pcm[0];
  if (isset($_POST["dscs"][$revId])) {
    $qry = "UPDATE committee SET canDiscuss=1 WHERE revId={$revId}";
    $committee[$j][5] = 1;
  } else {
    $qry = "UPDATE committee SET canDiscuss=0 WHERE revId={$revId}";
    $committee[$j][5] = 0;
  }
  db_query($qry, $cnnct);
}

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
<img src="../common/check3.GIF" alt="(+)" height=20> Assigned,&nbsp;&nbsp; 
<img src="../common/check2.GIF" alt="(+)" height=20> Reviewed,&nbsp;&nbsp; 
<img src="../common/check1.GIF" alt="(+)" height=20> Assigned and reviewed,&nbsp;&nbsp;
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
    $img = '&nbsp;';
    if (isset($subRevs[$subId][$revId])) {
      $assgn = $subRevs[$subId][$revId][1];
      if ($subRevs[$subId][$revId][0]) { // review entered
	$nRevs++;
	if ($assgn==1) {       // assigned and reviewed 
	  $img = '../common/check1.GIF';
	  $committee[$j][2]++;
	  $committee[$j][3]++;
	} else {               // reviewed but not assigned
	  $img = '../common/check2.GIF';
	  $committee[$j][4]++;
	}
	$img = "<a href=\"../review/receiptReport.php?subId={$subId}&amp;revId={$revId}\" target=\"_blank\">\n"
	  . '      <img src="'.$img.'" alt="(+)" height=20 border=0></a>';
      }
      else if ($assgn==1) {    // assigned but not reviewed
	$img = "<img src=\"../common/check3.GIF\" alt=\"(-)\" height=20>";
	$committee[$j][2]++;
      } else if ($assgn==-1)   // conflict
	$img = "<img src=\"../common/stop.GIF\" alt=\"(X)\" height=18>";
    }
    print "  <td>$img</td>\n";
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
<form action="overview.php" enctype="multipart/form-data" method="post">
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
