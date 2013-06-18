<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$subArray = array();
$minSubId = null;
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  if (!isset($minSubId)) $minSubId = $row[0];
  $row[1] = htmlspecialchars($row[1]);
  $subArray[] = $row;
  $maxSubId = $row[0];
}
$nSubmissions = count($subArray);
$numHdrIdx=(2+intval(($nSubmissions-1)/6));

$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & ".FLAG_IS_CHAIR.") ORDER BY revId";

$res = pdo_query($qry);
$committee = array();
$minRevId = null;
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  if (!isset($minRevId)) $minRevId = $revId;
  $committee[$revId] = array(trim($row[1]), 0, 0, 0);
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}
$maxRevId = $revId;
$cmteIds = array_keys($committee);

// Get the assignment preferences
$qry = "SELECT revId, subId, pref, compatible, sktchAssgn FROM {$SQLprefix}assignments";
$res = pdo_query($qry);
$prefs = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  list($revId, $subId, $pref, $compatible, $assign) = $row; 
  if (!isset($prefs[$subId]))  $prefs[$subId] = array();

  $prefs[$subId][$revId] = array($pref, $compatible, $assign);
}

// Compute the load for PC members and cover for submissions
foreach ($subArray as $i=>$sub) { 
  $subId = $sub[0];
  foreach ($committee as $revId => $pcm) {
    if (isset($prefs[$subId][$revId])) {
      $prf = $prefs[$subId][$revId][0];
      $assgn=$prefs[$subId][$revId][2];
      if ($assgn==1) {
	$subArray[$i][2]++;
	$committee[$revId][1]++;
	if ($prf>3) $committee[$revId][2]++;
	else if ($prf<3) $committee[$revId][2]--;
      }
      if ($prf>3) $committee[$revId][3]++;
    }
  }
}


// Compute the happiness level of reviewers
$happiness = array();
foreach ($committee as $revId=>$pcm) {
  $avg1 = ($pcm[1]>0) ?  // average pref of assigned submissions
          (((float)$pcm[2]) / $pcm[1]) : NULL;
  $avg2 = ($pcm[3]>0) ?  // average assign of prefrd submissions
          (((float)$pcm[2]) / $pcm[3]) : NULL;
  $happy = NULL;
  if (isset($avg1) && isset($avg2)) {
     $happy = round(max($avg1,$avg2)*100);
     if ($happy<0) $happy=0;
     else if ($happy>100) $happy=100;
  }
  $happiness[$revId] = $happy;
}

/* Display the assignments matrix to the user */
$classes = array('zero', 'one', 'two', 'three', 'four', 'five');
$links = show_chr_links(0,array('assignments.php','Assignments'));
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/saving.css" />
<style type="text/css">
h1 { text-align:center; }
th { font: bold 10px ariel; text-align: center; }
td { font: bold 16px ariel; text-align: center; }
.zero { background: red; }
.one { background: orange; }
.two { background: yellow; }
.three { }
.four { background: lightgreen; }
.five { background: green; }
.darkbg  { background-color: lightgrey; }
.hidden  { display: none; }
.shown   { display: block; }
</style>

<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="assignMatrix.js"></script>
<script type="text/javascript">
<!--
var numHdrIdx=$numHdrIdx;
var minRevId=$minRevId;
var maxRevId=$maxRevId;
var minSubId=$minSubId;
var maxSubId=$maxSubId;
var reviewers = [
EndMark;

$comma = '';
for ($i=0; $i<=$maxRevId; $i++) {
  print $comma;
  if (isset($committee[$i])) {
    $pcm = $committee[$i];
    print "{'load': ".$pcm[1].", 'wanted': ".$pcm[3].", 'match': ".$pcm[2]."}";
  }
  else print "{'load': 0, 'wanted': 0, 'match': 0}";
  $comma = ",\n  ";
}
print <<<EndMark
];
//-->
</script>

<title>Manual Assignments: Matrix Interface</title>
</head>

