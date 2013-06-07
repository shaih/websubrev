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
$subArray = $res->fetchAll(PDO::FETCH_NUM);

$qry = "SELECT revId,name FROM {$SQLprefix}committee WHERE !(flags & ?) ORDER BY revId";
$res = pdo_query($qry, array(FLAG_IS_CHAIR));
$committee = array();
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]), 0, 0, 0);
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}
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

// Compute the load for PC membres and cover for submissions
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
$links = show_chr_links(0,array('assignments.php','Assignments'));
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/autosuggest.css" />        
<script type="text/javascript">
  /**
   * Provides suggestions for PC member names 
   * @class
   * @scope public
   */
  function Suggestions() {
    this.suggest = [ $nameList ];
  }

  window.onload = function () {
    var nForms = document.forms.length;
    for (i=0; i<nForms; i++) {
      nFlds = document.forms[i].elements.length;
      for (j=0; j<nFlds; j++) {
        fld = document.forms[i].elements[j];
	if (fld.name.substring(0,5)=="cList") {
	  var oTextbox = new AutoSuggestControl(fld, new Suggestions()); 
	}
      }
    }
  };
</script>
<script type="text/javascript" src="../common/autosuggest.js"></script>

<title>Manual Assignments: List Interface</title>
</head>

<body>
$links
<hr />
<h1 align=center>Manual Assignments: List Interface</h1>
Use the list interface below to assign submissions to reviewers.
When submitting this form, the server will only update its "scratch
copy" of the assignments (namely, the copy that is only visible to the
chair and not the reviewers). To make the assignments visible to reviewers,
you need to check also the box next to the submit button. You can also
use the <a href="assignmentMatrix.php">matrix interface</a>, or go back to
the <a href="assignments.php">top assignment page</a> where you have
buttons to clear all assignments or reset the scratch assignments to the
assignments that are currently visible to the reviewers.<br/>
<br/>
EndMark;

if (defined('REVPREFS') && REVPREFS) {
  print <<<EndMark
You can have the software automatically compute an assignment of
submissions to reviewers from the reviewer preferences by going to
<a href="autoAssign.php">the Auto-Assignment page</a>. Also, the the
matrix interface below shows you the reviewers&prime; preferences, as
well as any preference that you entered: the check-boxes themselves are
colored <span style="color: green;">green</span>  if you indicated a
preference for the PC-member to review the submission or <span
style="color: red;">red</span> if you indicated a preference that the
PC-member do not review the submission.<br/>
<br/>
EndMark;
}

print <<<EndMark
For each submission you should provide a <b>semi-colon-separated</b> list
of PC members that are assigned to review that submission. When listing
PC members, you should provide the name of the PC member as recorded in
the database. You can specify only a prefix of the name, as long as it
is sufficient to uniquely identify a single member (e.g., if you have a
committee member named Wawrzyniec C. Antroponimiczna, it may be enough
to write "Waw"). For example, to assign Attila T. Hun and John Doe to
review submission number 132, you may use the following line:

<ol>
<li value="132"> The title of submission number 132<br/>
  <input size=85 value="Attila; John D" readonly="on"/><br/></li>
</ol>

<h3>Current PC members:</h3>
<table width=100%><tbody>

EndMark;

$i = 4;
foreach ($committee as $pcm) {
  if ($i==4) print "<tr>\n";
  print "  <td style=\"font-weight: normal; text-align: left;\">$pcm[0]</td>\n";
  if ((--$i) == 0) {
    print "</tr>\n";
    $i = 4;
  }
}
if ($i < 4) {
  print "  <td colspan=$i></td>\n</tr>\n";
}

print <<<EndMark
</tbody></table>

<h3>List of submissions</h3>
<form accept-charset="utf-8" action="doAssignments.php" enctype="multipart/form-data" method=post autocomplete="off">
<ol>

EndMark;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  $title = htmlspecialchars($sub[1]);
  $sbPrefs = &$prefs[$subId];
  $val = $sep = '';
  if (is_array($sbPrefs)) foreach($sbPrefs as $revId => $pcm) {
    if ($pcm[2]!=1) continue;
    $pcmName = $committee[$revId][0];
    $pcmName = htmlspecialchars($pcmName);

    $val .= "{$sep}{$pcmName}";
    $sep = '; ';
  }
  print <<<EndMark
  <li value="$subId">
    <a href="../review/submission.php?subId=$subId">$title</a><br/>
    <input type="text" name="cList{$subId}" size=85 value="$val"><br/><br/>
  </li>

EndMark;
}

print <<<EndMark
</ol>
<input type="submit" name="manualAssign" value="Save Assignments in List Interface">
<input type="checkbox" name="visible" value="on">
Make these assignments visible to the reviewers
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
