<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true;
require 'header.php';

// Get the assignment preferences
$cnnct = db_connect();

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 from submissions WHERE status!='Withdrawn'
  ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row = mysql_fetch_row($res)) { $subArray[] = $row; }

$qry = "SELECT revId, name from committee WHERE revId!=". CHAIR_ID
     . " ORDER BY revId";
$res = db_query($qry, $cnnct);
$committee = array();
$nameList = $sep = '';
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]));
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}

// read current chair-preferences from database
$curPrefs = array();
$qry = "SELECT revId, subId, compatible FROM assignments WHERE revId!="
     . CHAIR_ID . " ORDER BY subId, revId";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  $subId = (int) $row[1];
  $compatible = (int) $row[2];
  if (!isset($curPrefs[$subId])) $curPrefs[$subId] = array();
  $curPrefs[$subId][$revId] = $compatible;
}

// If user specified preferences, use them to update the preferences table.
if (isset($_POST["saveChairPrefs"])) {
  $prefs = array();
  foreach($_POST as $nm =>  $val) {
    $val = trim($val);

    // Look for fields with names yes<nnn> or no<nnn> (<nnn> is subisision-num)
    if (strncmp($nm, "cListYes", 8)==0) {
      $compatible = 1;
      $subId = (int) substr($nm, 8);
    } else if (strncmp($nm, "cListNo", 7)==0) {
      $compatible = -1;
      $subId = (int) substr($nm, 7);
    }
    if (($subId <= 0) || empty($val)) continue;
    if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }

    $x = explode(';', $val); // $val is a semi-colon-separated list
    foreach ($x as $revName) {
      $revName = trim($revName); if (empty($revName)) continue;
      $revId = match_PCM_by_name($revName, $committee);
      if ($revId == -1) continue;

      $prefs[$subId][$revId] = $compatible;
      if (!isset($curPrefs[$subId][$revId])) {     // inser a new entry
	$qry = "INSERT INTO assignments SET "
	  . "revId='{$revId}', subId='{$subId}', compatible={$compatible}";
	db_query($qry, $cnnct);
      }
      else if ($curPrefs[$subId][$revId] != $compatible) {// modify entry
	$qry = "UPDATE assignments SET compatible={$compatible} "
	  . "WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
	$curPrefs[$subId][$revId] = $compatible;
      }
    }
  }
  // entries in $curPrefs but not in $prefs should be set to 0 in database
  foreach($curPrefs as $subId => $revList)
    foreach ($revList as $revId => $compatible)
      if ($compatible != 0 && !isset($prefs[$subId][$revId])) {
	$qry = "UPDATE assignments SET compatible=0 "
	  . "WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
      }
}
// If no user preferences, use current prefs from database
else { 
  $prefs = &$curPrefs;
}

/*********************************************************************/
/******* Now we can display the assignments matrix to the user *******/
/*********************************************************************/
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

<style type="text/css">h1 { text-align:center; }</style>
<link rel="stylesheet" type="text/css" href="autosuggest.css" />        
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
<script type="text/javascript" src="autosuggest.js"></script>

<title>Auto-Assignment of Submissions to Reviewers</title>
</head>

<body>
$links
<hr />
<h1>Auto-Assignment of Submissions to Reviewers</h1>
Use this page to automatically generate an initial assignment of submissions
to reviewers. Each reviewer can specify his/her reviewing preferences, and
you can use the <a href="#chairPrefs">chair-preferences form</a> to specify
your own preferences as to who should (or should not) review what submission.
These preferences can then be used is a stable-marriage algorithm by going
to the <a href="#autoAssign">auto-assign form</a>.<br/>
<br/>
Also, the chair preferences are used to color the check-boxes in the <a
href="assignments.php#matrix">matrix interface</a> on the manual assignment
page. Specifically, the check-box is colored <span style="color: green;">
green</span> when you indicate that the PC-member should review the submission
or <span style="color: red;">red</span> when you indicate that the PC-member
should not review the submission.

<a name="chairPrefs"></a><h2>Chair Preferences</h2>
For each submission you may provide a <b>semi-colon-separated</b> list of PC
members that you would like to review that submission, and another list of
members that you prefer will <i>not</i> review that submission
(<a href="../documentation/chair.html#chairPrefs">more info</a>).

