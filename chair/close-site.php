<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

$cmrDdline = CAMERA_DEADLINE;
$now= date('r (T)');

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
</style>
<title>Close Final-Version Submission Site</title>
</head>
<body>
$links
<hr />
<h1>Close Final-Version Submission Site</h1>
<center>
<table><tbody>
<tr><td>Camera-ready deadline:</td><td style="color: red;">$cmrDdline</td></tr>
<tr><td>The time now is:</td><td style="color: blue;">$now</td></tr>
</tbody></table>

<h2>Are you sure you want to close the submission site?</h2>
<form action="act-recustomize.php"  enctype="multipart/form-data" method="post">
<input type="hidden" name="shutdown" value="yes">
<input type="submit" value="Yes, Shutdown Final-Version Submission Site">
</form>
</center>
<hr />
$links
</body>
</html>

EndMark;
?>
