<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

// Get the assignment preferences

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$subArray = $res->fetchAll(PDO::FETCH_NUM);

$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & ?) ORDER BY revId";
$res = pdo_query($qry, array(FLAG_IS_CHAIR));
$committee = array();
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]));
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}

// read current chair-preferences from database
$prefs = array();
$qry = "SELECT revId, subId, compatible FROM {$SQLprefix}assignments WHERE revId NOT IN(" . implode(", ", chair_ids()) . ") ORDER BY subId, revId";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $subId = (int) $row[1];
  $compatible = (int) $row[2];
  if (!isset($prefs[$subId])) $prefs[$subId] = array();
  $prefs[$subId][$revId] = $compatible;
}

/*********************************************************************/
/******* Now we can display the assignments matrix to the user *******/
/*********************************************************************/
$links = show_chr_links(0,array('assignments.php','Assignments'));
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">h1 { text-align:center; }</style>
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

<title>Chair Assignment Preferences</title>
</head>
<body>
$links
<hr/>
<h1>Chair Assignment Preferences</h1>
<p>
You can use this form to specify your own preferences as to who should
(or should not) review what submission. These preferences are non-binding,
they are used just to keep notes for yourself: the choices you make here
will be shown in the <a href="assignmentMatrix.php">matrix interface</a>.
Specifically, the check-boxes are colored <span style="color: green;">
green</span> when you indicate that the PC-member should review the submission
or <span style="color: red;">red</span> when you indicate that the PC-member
should not review the submission.</p>
<p>
For each submission you may provide a <b>semi-colon-separated</b> list
of PC members that you would like to review that submission, and another
list of members that you prefer will <i>not</i> review that submission. 
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
<form accept-charset="utf-8" action="saveChairPrefs.php" enctype="multipart/form-data" method="post" autocomplete="off">
<ol>
EndMark;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  $sbPrefs = $prefs[$subId];
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
    <br/><br/>
  </li>

EndMark;
}

print <<<EndMark
</ol>
<input type="submit" name="saveChairPrefs" value="Save Chair Preferences">
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
