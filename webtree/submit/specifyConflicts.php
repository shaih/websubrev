<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the contacts file and utils file
$confName = CONF_SHORT . ' ' . CONF_YEAR;
$links = show_sub_links();

$subId  = (int)$_GET['subId'];
$subPwd = $_GET['subPwd'];

// read the current blocked submissions from the database
$qry = "SELECT c.revId, c.name, a.authConflict FROM {$SQLprefix}committee c
  JOIN {$SQLprefix}submissions s ON s.subId=$subId AND s.subPwd=?
  LEFT JOIN {$SQLprefix}assignments a ON a.subId=$subId AND a.revId=c.revId
  WHERE c.revId!=".CHAIR_ID." ORDER BY c.name";
$res = pdo_query($qry, array($_GET['subPwd']));
$confHome = '<a href="'.CONF_HOME.'" target="_blank">'.CONF_SHORT.' '.CONF_YEAR.' site</a>';

print <<<EndMark
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<title>Specify Conflicts for $confName Submission $subId</title>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<script language="Javascript" type="text/javascript">
<!--
function chkBox(fld,id) {
  var box = document.getElementById(id);
  if (fld.value!="") {
    box.checked=true;
  }
  // else { box.checked=false; }
  return false;
}
//-->
</script>
</head>
<body>
$links
<hr/>

<h1>Specify Conflicts for $confName Submission $subId</h1>
<p>Check the boxes below that correspond to PC members that have a conflict of interest with this submission. You can optionally also specify the reason for marking this PC member as conflicted (e.g., author, friend, co-worker, etc.) See the $confHome for the conflict-of-interest rules and the list of PC members.
</p>
<form accept-charset="utf-8" action="doConflicts.php" enctype="multipart/form-data" method="post">
<input type='hidden' name="subId" value="{$subId}">
<input type='hidden' name="subPwd" value="{$subPwd}">
<table><tbody>
<tr><th></th><th>Name</th><th>Reason (optional)</th></tr>

EndMark;

while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $checked = empty($row[2])? '' : " checked='yes'";
  print "<tr><td class='rjust'><input type='checkbox' name='c[{$row[0]}]' value=1{$checked} ID='id{$row[0]}'></td>\n";
  print "  <td>{$row[1]}</td>\n";
  print "  <td><input type='text' name='r[{$row[0]}]' value='{$row[2]}' onchange='return chkBox(this,\"id{$row[0]}\")'></td></tr>\n";
}

print <<<EndMark
</table>
<input type="submit" value="Record Conflicts">
</form>
<hr/>
$links
</body></html>

EndMark;
?>
