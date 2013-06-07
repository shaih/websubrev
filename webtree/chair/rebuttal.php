<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
$rebDeadline = defined('REBUTTAL_DEADLINE') ? date('Y-n-j G:i e', REBUTTAL_DEADLINE) : "";
$maxRebuttal = defined('MAX_REBUTTAL') ? MAX_REBUTTAL : 3000;
if (active_rebuttal()) {
  $rebuttalOn = '';
  $rebuttalOff = '<input type="submit" value="Close Rebuttal Now"/>';
} else {
  $rebuttalOn = '<input type="submit" name="rebuttalOn" value="Activate Rebuttal"/>';
  $rebuttalOff = ' or <input type="submit" value="Only Store Parameter"/>';
}
$succ = isset($_GET['success'])? 
  '<h2>You have successfully updated the rebuttal data</h2>' : '';
$links = show_chr_links();
//Set rebuttal deadline
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8"><title>Rebuttals</title>
</head>
<body>
$links
<hr />
$succ
<h1>Set Rebuttal Parameters</h1>
<form accept-charset="utf-8" action="doRebuttal.php" enctype="multipart/form-data" method="post">
  <p>Once the rebuttal is active, authors will be able to make a single rebuttal to comments made by reviewers (until you close it). As usual, the deadline you enter here is only for informational purposes, <b>the software does not enforce it automatically.</b></p>
  <label>Rebuttal Deadline:</label>
  <input type="text" name="rebDeadline" size="40" value="{$rebDeadline}"/>
  <tt>&lt;== Format dates like 2013-05-15 18:20 EDT</tt>
  <br />
  <label>Number of Characters Allowed in Rebuttal:</label>
  <input type="text" name="maxRebuttal" value="{$maxRebuttal}"/><br/>
  $rebuttalOn
  $rebuttalOff
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
