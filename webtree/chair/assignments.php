<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true;
require 'header.php';
$cnnct = db_connect();

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, 0 from submissions WHERE status!='Withdrawn'
  ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row = mysql_fetch_row($res)) {
  $row[1] = htmlspecialchars($row[1]);
  $subArray[] = $row;
}

$qry = "SELECT revId, name from committee WHERE revId!='" . CHAIR_ID . "'
    ORDER BY revId";
$res = db_query($qry, $cnnct);
$committee = array();
$nameList = $sep = '';
while ($row = mysql_fetch_row($res)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]), 0);
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}
$cmteIds = array_keys($committee);

// Get the assignment preferences
$qry = "SELECT revId, subId, pref, compatible, sktchAssgn FROM assignments";
$res = db_query($qry, $cnnct);
$prefs = array();
while ($row = mysql_fetch_row($res)) { 
  list($revId, $subId, $pref, $compatible, $assign) = $row; 
  if (!isset($prefs[$subId]))
    $prefs[$subId] = array();

  $prefs[$subId][$revId] = array($pref, $compatible, $assign);
}

// Make user-indicated changes before displaying the matrix
if (isset($_POST["saveAssign"])) { // input from matrix interface
  foreach($subArray as $sub) foreach($cmteIds as $revId) {
    $subId = (int) $sub[0];
    $assgn = isset($_POST["a_{$subId}_{$revId}"]) ? 1 : 0;

    // do not override a conflict
    if (isset($prefs[$subId][$revId][2])
	&& $prefs[$subId][$revId][2] == -1) $assgn=-1;

    if (isset($prefs[$subId][$revId])                 // modify existing entry
	&& (isset($_POST["visible"]) || $prefs[$subId][$revId][2]!=$assgn)) {
      $prefs[$subId][$revId][2] = $assgn;
      $qry = "UPDATE assignments SET sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      $qry .= " WHERE revId='{$revId}' AND subId='{$subId}'";
      db_query($qry, $cnnct);
    }

    if (!isset($prefs[$subId][$revId]) && $assgn!=0) {// inser a new entry
      if (!isset($prefs[$subId])) { $prefs[$subId] = array(); }
      $prefs[$subId][$revId] = array(3, 0, $assgn);
      $qry = "INSERT INTO assignments SET revId={$revId}, subId={$subId}, sktchAssgn={$assgn}";
      if (isset($_POST["visible"])) $qry .= ", assign={$assgn}";
      db_query($qry, $cnnct);
    }
  }
}
else if (isset($_POST["manualAssign"])) { // input from list interface
  $newAssignment = array();
  foreach($subArray as $sub) {
    $subId = (int) $sub[0];
    if (!isset($_POST["cList{$subId}"])) continue;
    $newAssignment[$subId] = array();

    $nameList = explode(';', $_POST["cList{$subId}"]);
    $list = '';
    foreach ($nameList as $revName) {
      $revName = trim($revName); if (empty($revName)) continue;
      $revId = match_PCM_by_name($revName, $committee);

      if ($revId==-1 || $prefs[$subId][$revId][2]==-1) continue;
      $newAssignment[$subId][$revId]=1;

      $list .= $revId . ', ';
      if (!isset($prefs[$subId][$revId])) { // insert new entry
	$prefs[$subId][$revId] = array(3, 0, 1);
	$qry = "INSERT INTO assignments SET revId={$revId}, subId={$subId}, sktchAssgn=1";
	if (isset($_POST["visible"])) $qry .= ", assign=1";
	db_query($qry, $cnnct);
      }
      else if (isset($_POST["visible"]) || $prefs[$subId][$revId][2] != 1) { // update existing entry
	$prefs[$subId][$revId][2] = 1;
	$qry = "UPDATE assignments SET sktchAssgn=1";
	if (isset($_POST["visible"])) $qry .= ", assign=1";
	$qry .= " WHERE revId='{$revId}' AND subId='{$subId}'";
	db_query($qry, $cnnct);
      }
    }

    // Remove all other assignments to $subId from database and $prefs
    $qry = "UPDATE assignments SET sktchAssgn=0";
    if (isset($_POST["visible"])) $qry .= ", assign=0";
    $qry .= " WHERE subId={$subId} AND revId NOT IN ({$list}0) AND sktchAssgn=1";
    db_query($qry, $cnnct);
    foreach ($prefs[$subId] as $revId => $p) {
      if ($p[2]==1 && !isset($newAssignment[$subId][$revId]))
	$prefs[$subId][$revId][2] = 0;
    }
  }
}

/*********************************************************************/
/******* Now we can display the assignments matrix to the user *******/
/*********************************************************************/
$classes = array('zero', 'one', 'two', 'three', 'four', 'five');
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

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
</style>
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

<title>Manual Assignments of Submissions to Reviewers</title>
</head>

<body>
$links
<hr />
<h1>Manual Assignments of Submissions to Reviewers</h1>

<form action="scrapAssignments.php" enctype="multipart/form-data" method="post">
To manually assign submissions to reviewers, you can use either the <a
href="#matrix">matrix interface</a> or the <a href="#sublist">submission-list
interface</a> below. The two interfaces are synchronized (and the sums at
the right column and the bottom row of the matrix are updated) whenever
you hit the "Save Assignments" button at the bottom of either interface.
In addition, if you check the box for making the changes visible then the
reviewers will be able to see what submissions were assigned to them.<br/>
<br/>
You can always start from scratch by using the clear-all button, or
reset the form to the assignments that are currently visible to the
reviewers.
<input type="submit" name="clearAll" value="Clear All Assignments"> or 
<input type="submit" name="reset2visible" value="Reset to Visible Assignments">
Note: this only effects the scratch copy of the assignments and is not
visible to the reviewers. (<a target=_blank
href="../documentation/chair.html#scratchAssign">explain this</a>)
</form>

