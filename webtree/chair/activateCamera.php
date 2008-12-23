<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");

$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();
$cmrDdline = utcDate('r (T)', CAMERA_DEADLINE);

$prot = (defined('HTTPS_ON') || isset($_SERVER['HTTPS']))? 'https' : 'http';
$baseURL = $prot.'://'.BASE_URL;

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<style type="text/css">
h1 {text-align: center;}
</style>
<title>Activate Final-Version Submission Site for $cName</title>
</head>
<body>
$links
<hr />
<h1>Activate Final-Version Submission Site for $cName</h1>
When you hit the "Activate Final-Version Submissions" button at the
bottom of this page, the authors of accepted papers will be sent emails,
telling them that the final-version submission site is open. At the same
time, the review site will switch to a read-only mode. (I.e., PC members
will still be able to view the reviews and discussions but not to modify
them or insert new ones.)
<p>
You can use the form below to customize the email that is sent to the
authors, and also if you want to change the camera-ready deadline.
</p>

<form name="cameraInstructions" action="doActivateCamera.php"
      enctype="multipart/form-data" method="post">
Subject: <input type=text name=subject size=75 value="Final-version submission site for $cName now open">
<textarea cols=80 rows=15 name="finalVersionInstructions">The site for uploading the camera-ready papers for $cName is now open at

  ${baseURL}submit/

Instructions for preparing your camera-ready versions are available there. The deadline for uploading your submission to the server, as well as for sending the copyright form, is $cmrDdline.

Regards,

The chair(s)</textarea><br/>
<br />
Camera-ready deadline:
<input type=text name=cameraDeadline value="$cmrDdline" size=50>
<br/><br/>
<input type="submit" value="Activate Final-Version Submissions">
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
