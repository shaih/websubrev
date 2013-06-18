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
$pcmFlags= (int) $pcMember[5];

$chkEml = ($pcmFlags & FLAGS_EML_MY_REPORT)? ' checked="checked"' : '';

if (defined('CAMERA_PERIOD'))
  exit("<h1>Site closed: cannot post new reviews</h1>");

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

// The chair can edit anyone's review
$notMine = (isset($_GET['revId'])
	    && intval($_GET['revId']) != $revId
	    && is_chair($revId));
if ($notMine) {
  $revName = '(PC-chair modifications of reviewer&prime;s comments)';
  $revId = intval($_GET['revId']);
}
// Make sure that this submission exists and the reviewer does not have
// a conflict with it, and get the review for it (if exists)

$qry= "SELECT s.title ttl, a.assign assign, r.subReviewer subRev,
      r.lastModified lastModif, r.confidence conf, r.score score,
      r.comments2authors cmnts2athr, r.comments2committee cmnts2PC,
      r.comments2chair cmnts2chair, r.comments2self cmnts2self,
      a.watch watch, r.flags revFlags, r.attachment attachment
      FROM {$SQLprefix}submissions s
        LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
        LEFT JOIN {$SQLprefix}reports r     ON r.revId=? AND r.subId=s.subId
    WHERE s.subId=?";

// get also the auxiliary grades
$qry2 = "SELECT gradeId, grade from {$SQLprefix}auxGrades WHERE subId=? and revId=?";
$res = pdo_query($qry, array($revId,$revId,$subId));
$auxRes = pdo_query($qry2, array($subId,$revId));
$row = $res->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['assign']<0) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$mxGrades = MAX_GRADE;
$auxGrades = array();
if (is_array($criteria) && count($criteria)>0) {
  while ($auxRow = $auxRes->fetch(PDO::FETCH_NUM)) {
    $gId = $auxRow[0];
    $auxGrades[$gId] = isset($auxRow[1]) ? ((int)$auxRow[1]) : NULL;
  }
  for ($i=0; $i<count($criteria); $i++) {
    if (!array_key_exists($i, $auxGrades)
	|| $auxGrades[$i]<1 || $auxGrades[$i]>$criteria[$i][1])
      $auxGrades[$i] = NULL;
    if ($mxGrades < $criteria[$i][1]) $mxGrades = $criteria[$i][1];
  }
}
if ($mxGrades<MAX_CONFIDENCE)  $mxGrades = MAX_CONFIDENCE;

$title      = isset($row['ttl']) ? htmlspecialchars($row['ttl']) : '';
$subRev     = isset($row['subRev']) ? htmlspecialchars($row['subRev']) : '';
$conf  = (int) $row['conf'];  if ($conf<1 || $conf>3)           $conf=NULL;
$score = (int) $row['score']; if ($score<1 || $score>MAX_GRADE) $score=NULL;
$cmnts2athr= isset($row['cmnts2athr'])?htmlspecialchars($row['cmnts2athr']):'';
$cmnts2PC  = isset($row['cmnts2PC'])  ?htmlspecialchars($row['cmnts2PC']): '';
$cmnts2chair=isset($row['cmnts2chair'])?htmlspecialchars($row['cmnts2chair']):'';
$cmnts2self= isset($row['cmnts2self'])?htmlspecialchars($row['cmnts2self']):'';

$attachmentLine = '';
if (CONF_FLAGS & FLAG_REV_ATTACH) {
  $attachmentLine = 'Attach a file with your review (<a target=documentation href="../documentation/reviewer.html#revAttach"><b>read this!</b></a>): <input type=file name=attach'.$subId.' size=49><br/>';

  if (isset($row['attachment'])) {
    $ext = strtoupper(file_extension($row['attachment']));
    $attachment = $row['attachment'];
    $attachmentLine .= "\n or <input type=checkbox name=keepAttach{$subId} checked=checked> keep current <a target=_blank href=\"download.php?attachment=$attachment\">$ext attachment</a> (clear checkbox or attach a new file to discard existing attachment)<br/>";
  }
}

$pcCmmntsInitStyle = empty($cmnts2PC) ? 'hidden' : 'shown';
$chrCmmntsInitStyle = empty($cmnts2chair) ? 'hidden' : 'shown';
$slfCmmntsInitStyle = empty($cmnts2self) ? 'hidden' : 'shown';

if (isset($row['lastModif'])) $update = ' (updated)'; // revision
else $update = '';
$watch = $row['watch'];

if ($disFlag && !$watch) { // put a checkbox to add to watch list
  $watchHtml = '<input type=checkbox name=add2watch> Add this submission to my watch list<br/>';
}
else $watchHtml = '';

$colors = array('lightgrey', 'rgb(240, 240, 240)');
$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
h1 { text-align: center; }
h2 { text-align: center; }
tr { vertical-align: top; }
</style>

