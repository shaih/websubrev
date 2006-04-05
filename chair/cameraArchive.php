<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

if (!defined('CAMERA_PERIOD'))
  exit('<h1>Final-verions are not available yet</h1>');

require_once 'Archive/Tar.php';
$cnnct = db_connect();
$qry = "SELECT subId, format from submissions WHERE status='Accept'
  ORDER by subId";
$res = db_query($qry, $cnnct);

chdir(SUBMIT_DIR.'/final');

// create a tar with temporary name
$tar_object = new Archive_Tar("all_in_one.tmp.tar");
$tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br />\n");// print errors

while ($row=mysql_fetch_row($res)) {
  $subName = $row[0].'.'.$row[1];
  if (!($tar_object->addModify($subName, "camera"))) {
    error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
	      3, '../log/'.LOG_FILE);
  }
}

// backup old tar (if exist) and rename new tar to premanent name
if (file_exists("all_in_one.tar.bak")) unlink("all_in_one.tar.bak");
if (file_exists("all_in_one.tar")) rename("all_in_one.tar", "all_in_one.tar.bak");

if (!rename("all_in_one.tmp.tar", "all_in_one.tar")) {
    error_log(date('Ymd-His: ')."rename(all_in_one.tmp.tar, all_in_one.tar) failed\n", 3, './log/'.LOG_FILE);
}

header("Location: index.php");
?>