<body onload="initMatrix();">
$links
<hr/>
<h1>Manual Assignments: Matrix Interface</h1>
<p>
Use the matrix interface below to assign submissions to reviewers.
When submitting this form, the server will only update its "sketch
copy" of the assignments (namely, the copy that is only visible to the
chair and not the reviewers). To make the assignments visible to reviewers,
you need to check also the box next to the submit button.
The matrix interface below shows you the reviewers&prime; preferences, as
well as any preference that you entered: the check-boxes themselves are
colored <span style="color: green;">green</span>  if you indicated a
preference for the PC-member to review the submission or <span
style="color: red;">red</span> if you indicated a preference that the
PC-member do not review the submission.</p>
<blockquote class="jsEnabled hidden">
<i>If Javascript is enabled in your browser, then the various sums are updated
immediately when you check or clear any check-box, and the page will try to
asynchronously update the sketch assignments on the server with your changes
as you make them.</i> (This is a "best effort" approach, however, you will
<b>not be notified</b> of communication errors with the server.)</blockquote>
<p>
Once you are happy with the assignment you should use the button to 'Save
All Assignments' at the bottom of the table to upload the complete assignment
to the server, and this will also reload the page with all the updated
information. You can go back to the <a href="assignments.php">main assignment
page</a> to reset all the assignments and start from scratch, or to upload a
backup copy of the assignments that you stored on your local machine.</p>

<a name="matrix"></a>
<button class="jsEnabled hidden" onclick="location.reload();">Reload Matrix from Server</button>
<form accept-charset="utf-8" id="saveAssignments" action="doAssignments.php" enctype="multipart/form-data" method="post">
<table cellspacing=0 cellpadding=0 border=1><tbody>

EndMark;

foreach ($committee as $revId => $pcm) {
  $name = explode(' ', $pcm[0]);
  if (is_array($name)) for ($j=0; $j<count($name); $j++) { 
    $name[$j] = htmlspecialchars(substr($name[$j], 0, 7)); 
  }
  $cmte[$revId] = implode('<br/>', $name);
}

  $header = "<tr>  <th>Num</th>\n";
$dark = true;
foreach ($cmte as $revId=>$name) {
  $header .= '<th';
  if ($dark) $header .= ' class="darkbg"';
  $dark = !$dark;
  $header .= '>'.$name."</th>\n"; }
$header .= "  <th>Num</th>\n";
$header .= "  <th>&nbsp;Title&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>\n";
$header .= "<th>&nbsp;&nbsp;#&nbsp;&nbsp;</th>\n";
$header .= "</tr>\n";

$hdr2 = "<tr>  <th>#</th>\n";
$dark = true;
foreach ($cmte as $revId=>$name) {
  $hdr2 .= '<th id="hdr'.$revId.'_###"';

  if ($dark) $hdr2 .= ' class="darkbg"';
  $dark = !$dark;

  $innerHTML = $committee[$revId][1];
  $title = 'Assigned: '.$committee[$revId][1];
  if (isset($happiness[$revId])) {
    $innerHTML .= '('.$happiness[$revId].')';
    $title .= 'happy: '.$happiness[$revId].'%';
  }
  $hdr2 .= ' title="'.$title.'">'.$innerHTML."</th>\n";
}
$hdr2 .= "  <th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th>\n";
$hdr2 .= "</tr>\n";

$smilyLine = '<tr><td><a target=documentation href="../documentation/chair.html#happy"><img border=1 src="../common/smile.gif" alt=":)" title="Satisfaction level"></a></td>'."\n";
$dark = true;
foreach ($committee as $revId=>$pcm) {
  $happy = $happiness[$revId];
  if (!isset($happy)) {
      $src = '../common/empty.gif'; $title = '';
  } else if ($happy<50) {
    $src= '../common/angry.gif'; $title= "Angry: $happy%";
  } else if ($happy<65) {
    $src = '../common/sad.gif'; $title= "Sad: $happy%";
  } else if ($happy<80) {
    $src = '../common/ok.gif'; $title= "Satisfied: $happy%";
  } else {
    $src = '../common/laugh.gif'; $title= "Happy: $happy%";
  }
  $happy= "<img border=0 src=\"$src\" id=\"smily{$revId}_###\" alt=\"$happy\" title=\"$title\">";
  $smilyLine .= (($dark?'  <td class="darkbg">':'  <td>'));
  $dark = !$dark;
  $smilyLine .= "$happy</td>\n";
}
$smilyLine .= "  <td colspan=3> &nbsp;</td>\n";
$smilyLine .= "</tr>\n";