EndMark;

if (defined('REVPREFS') && REVPREFS) {
  print <<<EndMark
You can have the software automatically compute an assignment of
submissions to reviewers (using the reviewer preferences and a stable-marriage
algorithm) by going to <a href="autoAssign.php">the Auto-Assignment page</a>.
The Auto-Assignment page includes also a form for specifying the <a
href="autoAssign.php#chairPrefs">chair-preferences</a>, and these
preferences are used in the automatic assignment algorithm. Also, the
check-boxes in the <a href="#matrix">matrix interface</a> below will
be colored <span style="color: green;">green</span> when you indicate
a preference for the PC-member to review the submission or <span
style="color: red;">red</span> when you indicate a preference that the
PC-member do not review the submission.<br/>
<br/>

EndMark;
}

print <<<EndMark
<a name="matrix"></a><h2>Matrix Interface</h2>
<form action="assignments.php" enctype="multipart/form-data" method="post">
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
foreach ($cmte as $name) { $header .= "  <th>".$name."</th>\n"; }
$header .= "  <th>Num</th>\n";
$header .= "  <th>&nbsp;Title&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>\n";
$header .= "<th>&nbsp;&nbsp;#&nbsp;&nbsp;</th>\n";
$header .= "</tr>\n";
print $header;

$n = count($committee);
$count = 0;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
  print "<tr><td>{$subId}</td>\n";
  foreach ($committee as $revId => $pcm) {
    $name = $pcm[0];
    if (isset($prefs[$subId][$revId])) {
      $prf = $prefs[$subId][$revId][0];
      $cmpt= $prefs[$subId][$revId][1];
      $assgn=$prefs[$subId][$revId][2];
      if ($assgn==1) { // update num of assignments for submission, reviewer
        $sub[2]++;
	$committee[$revId][1]++;
      }
    }
    else { $prf = 3; $cmpt = $assgn = 0;
    }
    print "  <td>".colorNum($prf)."<br />";
    print checkbox($subId, $revId, $name, $cmpt, $assgn);
    print "\n  </td>\n";
  }
  print "  <td>{$sub[0]}</td>\n";
  print "  <td style=\"text-align: left; font: italic 12px ariel;\">\n";
  print "    <a href=\"../review/submission.php?subId={$subId}\">{$sub[1]}</a></td>\n";
  print "  <td id=\"subSum_{$sub[0]}\">{$sub[2]}</td>\n";
  print "</tr>\n";
  if ($count >= 5) {
    print $header;
    $count = 0;
  }
  else $count++;
}

if ($count > 1) print $header;
print "<tr><td> </td></tr><tr><td>#:</td>\n";
foreach ($committee as $pcm) { print "  <td>{$pcm[1]}</td>\n"; }
print "  <td colspan=3> &nbsp;</td>\n";
print "</tr>\n";

print <<<EndMark
</tbody></table>
<a name="saveMatrix"></a>
<input type="hidden" name="saveAssign" value="on">
<input type="submit" value="Save Assignments">
<input type="checkbox" name="visible" value="on">
Make these assignments visible to the reviewers
</form>
<hr /><hr />
<a name="sublist"></a><h2>Submission-List Interface</h2>
For each submission you may provide a <b>semi-colon-separated</b> list of PC
members that are assigned to review that submission. When listing PC members,
you should provide the name of the PC member as recorded in the database. You
can specify only a prefix of the name, as long as it is sufficient to uniquely
identify a single member (e.g., if you have a committee member named Wawrzyniec
C. Antroponimiczna, it may be enough to write "Waw"). For example, to assign
Attila T. Hun and John Doe to review submission number 132, you may use
the following line:

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
<form action="assignments.php" enctype="multipart/form-data" method="post" autocomplete="off">
<ol>

EndMark;
foreach ($subArray as $sub) { 
  $subId = $sub[0];
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
    <a href="../review/submission.php?subId=$subId">$sub[1]</a><br/>
    <input type="text" name="cList{$subId}" size=85 value="$val"><br/><br/>
  </li>

EndMark;
}

print <<<EndMark
</ol>
<input type="hidden" name="manualAssign" value="on">
<input type="submit" value="Save Assignments">
<input type="checkbox" name="visible" value="on">
Make these assignments visible to the reviewers
</form>

<hr />
$links
</body>
</html>

EndMark;
exit();

function colorNum($num)
{
  global $classes;
  if ($num >=0 && $num <=5 && $num != 3)
    return '<span class="'.$classes[$num].'"> '.$num.' </span>';
  else return '';
}

function checkbox($subId, $revId, $name, $cmpt, $isChecked)
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
  case -1:
      return '<span class="'.$cls.'"><img src=../common/xmark.gif title="'.$ttl.' (conflict)" alt="x"></span>';
      // $chk = ' disabled="disabled"'; $ttl .= ' (conflict)'; break;
  case 1:
    $chk = ' checked="checked"'; $ttl .= ' (assigned)'; break;
  default:
    $chk = '';
  }

  return "<span class=\"{$cls}\"><input
    type=\"checkbox\" name=\"a_{$subId}_{$revId}\" title=\"{$ttl}\"{$chk}></span>";
}
?>
