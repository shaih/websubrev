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

$cnnct = db_connect();
$statuses = array();
$qry = "SELECT status, COUNT(subId) from submissions WHERE status!='Withdrawn'
  GROUP BY status";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) {
  $stts = $row[0];
  $statuses[$stts] = $row[1];
}

// Prepare a list of status modifications
$qry = "SELECT subId, description, UNIX_TIMESTAMP(entered) from changeLog WHERE changeType='Status' ORDER BY entered DESC";
$res = db_query($qry, $cnnct);
$infoHist = '';
$historyHTML = '';
$history = array();
while ($row=mysql_fetch_row($res)) {
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
  $infoHist = '<div style="float: left; font-size: 80%;"><br/>Hover mouse over<br/>bullets for history</div>';
}


$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<style type="text/css">
h1 {text-align: center;}
a.tooltips { border: none; }
a.tooltips:hover span { width: 250px; }
</style>

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
<center>
$infoHist

EndMark;
print status_summary($statuses); // defined in header.php

print <<<EndMark
</center>
<br/>
<form action="setStatus.php" enctype="multipart/form-data" method="post">
<table style="width: 100%;"><tbody><tr>
  <th style="width: 60px;">Status</th><th>Num</th>
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
$qry = "SELECT subId,title,status from submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row=mysql_fetch_assoc($res)) {
  $maxSubId = $row['subId'];
  $subArray[]=$row;
}

foreach($subArray as $sb) {
  $subId = $sb['subId'];
  $title = htmlspecialchars($sb['title']);
  $status = $sb['status'];
  $zIdx = $maxSubId - $subId; // z-index for popup tooltips

  $chk0 = $chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = '';
  if ($status == 'Withdrawn') {
    $chk1 = "checked=\"checked\"";
  } else if ($status == 'Reject') {
    $chk2 = "checked=\"checked\"";
  } else if ($status == 'Perhaps Reject') {
    $chk3 = "checked=\"checked\"";
  } else if ($status == 'Needs Discussion') {
    $chk4 = "checked=\"checked\"";
  } else if ($status == 'Maybe Accept') {
    $chk5 = "checked=\"checked\"";
  } else if ($status == 'Accept') {
    $chk6 = "checked=\"checked\"";
  } else { 	$chk0 = "checked=\"checked\""; }
  $status = show_status($status);

  print '<tr><td>';
  if (empty($history[$subId])) print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
  else {
    print '<a style="z-index: '.$zIdx.';" class=tooltips href="#openHistory" onclick="return expand(\'history\');">&nbsp;&bull;&nbsp;&nbsp;<span>'.$history[$subId].'</span></a>';
  }
  print <<<EndMark
  $status</td>
  <th style="width: 20px;">$subId.</th>
  <td class="setNO" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="None" $chk0>
  </td>
  <td class="setRE" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="Reject" $chk2>
  </td>
  <td class="setMR" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="Perhaps Reject" $chk3>
  </td>
  <td class="setDI" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="Needs Discussion" $chk4>
  </td>
  <td class="setMA" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="Maybe Accept" $chk5>
  </td>
  <td class="setAC" style="width: 20px;">
    <input type="radio" name="subStts{$subId}" value="Accept" $chk6>
  </td>
  <td>$title</td>
</tr>

EndMark;
}
$submit = '<input type="submit" name="noAnchor" value="Set Status">';
if (PERIOD==PERIOD_FINAL) $submit="<!-- $submit -->";
print <<<EndMark
</tbody></table>
$submit
</form>

$historyHTML

<hr />
{$links}
</body></html>

EndMark;
?>
