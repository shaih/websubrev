<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)

if (isset($_GET['all_in_one'])) {
  $subId='all_in_one';
  $fmt = trim($_GET['all_in_one']);
} else {
  $subId = (int) $_GET['subId'];
  $cnnct = db_connect();
  $qry = "SELECT format FROM submissions WHERE subId=$subId";
  $res = db_query($qry, $cnnct);
  if (!$res || mysql_num_rows($res)==0) die("<h1>Submission not found</h1>");
  $row = mysql_fetch_row($res);
  $fmt = $row[0];
}

// Find the MIME type of this format
$mimeType = NULL;
if (is_array($confFormats) && isset($confFormats[$fmt]))
  $mimeType = $confFormats[$fmt][1];
else if ($fmt=='tar') $mimeType = 'application/x-tar';
else if ($fmt=='tar.gz') $mimeType = 'application/x-tar-gz';
else if ($fmt=='tgz') $mimeType = 'application/x-compressed-tar';
else if ($fmt=='zip') $mimeType = 'application/x-zip';

if (isset($mimeType))  header("Content-Type: $mimeType");


header("Content-Disposition: inline; filename=\"$subId.$fmt\"");

if (isset($_GET['final'])&&(PERIOD>=PERIOD_CAMERA)&&($pcMember[0]==CHAIR_ID)){
  $fileName = SUBMIT_DIR."/final/$subId.$fmt";
} else {
  $fileName = SUBMIT_DIR."/$subId.$fmt";
}
if (!file_exists($fileName) || !readfile($fileName)) {
  exit("<h1>Submission not found</h1>");
}
?>
