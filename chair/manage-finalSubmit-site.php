<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");

$cName = CONF_SHORT.' '.CONF_YEAR;
$cNameLowCase = strtolower(CONF_SHORT).CONF_YEAR;
$links = show_chr_links();
$cmrDdline = CAMERA_DEADLINE;


$prot = (defined('HTTPS_ON') || isset($_SERVER['HTTPS']))? 'https' : 'http';
$baseURL = $prot.'://'.BASE_URL;

$cameraInstructions =
"The proceedings of $cName will be published in Springer-Verlag's
Lecture Notes in Computer Science (LNCS). Authors must prepare their
camera-ready version in LaTeT2e. You should obtain the LNCS LaTeX2e
class file (llncs.cls), which can be obtained off Springer's site at:

  http://www.springer.de/comp/lncs/authors.html

(Off that page you will find a zip file that include the class file as
well as documentation.)

Please prepare an archive file (either zip, tar, tar.gz, or tgz) that
includes the following files:

* The source LaTeX file, including any figures, style files, and all
  other files that are needed to produce the camera ready copy. It
  would help us if you name the main LaTeX file (i.e., the one
  with the LaTeX commands \\title and \\author) $cNameLowCase.tex.
* A PDF file that was created from the source LaTeX file(s).

Use the final-version submissions form at

  ${baseURL}cameraready.php

to upload your archive file. (*NOTE: you will need the submission-ID and
password that were given to you when you first submitted the paper. Contact
the chair if you lost your submiddion-ID and password.) The deadline for
uploading the files is ".CAMERA_DEADLINE.".

The page limit is 17 pages total, including bibliography and all appendices.

Authors must also download the IACR copyright form from

  http://www.iacr.org/forms/

and mail or FAX a signed copy of this form to the editor, at the address:

[[your-address-here]]

The form should be mailed (postmarked) or FAXed by ".CAMERA_DEADLINE.".\n";

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
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
bottom of this page, the authors of accepted authors will be sent emails
with instructions for preparing the camera-ready version. At the same
time, the review site will switch to a read-only mode. (I.e., PC members
will still be able to view the reviews and discussions but not to modify
them or insert new ones.)

<h3>Instructions for camera-ready verision</h3>
<form name="committee" action="act-recustomize.php"
      enctype="multipart/form-data" method="post">

Use the text area below to customize the instructions for authors of
accepted papers. These instructions will be sent to the authors, and
will also be available off the final-version submission site. <br />

<textarea cols=80 rows=23 name="finalVersionInstructions">$cameraInstructions</textarea>
<br /><br />
Camera-ready deadline: <input type="text" name="cameraDeadline" value="$cmrDdline" size="50">
<br /><br />
<input type="submit" value="Activate Final-Version Submissions">
</form>
</body>
</html>
EndMark;
?>
