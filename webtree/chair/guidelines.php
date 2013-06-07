<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

// If the chair uploaded a new file then store it
if (isset($_POST["uploadGuidelines"])) {
  if (($_FILES['guidelinesFile']['size'] <= 0)
      || !is_uploaded_file($_FILES['guidelinesFile']['tmp_name'])) {
    die("<h1>Uploading file failed!</h1>");
  }

  if (isset($_POST["what"]) && $_POST["what"]=='camera') {
    $gdFile = SUBMIT_DIR."/final/guidelines.html";
    $bkFile = SUBMIT_DIR."/cmrGuidelines.bak.html";
  }
  else {
    $gdFile = SUBMIT_DIR."/guidelines.html";
    $bkFile = SUBMIT_DIR."/revGuidelines.bak.html";
  }
  // Move the old guidelines file to backup
  if (file_exists($bkFile)) unlink($bkFile);
  rename($gdFile, $bkFile);

  // Write the new file
  move_uploaded_file($_FILES['guidelinesFile']['tmp_name'], $gdFile)
    or rename($bkFile, $gdFile); // if failed - recover backup

  chmod($gdFile, 0664); // makes debugging a bit easier
  header("Location: index.php");
  exit();
}

// Otherwise, show a simple form that allows access to the default and
// the current files, with an option to upload a new guidelines file

if (isset($_GET['what']) && $_GET['what']=='camera'){ // camera instractions
  $what = 'camera';
  $gdlines = 'Camera-Ready Instructions';
  $default = 'defaultCameraGuidelines.html';
  $current = '../submit/cameraInstructions.php';
} else {                                              // reviewing guidelines
  $what = 'review';
  $gdlines = 'Review Guideline';
  $default = 'defaultReviewGuidelines.html';
  $current = '../review/guidelines.php';
}
$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
</style>
<title>Set $gdlines for $cName</title>
</head>
<body>
$links
<hr />
<h1>Set $gdlines for $cName</h1>
Use this form to upload the $gdlines to the server. You can start by
downloading a <a href="$default">template file</a>, saving it to your
local machine, then editing this template file and uploading it back to
the server. You can also see the <a href="$current">last file that
you uploaded</a> (if any).<br/>
<br/>
<form accept-charset="utf-8" action="guidelines.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="what" value="$what">
<input name="guidelinesFile" size="60" type="file">
<input type="submit" name="uploadGuidelines" value="Upload $gdlines File">
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