print str_replace('_###','_0',$smilyLine). $header. str_replace('_###','_0',$hdr2);
$n = count($committee);
$count = 0; $idx = 1;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  print "<tr><td>{$subId}</td>\n";
  $dark = true;
  $nCompat = $nMatch = 0;
  foreach ($committee as $revId => $pcm) {
    $name = $pcm[0];
    if (isset($prefs[$subId][$revId])) {
      $prf = $prefs[$subId][$revId][0];
      $cmpt= $prefs[$subId][$revId][1];
      $assgn=$prefs[$subId][$revId][2];
    }
    else { $prf = 3; $cmpt = 0; $assgn = 0; }
    if ($dark)
      print '  <td class="darkbg">'.colorNum($subId,$revId,$prf)."<br />";
    else 
      print "  <td>".colorNum($subId,$revId,$prf)."<br />";
    print checkbox($subId, $revId, $name, $cmpt, $assgn,$prf);
    $dark = !$dark;
    print "\n  </td>\n";
  }
  print "  <td>{$sub[0]}</td>\n";
  print "  <td style=\"text-align: left; font: italic 12px ariel;\">\n";
  print "    <a href=\"../review/submission.php?subId={$subId}\">{$sub[1]}</a></td>\n";
  print "  <td id=\"subSum_{$sub[0]}\">{$sub[2]}</td>\n";
  print "</tr>\n";
  if ($count >= 5) {
    print $header . str_replace('_###',"_$idx",$hdr2);
    $idx++;
    $count = 0;
  }
  else $count++;
}

if ($count > 1) print $header . str_replace('_###',"_$idx",$hdr2);
print str_replace('_###','_1',$smilyLine);

print <<<EndMark
</tbody></table>
<a name="saveMatrix"></a>
<input type="hidden" name="saveAssign" value="on">
<input type="submit" value="Save Assignments in Matrix Interface"/>
<input type="checkbox" name="visible" value="on"/>
Make these assignments visible to the reviewers
</form>
<p class="hidden"> Ignore this check-box: there is something wrong with
your browser if you see it.<input type="checkbox" id="recompMatrix"/></p>
<hr/>
$links
</body>
</html>

EndMark;
exit();

function colorNum($subId,$revId,$num)
{
  global $classes;
  if ($num >=0 && $num <=5 && $num != 3)
    return "<span id=\"prf_{$subId}_{$revId}\" class=\"".$classes[$num].'">'.$num.'</span>';
  else return '';
}

function checkbox($subId, $revId, $name, $cmpt, $isChecked, $pref)
{
  global $classes;
  switch ($cmpt) {
  case -1:
    $cls = $classes[0]; break;
  case 1:
    $cls = $classes[4]; break;
  default:
    $cls = $classes[3];
  }

  $ttl = "{$subId}/{$name}";
  switch ($isChecked) {
  case -2:
  case -1:
      return '<span class="'.$cls.'"><img src=../common/xmark.gif title="'.$ttl.' (conflict)" alt="x"></span>';
      // $chk = ' disabled="disabled"'; $ttl .= ' (conflict)'; break;
  case 1:
    $chk = ' checked="checked"'; $ttl .= ' (assigned)'; break;
  default:
    $chk = '';
  }
  $name = "{$subId}_{$revId}";
  return '<span class="'.$cls.'"><input class="assignment" type="checkbox" name="a_'.$name.'" id="chk_'.$name.'" onclick="return updateBox(this);" title="'.$ttl.'"'."$chk/></span>";
}
?>
