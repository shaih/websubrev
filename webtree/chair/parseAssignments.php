<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
function parse_assignment_file($tmpFile,$cnnct)
{
  $fName = SUBMIT_DIR."/scratch/sketchAssign_".date('is');
  if (!move_uploaded_file($tmpFile,$fName)) {
    error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fName) failed\n", 3, LOG_FILE);
    die("Cannot move assignment file");
  }
  if (!($fd=fopen($fName,'r'))) die("Could not open assignment file");

  db_query("UPDATE assignments SET sktchAssgn=0 WHERE sktchAssgn!=-1", $cnnct);
  $subId = -1;
  while (!feof($fd) && ($line=fgets($fd))!==false) { // Read next line
    if (substr($line,0,10)=='Submission') {
      $pos=strpos($line,':',11);
      if ($pos===false) { $subId = -1; continue; }
      $subId = intval(trim(substr($line,11,$pos-11)));
    }
    if ($subId<=0) continue;
    if (substr($line,0,8)=='Reviewer') {
      $pos=strpos($line,':',9);
      if ($pos===false) continue;
      $revId = intval(trim(substr($line,9,$pos-9)));
      if ($revId<=0) continue;

      // record this assignment (but do not overwrite conflicts)
      db_query("INSERT IGNORE INTO assignments SET subId=$subId,revId=$revId,sktchAssgn=1", $cnnct);
      db_query("UPDATE assignments SET sktchAssgn=1 WHERE subId=$subId AND revId=$revId AND sktchAssgn!=-1", $cnnct);
    }
  }
  fclose($fd);
  unlink($fName);
}
?>