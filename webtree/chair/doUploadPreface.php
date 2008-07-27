<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD < PERIOD_FINAL) {
  exit("<h1>Cannot upload preface before the camera-ready deadline</h1>");
}

if ($_FILES['preface_archive']['size']<=0 || 
    !is_uploaded_file($_FILES['preface_archive']['tmp_name'])) {
  die("Upload failed");
}

$fname = $_FILES['preface_archive']['name'];
$suffix=substr($fname, strrpos($fname, '.')+1);
if ($suffix!='zip' && $suffix!='tar' && $suffix!='tgz') {
  die("Uploaded file must have suffix either tar, tgz, or zip");
}
$fname = SUBMIT_DIR.'/final/preface.'.$suffix;
$tmpFile = $_FILES['preface_archive']['tmp_name'];

if (file_exists($fname)) { // move existing file (if any) to backup
  if (file_exists(SUBMIT_DIR."/backup/preface.$suffix"))
    unlink(SUBMIT_DIR."/backup/preface.$suffix");
  rename($fname, SUBMIT_DIR."/backup/preface.$suffix");
}

if (!move_uploaded_file($tmpFile, $fname)) {
  error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fname) failed\n", 3, LOG_FILE);
  die("<h1>Upload Failed</h1>Cannot move preface file $tmpFile to $fname");
}
header("Location: index.php");
?>
