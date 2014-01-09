<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';
$cName = CONF_SHORT.' '.CONF_YEAR;

$statuses = array();
$qry = "SELECT status, COUNT(subId) FROM {$SQLprefix}submissions WHERE status!='Withdrawn' GROUP BY status";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $stts = $row[0];
  $statuses[$stts] = $row[1];
}
$scstatuses = array();
$qry = "SELECT scratchStatus, COUNT(subId) FROM {$SQLprefix}submissions WHERE status!='Withdrawn' GROUP BY scratchStatus";
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $stts = $row[0];
  $scstatuses[$stts] = $row[1];
}

// Prepare a list of status modifications
$qry = "SELECT subId, description, UNIX_TIMESTAMP(entered) FROM {$SQLprefix}changeLog WHERE changeType='Status' ORDER BY entered DESC";
$res = pdo_query($qry);
$infoHist = '';
$historyHTML = '';
$history = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = $row[0];
  $when = utcDate('M-d H:i', $row[2]);

  $historyHTML .= "<tr><td><b>$subId.</b></td><td>$when: </td><td> {$row[1]}</td></tr>\n";

  $line = "$when: " . $row[1];
  if (empty($history[$subId])) $history[$subId] = $line;
  else $history[$subId] .= "<br/>\n" . $line;
}
if (!empty($historyHTML)) {
  $historyHTML = '<h3>Complete Revision History <a class=hidden name="openHistory" href="#openHistory" Id="openHistory" onclick="return expandCollapse(\'history\');">(click to expand/collapse)</a></h3>
<div Id="history"><table><tbody>
<tr align=left><th>Num</th><th>When</th><th> What</th></tr>'
    . "\n" . $historyHTML
    .'</tbody></table></div>'; 
  $infoHist = '<span style="font-size: 80%;"><br/>Hover mouse over<br/>bullets for history</span>';
}

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/saving.css" />
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<style type="text/css">
h1 {text-align: center;}
a.tooltips { color: blue; }
a.tooltips:hover span { width: 250px; }
</style>

<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="status.js"></script>
<script type="text/javascript" language="javascript">
<!--
  function expandCollapse(fid) {
    fld = document.getElementById(fid);
    if (fld.className=="shown") { fld.className="hidden"; }
    else { fld.className="shown"; }
    return false;
}

  function expand(fid) {
    fld = document.getElementById(fid);
    fld.className="shown"; 
    return true;
}

  function hideHistory() {
    document.getElementById("history").className="hidden";
    document.getElementById("openHistory").className="shown";
    return true;
}
// -->
</script>

<title>$cName Set Submission Status</title>
</head>
<body onload="hideHistory();">
$links
<hr />
<h1>$cName Set Submission Status</h1>
<p>
On this form you can works with a scratch copy of submissions&prime; status,
which is only visible to the chair(s). Alternatively, by checking the box next
to the submit button you can update both the scratch status and the "actual"
status which is visible to all PC members.</p>

<center>
EndMark;
print status_summary($statuses,$scstatuses); // defined in header.php
print <<<EndMark
</center>
<p class="jsEnabled hidden">
If Javascript is enabled in your browser, then the sums above will be updated
whenever you make a change, and the page will try to post your changes to
the server asynchronously as you make them. This is a "best effort" approach,
however, you will <b>not be notified</b> of communication errors with the
server. To ensure that the server has all the changes, please submit the
form using the submit button at the bottom.</p>
$infoHist
<br/>
<div style="width: 100%;">
<form accept-charset="utf-8" id="setStatus" action="setStatus.php" enctype="multipart/form-data" method="post">
<table style="width: 100%;"><tbody><tr style="vertical-align: top;">
  <th style="width: 90px;">Status:<br/><small>&nbsp;&nbsp;visible/scratch</small></th>
  <th>Num</th>
  <th class="setNO" style="width: 20px;">NO</th>
  <th class="setRE" style="width: 20px;">RE</th>
  <th class="setMR" style="width: 20px;">MR</th>
  <th class="setDI" style="width: 20px;">DI</th>
  <th class="setMA" style="width: 20px;">MA</th>
  <th class="setAC" style="width: 20px;">AC</th>
  <th style="text-align: left;">Title</th>
</tr>

EndMark;

// Prepare an array of submissions
$qry = "SELECT subId,title,status,scratchStatus FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$subArray = $res->fetchAll(PDO::FETCH_ASSOC);
$maxSubId = end($subArray);
$maxSubId = $maxSubId['subId'];
reset($subArray);

foreach($subArray as $sb) {
  $subId = $sb['subId'];
  $title = htmlspecialchars($sb['title']);
  $status = $sb['status'];
  $scstatus = $sb['scratchStatus'];
  $zIdx = $maxSubId - $subId; // z-index for popup tooltips

  $chk0 = $chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = '';
  if ($scstatus == 'Withdrawn') {
    $chk1 = "checked=\"checked\"";
  } else if ($scstatus == 'Reject') {
    $chk2 = "checked=\"checked\"";
  } else if ($scstatus == 'Perhaps Reject') {
    $chk3 = "checked=\"checked\"";
  } else if ($scstatus == 'Needs Discussion') {
    $chk4 = "checked=\"checked\"";
  } else if ($scstatus == 'Maybe Accept') {
    $chk5 = "checked=\"checked\"";
  } else if ($scstatus == 'Accept') {
    $chk6 = "checked=\"checked\"";
  } else { 	$chk0 = "checked=\"checked\""; }
  $statusHTML = show_status($status);
  $scstatusHTML = show_status($scstatus);

  print '<tr><td>';
  if (empty($history[$subId])) print "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
  else {
    print '<a style="z-index: '.$zIdx.';" class=tooltips href="#openHistory" onclick="return expand(\'history\');">&nbsp;&bull;&nbsp;&nbsp;<span>'.$history[$subId]."</span></a>\n";
  }
  print <<<EndMark
  $statusHTML/<span title="$scstatus" id="sc{$subId}">$scstatusHTML</span></td>
  <th style="width: 20px;">$subId.</th>
  <td class="setNO" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="None" $chk0>
  </td>
  <td class="setRE" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="Reject" $chk2>
  </td>
  <td class="setMR" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="Perhaps Reject" $chk3>
  </td>
  <td class="setDI" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="Needs Discussion" $chk4>
  </td>
  <td class="setMA" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="Maybe Accept" $chk5>
  </td>
  <td class="setAC" style="width: 20px;">
    <input type="radio" name="scrsubStts{$subId}" class="statusRadio" value="Accept" $chk6>
  </td>
  <td>$title</td>
</tr>

EndMark;
}
print "</tbody></table>\n";

if (PERIOD<PERIOD_FINAL) 
  print '<br/>&nbsp;<input type="submit" value="Save Status"/>
<input type="checkbox" name="visible" value="yes"/>
Make these status assignments visible to reviewers';

print <<<EndMark
</form>
</div>
$historyHTML
<hr />
{$links}
</body></html>
EndMark;
?>
