<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag = (int) $pcMember[3];

if (defined('CAMERA_PERIOD'))
   exit("<h1>Site closed: cannot post new reviews</h1>");

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");


// Make sure that this submission exists and the reviewer does not have
// a conflict with it, and get the review for it (if exists)
$cnnct = db_connect();

$auxGrades = '';
if (is_array($criteria)) for ($i=0; $i<count($criteria); $i++) {
  $auxGrades .= "r.grade_$i grade_$i, ";
}

$qry= "SELECT s.title ttl, a.assign assign, r.subReviewer subRev,
      r.lastModified lastModif, r.confidence conf, r.grade grade,
      $auxGrades
      r.comments2authors cmnts2athr, r.comments2committee cmnts2PC,
      r.comments2chair cmnts2chair, a.watch watch
      FROM submissions s
        LEFT JOIN assignments a ON a.revId='$revId' AND a.subId='$subId'
        LEFT JOIN reports r     ON r.revId='$revId' AND r.subId='$subId'
    WHERE s.subId='$subId'";

$res = db_query($qry, $cnnct);
if (!($row = mysql_fetch_assoc($res)) || $row['assign']==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$title      = isset($row['ttl']) ? htmlspecialchars($row['ttl']) : '';
$subRev     = isset($row['subRev']) ? htmlspecialchars($row['subRev']) : '';

$conf  = (int) $row['conf'];  if ($conf<1 || $conf>3)           $conf=NULL;
$grade = (int) $row['grade']; if ($grade<1 || $grade>MAX_GRADE) $grade=NULL;

$mxGrades = MAX_GRADE;
$auxGrades = array();
if (is_array($criteria) && count($criteria)>0) {
  for ($i=0; $i<count($criteria); $i++) {
    $auxGrades[$i] = $row["grade_$i"];
    if ($auxGrades[$i]<1 || $auxGrades[$i]>$criteria[$i][1])
      $auxGrades[$i] = NULL;
    if ($mxGrades < $criteria[$i][1])
      $mxGrades = $criteria[$i][1];
  }
}
if ($mxGrades<MAX_CONFIDENCE)  $mxGrades = MAX_CONFIDENCE;

$cmnts2athr = isset($row['cmnts2athr']) ? htmlspecialchars($row['cmnts2athr']) : '';
$cmnts2PC   = isset($row['cmnts2PC'])   ? htmlspecialchars($row['cmnts2PC'])   : '';
$cmnts2chair= isset($row['cmnts2chair'])? htmlspecialchars($row['cmnts2chair']): '';

if (isset($row['lastModif'])) // revision 
     $update = ' (updated)';
else $update = '';
$watch = $row['watch'];

if ($disFlag && !$watch) { // put a checkbox to add to watch list
  $watchHtml = '<input type=checkbox name=add2watch> Also add this submission to my watch list';
}
else $watchHtml = '';

$colors = array('lightgrey', 'rgb(240, 240, 240)');
$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head>
<style type="text/css">
h1 { text-align: center; }
h2 { text-align: center; }
tr { vertical-align: top; }
</style>

<title>Review of Submission $subId{$update}</title></head>
<body>
$links
<hr />
<h1>Review of Submission $subId{$update}</h1>
<h2>$title</h2>

<form action="act-review.php" enctype="multipart/form-data" method=post>

<table cellspacing="3" cellpadding="2"><tbody>
<tr><td>Reviewer:    </td> <td>$revName</td></tr>
<tr><td>Sub-reviewer:</td>
    <td><input name="subRev" type="text" value="$subRev"></td>
</tr>
</tbody></table>
<br />

<table cellspacing="0" cellpadding="2"><tbody>

<!-- A header line with the numbers -->
<tr style="text-align: center; background: $colors[0];">
  <th style="text-align: left;"><small><a href="guidelines.php#grades">Grade&nbsp;semantics</a></small>&nbsp;&nbsp;&nbsp;&nbsp;</th> 

EndMark;
print "  <th>ignore</th>\n  <th style=\"width:50px;\">1<br/><small>(low)</small></th>\n";
for ($i=2; $i<$mxGrades; $i++)
     print "  <th style=\"width:50px;\">$i</th>\n";
print "  <th style=\"width:50px;\">$mxGrades<br/><small>(high)</small></th>\n";
print "</tr>\n\n";

$chk = isset($grade) ? '' : 'checked="checked"';
print <<<EndMark
<!-- A line of radio buttons for the grade -->
<tr style="text-align: center; background: $colors[1];">
  <td style="text-align: right;">Grade:&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td><input type="radio" name="grade" value="*" $chk title="Ignore my grade">
  </td>

EndMark;
for ($i=1; $i<=MAX_GRADE; $i++) {
  $chk = ($grade==$i) ? 'checked="checked"' : '';
  print "  <td><input type=\"radio\" name=\"grade\" value=\"$i\" $chk></td>\n";
}
if (MAX_GRADE<$mxGrades) {
  $cspan = $mxGrades - MAX_GRADE; 
  print "  <td colspan=\"$cspan\"></td>\n";
}
print <<<EndMark
</tr>

<!-- A line of radio buttons for the confidence -->
<tr style="text-align: center; background: $colors[0];">
  <td style="text-align: right;">Confidence:&nbsp;&nbsp;&nbsp;&nbsp;</td>

EndMark;

$chk = isset($conf) ? '' : 'checked="checked"';
print "  <td><input type=\"radio\" name=\"conf\" value=\"*\" $chk title=\"Zero confidence\"></td>\n";
for ($i=1; $i<=MAX_CONFIDENCE; $i++) {
  $chk = ($conf==$i) ? 'checked="checked"' : '';
  print "  <td><input type=\"radio\" name=\"conf\" value=\"$i\" $chk></td>\n";
}
if (MAX_CONFIDENCE<$mxGrades) {
  $cspan = $mxGrades - MAX_CONFIDENCE; 
  print "  <td colspan=\"$cspan\"></td>\n";
}
print "</tr><tr></tr>\n\n";

if (is_array($criteria) && count($criteria)>0) {
  $parity = 0;
  $cspan = $mxGrades + 2;
  print "<tr><th style=\"text-align: left; background: $colors[$parity];\" colspan=\"$cspan\"></th></tr>\n";
  $parity = 1-$parity;
  for ($j=0; $j<count($criteria); $j++) {
    $cr = $criteria[$j];
    $grade = $auxGrades[$j];
    print <<<EndMark
<!-- A line of radio buttons for the $cr[0] -->
<tr style="text-align: center; background: $colors[$parity];">
  <td style="text-align: right;">$cr[0]:&nbsp;&nbsp;&nbsp;&nbsp;</td>

EndMark;

    $chk = isset($grade) ? '' : 'checked="checked"';
    print "  <td><input type=\"radio\" $chk name=\"grade_{$j}\" title=\"Ignore my grade\" value=\"*\"></td>\n";
    for ($i=1; $i<=$cr[1]; $i++) {
      $chk = ($grade==$i) ? 'checked="checked"' : '';
      print "  <td><input type=\"radio\" $chk name=\"grade_{$j}\" value=\"$i\"></td>\n";
    }
    if ($cr[1]<$mxGrades) {
      $cspan = $mxGrades - $cr[1];
      print "  <td colspan=\"$cspan\"></td>\n";
    }
    print "</tr>\n\n";

    $parity = 1-$parity;
  }
} // end if (is_array($criteria) && count($criteria)>0)

$cSpan = $mxGrades+1;
print <<<EndMark
</tbody></table>

<h3>Comments to the Authors:</h3>
<textarea name="comments2authors" rows="15" cols="80">$cmnts2athr</textarea>
<br />The authors, program-committee members, and chair see these comments.

<h3>Comments to the Committee:</h3>
<textarea name="comments2PC" rows="5" cols="80">$cmnts2PC</textarea>
<br />Only the program-committee members and chair see these comments.

<h3>Comments to the Chair:</h3>
<textarea name="comments2chair" rows="5" cols="80">$cmnts2chair</textarea>
<br />Only the program chair sees these comments.
<br /><br />

<input type="hidden" name="subId" value="$subId">
<center><input style="width: 100px;" type="submit" value="Submit">
$watchHtml</center>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