<br/>
<br/>
When listing PC members, you should provide the name of the PC member as
recorded in the database. You can specify only a prefix of the name, as long
as it is sufficient to uniquely identify a single member (e.g., if you have
a committee member named Wawrzyniec C. Antroponimiczna, it may be enough to
write "Waw"). For example, to specify that you would like Attila T. Hun and
Britney Spears to review submission number 132, but not George W. Bush, you
may use the following line:

<ol>
<li value="132"> The title of submission number 132<br/>
  Yes:<input size=85 value="Attila; Britney S" readonly="on"/><br/> 
  No: <input size=85 value="George W. B" readonly="on"/><br/></li>
</ol>

<h3>Current PC members:</h3>
<table width=100%><tbody>

EndMark;

$i = 4;
foreach ($committee as $pcm) {
  if ($i==4) print "<tr>\n";
  print "  <td>$pcm[0]</td>\n";
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
<form action="auto-assign.php" enctype="multipart/form-data" method="post" autocomplete="off">
<ol>
EndMark;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  $sbPrefs = &$prefs[$subId];
  $valYes = $valNo = $sepYes = $sepNo = '';
  if (is_array($sbPrefs)) foreach($sbPrefs as $revId => $compatible) {
    if ($compatible==0) continue;
    $pcmName = $committee[$revId][0];
    $pcmName = htmlspecialchars($pcmName);

    if ($compatible==-1) {
      $valNo .= "{$sepNo}{$pcmName}";
      $sepNo = '; ';
    } else {
      $valYes .= "{$sepYes}{$pcmName}";
      $sepYes = '; ';
    }
  }
  $title = htmlspecialchars($sub[1]);
  print <<<EndMark
  <li value="$subId">
    <a href="../review/submission.php?subId=$subId">$title</a><br/>
    Yes:<input type="text" name="cListYes{$subId}" size=85 value="$valYes">
    <br/>
    No: <input type="text" name="cListNo{$subId}" size=85 value="$valNo">
    <br/>
<br/>
  </li>

EndMark;
}

print <<<EndMark
</ol>
<input type="hidden" name="saveChairPrefs" value="on">
<input type="submit" value="Save Chair Preferences">
</form>

<hr /><hr />
<h2><a name="autoAssign">Compute Initial Assignments</a></h2>
<form action="stableMarriage.php" enctype="multipart/form-data" method="post" autocomplete="off">
When you hit the "Compute Assignments" button below, the reviewer
preferences and your own preferences from <a href="#chairPrefs">the form
above</a> will be used to compute an initial assignment of reviewers to
submissions, using a stable-marriage algorithm. You can use this form to
modify a few parameters of this algorithm:
<ul>
<li><a href="../documentation/chair.html#exRevs"
     title="Click for more information">Excluded reviewers</a>:
     <input type="text" size=85 name="cListExclude"><br/>
(a <b>semi-colon-separated</b> list of reviewer names). Some reviewers can be excluded from consideration by the algorithm (e.g., the chair). They will not be assigned any submissions (and their assignments will not be cleared even if the "keep existing assignments" checkbox below is cleared).<br/>
<br />
</li>
<li>The number of reviewers that are assigned to each submission is: <input type="text" value="3" size=1 name="subCoverage">.<br /><br />
</li>
<li>"Special" submissions: <input type="text" size=50 name="specialSubs"> (a <b>comma-separated</b> list of submission-IDs). Some submissions can be designated as "special" and assigned a different number of reviewers (e.g., PC-member submissions).<br /><br />
</li>
<li>The number of reviewers that are assigned to "special" submissions is: <input type="text" value="4" size=1 name="specialCoverage">.<br /><br />
</li>
<li>
<input type="checkbox" name="keepAssignments" checked="checked"> keep
existing assignments. Check the box if you already made some assignments
and you want to keep them. <b>Clearing the checkbox means that we start
from scratch.</b><br /><br />
</li>
<li>
The algorithm never assigns a submission to a reviewer if a conflict-of-interests exists. You should probably <a href="conflicts.php">set conflict-of-interests</a> before setting the assignments.
</li>
</ul>
<input type="submit" value="Compute Assignments">
</form>
<hr />
$links
</body>
</html>

EndMark;

?>
