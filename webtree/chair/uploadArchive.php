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

if ($_FILES['sub_archive']['size']<=0 || 
    !is_uploaded_file($_FILES['sub_archive']['tmp_name'])) {
  die("Upload failed");
}

$fname = $_FILES['sub_archive']['name'];
$suffix=substr($fname, strrpos($fname, '.')+1);
if ($suffix!='zip' && $suffix!='tar' && $suffix!='tgz') {
  die("Uploaded file must have suffix either tar, tgz, or zip");
}
$fname = SUBMIT_DIR.'/all_in_one.'.$suffix;

$oldFile = SUBMIT_DIR."/all_in_one.tgz";
if (!file_exists($oldFile)) {   // maybe .zip rather than .tzg?
  $oldFile = SUBMIT_DIR."/all_in_one.zip";
  if (!file_exists($oldFile)) { // or perhaps jusr .tar?
    $oldFile = SUBMIT_DIR."/all_in_one.tar";
    if (!file_exists($oldFile)) $oldFile = NULL; // oh, I give up
  }
}
if (file_exists($oldFile)) rename($oldFile, $oldFile.'.bak');

// Write the new file
if (!move_uploaded_file($_FILES['sub_archive']['tmp_name'], $fname)) {
  rename($oldFile.'.bak', $oldFile); // if failed - recover backup
  die("Upload failed");
}

exit("<h1>Archive file all_in_one.$suffix uploaded</h1>\n<a href=\".\">Back to main page</a>");
?>
