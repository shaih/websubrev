<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (!defined('CAMERA_PERIOD'))
  exit('<h1>Final-verions are not available yet</h1>');

// Use PEAR is available
$ext = '';
if (($fp = @fopen('Archive/Tar.php', 'r', 1)) and fclose($fp)) {
  $ext = PEARmkTar();
}

// Otherwise try to use system programs
if (empty($ext))
  $ext = SYSmkTar();

// backup old file (if exist) and rename new tar to premanent name
if (!empty($ext)) {
  if (file_exists("all_in_one.$ext.bak")) unlink("all_in_one.$ext.bak");
  if (file_exists("all_in_one.$ext")) rename("all_in_one.$ext", "all_in_one.$ext.bak");
 
  if (!rename("all_in_one.tmp.$ext", "all_in_one.$ext")) {
    error_log(date('Ymd-His: ')."rename(all_in_one.$ext.tar, all_in_one.$ext) failed\n", 3, '../../log/'.LOG_FILE);
  }  
}

header("Location: index.php");
exit();

function SYSmkTar()
{
  $cnnct = db_connect();
  $qry = "SELECT subId, format from submissions WHERE status='Accept'
  ORDER by subId";
  $res = db_query($qry, $cnnct);

  $submissions = '';
  while ($row=mysql_fetch_row($res)) {
    $submissions .= $row[0].'.'.$row[1].' ';
  }
  if (empty($submissions)) return NULL;

  // Try to create a tar file
  chdir(SUBMIT_DIR.'/final');
  $return_var = 0;
  $output_lines = array();
  $ret=exec("tar -cf all_in_one.tmp.tar $submissions", 
       $output_lines, $return_var); // execute the command

  if ($ret!==false && $return_var == 0) // success
    return 'tar';

  // If failed, try to create a zip file instead
  if ($ret===false || $return_var != 0) {
    $return_var = 0;
    $ret=exec("zip all_in_one.tmp.zip $submissions", $output_lines, $return_var);
  }

  return ($ret!==false && $return_var == 0) ? 'zip' : '';
}

function PEARmkTar()
{
  require_once 'Archive/Tar.php';
  $cnnct = db_connect();
  $qry = "SELECT subId, format from submissions WHERE status='Accept'
  ORDER by subId";
  $res = db_query($qry, $cnnct);

  // create a tar with temporary name
  chdir(SUBMIT_DIR.'/final');
  $tar_object = new Archive_Tar("all_in_one.tmp.tar");
  $tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br />\n");// print errors

  while ($row=mysql_fetch_row($res)) {
    $subName = $row[0].'.'.$row[1];
    if (!($tar_object->addModify($subName, "final"))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
		3, '../../log/'.LOG_FILE);
      return '';
    }
  }
  return 'tar';
}
?>
