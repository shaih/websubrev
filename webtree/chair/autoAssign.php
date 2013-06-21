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

// An array of PC members (for the autoSuggest functionality)
$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & ". FLAG_IS_CHAIR .") ORDER BY revId";
$res = pdo_query($qry);
$committee = array();
$nameList = $sep = '';
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $committee[$revId] = array(trim($row[1]));
  $nameList .= $sep . '"'.htmlspecialchars(trim($row[1])).'"';
  $sep = ",\n    ";
}

// Recall the last choices that were made in this form (if any)
$excludedRevs='';
$specialSubs='';
$coverage=3;
$spclCvrge=4;
$startFromScratch = '';
$startFromCurrent = 'checked="on"';
$res = pdo_query("SELECT * FROM {$SQLprefix}assignParams WHERE idx=1");
if ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $excludedRevs = htmlspecialchars($row['excludedRevs']);
  $specialSubs  = htmlspecialchars($row['specialSubs']);
  $coverage  = (int) $row['coverage'];
  $spclCvrge = (int) $row['spclCvrge'];
  if ($row['startFrom']=='scratch') {
    $startFromScratch='checked="on"';
    $startFromCurrent = '';
  }
} else {
  // Guess that the special submissions are the PC-authored papers
  $conflicts = array();
  // Check for -1 or -2 assignments (conflict or PC-member paper)
  $res = pdo_query("SELECT MIN(assign), subId FROM {$SQLprefix}assignments GROUP BY subId");
  while ($row = $res->fetch(PDO::FETCH_NUM))
    if (isset($row[0]) && $row[0]<=-2) $conflicts[] = $row[1];
  $specialSubs = implode(',',$conflicts);
}

$links = show_chr_links(0,array('assignments.php','Assignments'));
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">h1 { text-align:center; }</style>
<link rel="stylesheet" type="text/css" href="../common/autosuggest.css"/>

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

  function setRadio(rad,val) {
    var i;
    for (i=0; i<rad.length; i++) {
      if (rad[i].value==val) rad[i].checked=true;
      else                   rad[i].checked=false;
    }
    return true;
  }

  function resetFile(f) {
    f.setAttribute('type', 'input');
    f.setAttribute('type', 'file');
  }
</script>
<script type="text/javascript" src="../common/autosuggest.js"></script>

<title>Auto-Assignment of Submissions to Reviewers</title>
</head>
<body>
$links
<hr />
<h1>Auto-Assignment of Submissions to Reviewers</h1>
<form accept-charset="utf-8" name="autoAssign" action="doAutoAssign.php" enctype="multipart/form-data" method="post" autocomplete="off">
Use this page to automatically generate an assignment of submissions to
reviewers. When you hit the "Compute Assignments" button below, the reviewer
preferences will be used to compute an assignment of reviewers
to submissions. You can use this form to modify a few parameters of this
assignment:
<ul>
<li>
The algorithm never assigns a submission to a reviewer if a conflict-of-interests exists. You should probably <a href="conflicts.php">set conflict-of-interests</a> before submitting this form.<br/>
<br/>
</li>
<li><a target=documentation href="../documentation/chair.html#exRevs"
    title="Click for more information">Excluded reviewers</a>:
    <input type="text" size=85 name="cListExclude" value="$excludedRevs"/>
(a <b>semi-colon-separated</b> list of reviewer names). Reviewers to be excluded from consideration by the algorithm (e.g., the chair). They will not be assigned any submissions.<br/>
<br/>
</li>
<li><a target=documentation href="../documentation/chair.html#startAssignFrom" title="Click for more information">Start from:</a>
<input type="radio" name="startFrom" value="scratch" onclick="return resetFile(document.autoAssign.assignmnetFile);" $startFromScratch/>scratch, or 
<input type="radio" name="startFrom" value="current" onclick="return resetFile(document.autoAssign.assignmnetFile);" $startFromCurrent/>the current assignments, or
<input type="radio" name="startFrom" value="file"/>assignments from stored text file</br>
<input name="assignmnetFile" size="80" type="file" onchange="return setRadio(document.autoAssign.startFrom,'file');"><br/>
If you choose to start from scratch then all existing assignments will
be cleared, <b>even those of the excluded reviewers from above</b>.<br/>
<br/>
</li>
<li>The number of reviewers that are assigned to each submission is: <input type="text" value="$coverage" size=1 name="subCoverage"/>.<br/><br/>
</li>
<li>"Special" submissions: <input type="text" size=50 name="specialSubs" value="$specialSubs"/> (a <b>comma-separated</b> list of submission-IDs). Some submissions can be designated as "special" and assigned a different number of reviewers (e.g., PC-member submissions).<br/><br/>
</li>
<li>The number of reviewers that are assigned to "special" submissions is: <input type="text" value="$spclCvrge" size=1 name="specialCoverage"/>.<br/><br/>
</li>
<li>You may want to <a href="getSketchAssign.php">store the current assignment to your local machine</a> (as a text file) before submitting this form, so you can later recover it if you do not like the result of the auto-assignment.
</li>
</ul>
<input type="submit" value="Compute Assignments">
</form>
<a target=documentation href="../documentation/chair.html#scratchAssign" title="Click for more information">Note</a>: The computed assignments will not be visible to the reviewers;
you can make them visible from the matrix or list interface pages.
<hr />
$links
</body>
</html>

EndMark;

?>
