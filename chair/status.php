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

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
h1 {text-align: center;}
</style>
<title>$cName Set Submission Status</title>
</head>
<body>
$links
<hr />
<h1>$cName Set Submission Status</h1>
<center>

EndMark;
print status_summary($statuses); // defined in header.php

print <<<EndMark
</center>
<br/>
<form action="setStatus.php" enctype="multipart/form-data" method="post">
<table style="width: 100%;"><tbody><tr>
  <th style="width: 30px;">Status</th><th>Num</th>
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
$qry = "SELECT subId, title, status from submissions
  WHERE status!='Withdrawn' ORDER BY subId";
$res = db_query($qry, $cnnct);
$subArray = array();
while ($row=mysql_fetch_assoc($res)) {
  $subArray[]=$row;
}

foreach($subArray as $subId => $sb) {
  $subId = $sb['subId'];
  $title = htmlspecialchars($sb['title']);
  $status = $sb['status'];

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

  print <<<EndMark
<tr><td style="width: 30px;">$status</td>
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
print <<<EndMark
</tbody></table>
<input type="submit" name="noAnchor" value="Set Status">
</form>
<hr />
{$links}
</body></html>

EndMark;
?>
