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
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
</style>
<title>Activate Final-Version Submission Site for $cName</title>
</head>
<body>
$links
<hr />
<h1>Activate Final-Version Submission Site for $cName</h1>
EndMark;

if (PERIOD<PERIOD_CAMERA) {
print <<<EndMark
<p>
When you hit the "Activate Final-Version Submissions" button below, the
camera-ready submission site will open and the review site will switch to
a read-only mode. (I.e., PC members will still be able to view the reviews
and discussions but not to modify them or insert new ones.)</p>

<form accept-charset="utf-8" name="cameraReady" action="doActivateCamera.php"
      enctype="multipart/form-data" method="post">
<input type="submit" value="Activate Final-Version Submissions">,
camera-ready deadline is
<input type=text name=cameraDeadline value="$cmrDdline" size=50>
</form>
<hr/>
EndMark;
} else {
  print "Final-Version Submission Site was activated.";
}
print <<<EndMark
<h2>Send email to authors of accepted papers</h2>
You can use this form to customize the email that is sent to the
authors. You can use the keywords <tt><\$subId>, <\$subPwd>,
<\$authors>, <\$title></tt>, and <tt><\$comments></tt>, in the message
body. They will be replaced by the submission-ID, password, authors,
title and the comments-to-authors as they appear in the database.
(To be recognized as keywords, these words MUST include the '<'
and '>' characters and the dollar-sign.)
<br/><br/>
<form accept-charset="utf-8" name="cameraReady" action="doEmailAuthors.php"
  enctype="multipart/form-data" method="post">
<input type="hidden" name="emailTo" value="AC">
Subject: <input type=text name="subject" size=75 value="Final-version submission site for $cName now open"><br/>
<textarea cols=80 rows=15 name="message">The site for uploading the camera-ready papers for $cName is now open at

  ${baseURL}submit/

Instructions for preparing your camera-ready versions are available there. You will need your submission-ID and password in order to upload the camera-ready version. These are:

Submission-ID: &lt;\$subId&gt;
Password:      &lt;\$subPwd&gt;

The deadline for uploading your submission to the server is $cmrDdline.

Regards,

The chair(s)</textarea><br/>
<input type="submit" name="SendEmail" value="Send Email">

<hr/>
$links
</body>
</html>
EndMark;
?>