<script type="text/javascript" language="javascript">
<!--
  function expandCollapse(fld) {
    var f = document.getElementById(fld);
    if (f==null) return false;
    if (f.className=="shown") { f.className="hidden"; }
    else { f.className="shown"; f.focus(); }
    return false;
  }
  function hideAreas() {
    var f = document.getElementById("cmnt2PC");
    if (f!=null) f.className="$pcCmmntsInitStyle";
    f = document.getElementById("cmnt2chr");
    if (f!=null) f.className="$chrCmmntsInitStyle";
    f = document.getElementById("cmnt2slf");
    if (f!=null) f.className="$slfCmmntsInitStyle";
    f = document.getElementById("openCmnt2PC");
    if (f!=null) f.className="shown";
    f = document.getElementById("openCmnt2chr");
    if (f!=null) f.className="shown";
    f = document.getElementById("openCmnt2slf");
    if (f!=null) f.className="shown";
    return true;
}

// -->
</script>

<title>Review of Submission $subId{$update}</title></head>
<body onload="hideAreas();">
$links
<hr />
<h1>Review of Submission $subId{$update}</h1>
<h2><a target=_blank href="submission.php?subId=$subId">$title</a></h2>

<form accept-charset="utf-8" action="doReview.php" enctype="multipart/form-data" method=post>

<table cellspacing="3" cellpadding="2"><tbody>
<tr><td>Reviewer:    </td> <td>$revName</td></tr>
<tr><td>Sub-reviewer:</td>
  <td><input name="subRev" type="text" value="$subRev" size=60>
  <br/><small>Separate different sub-reviewers with a semi-colon '<b>;</b>'
  </small></td>
</tr>
</tbody></table>
<br />

<table cellspacing="0" cellpadding="2"><tbody>

<!-- A header line with the numbers -->
<tr style="text-align: center; background: $colors[0];">
  <th style="text-align: left;"><small><a target=_blank href="guidelines.php#grades">Score&nbsp;semantics</a></small>&nbsp;&nbsp;&nbsp;&nbsp;</th> 

EndMark;
print "  <th>ignore</th>\n  <th style=\"width:50px;\">1<br/><small>(low)</small></th>\n";
for ($i=2; $i<$mxGrades; $i++)
     print "  <th style=\"width:50px;\">$i</th>\n";
print "  <th style=\"width:50px;\">$mxGrades<br/><small>(high)</small></th>\n";
print "</tr>\n\n";

$chk = isset($score) ? '' : 'checked="checked"';
print <<<EndMark
<!-- A line of radio buttons for the score -->
<tr style="text-align: center; background: $colors[1];">
  <td style="text-align: right;">Score:&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td><input type="radio" name="score" value="*" $chk title="Ignore my score">
  </td>

EndMark;
for ($i=1; $i<=MAX_GRADE; $i++) {
  $chk = ($score==$i) ? 'checked="checked"' : '';
  print "  <td><input type=\"radio\" name=\"score\" value=\"$i\" $chk></td>\n";
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
print "</tr>\n\n";

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
<div ID="cmnt2athr">
$attachmentLine
<textarea name="comments2authors" rows=15 cols=80>$cmnts2athr</textarea>
<br/>The authors and program-committee see these comments.
</div>

<h3>Comments to the Committee <a class="hidden" href="#" ID="openCmnt2PC" onclick="return expandCollapse('cmnt2PC');">(click to expand/collapse)</a></h3>
<div ID="cmnt2PC">
<textarea class="shown" name="comments2PC" rows=15 cols=80>$cmnts2PC</textarea>
<br />Only the program-committee sees these comments.
</div>

<h3>Comments to the Chair <a class="hidden" href="#" ID="openCmnt2chr" onclick="return expandCollapse('cmnt2chr');">(click to expand/collapse)</a></h3>
<div ID="cmnt2chr">
<textarea name="comments2chair" rows=15 cols=80>$cmnts2chair</textarea>
<br />Only the program chair sees these comments.
</div>

EndMark;
if (!$notMine) { print <<<EndMark
<h3>Notes to yourself <a class="hidden" href="#" ID="openCmnt2slf" onclick="return expandCollapse('cmnt2slf');">(click to expand/collapse)</a></h3>
<div ID="cmnt2slf">
<textarea name="comments2self" rows=15 cols=80>$cmnts2self</textarea><br/>
No one else can see these comments
</div>
EndMark;
} 
print <<<EndMark
<br/>

<input type="hidden" name="subId" value="$subId">
<input type="hidden" name="revId" value="$revId">
<center>
<table><tbody>
<tr><td><input type="submit" value="Submit"></td>
<td align=left>
EndMark;
if (!$notMine) { print <<<EndMark
$watchHtml
<input type=checkbox name=emilReview{$chkEml}> Send my review back to me via email<br/>
<input name="draft" type="checkbox"> Remind me to go back to this review (<a target=documentation href="../documentation/reviewer.html#draftReview">what&prime;s this?</a>)
EndMark;
}
print <<<EndMark
</td>
</tr></tbody></table>
</center>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>