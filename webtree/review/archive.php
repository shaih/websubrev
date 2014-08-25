<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, William Blair, Adam Udi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';

$revId = $pcMember[0];

if(!(isset($_POST['download']) && is_array($_POST['download'])))
  exit("<h1>No Submissions Specified</h1>");

// Sanitize
$subs = array();
foreach($_POST['download'] as $id) {
  $iid = intval($id);
  if ($id == $iid) $subs[] = $iid;
}

// Keep only these submissions that do not have conflict
$qry = "SELECT s.subId, s.format, s.auxMaterial FROM {$SQLprefix}submissions s
   LEFT JOIN {$SQLprefix}assignments a ON a.subId=s.subId
   WHERE !(a.revId=? AND a.assign<0) AND s.subId IN("
  .implode(",",$subs).") GROUP BY s.subId";

$res = pdo_query($qry, array($revId));

// Test that the submissions files are there
$files = array();
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $fileName = $row['subId'].".".$row['format'];
  if(file_exists(SUBMIT_DIR."/$fileName"))
    $files[] = $fileName;
  $fileName = $row['subId'].".aux.".$row['auxMaterial'];
  if(file_exists(SUBMIT_DIR."/$fileName"))
    $files[] = $fileName;
}
if (empty($files))
 exit("<h1>No Submissions Found</h1>");

$path = sha1($revId.CONF_SHORT.CONF_YEAR);    // One name per PC member
$path = SUBMIT_DIR."/scratch/".alphanum_encode(substr($path,0,15));

$fileName = SYSmkTar($files, $path); // Try to use tar or zip utility
if (empty($fileName)) {    // Try to use the PEAR library, if present
  if (($fp = @fopen('Archive/Tar.php', 'r', 1)) and fclose($fp)) {
    $fileName = PEARmkTar($files, $path);
  }
}

if (empty($fileName)) // failed
  exit("<h1>Cannot create archive file</h1>");

// Let the use download this file
$ext = substr($fileName,-3);
if ($ext=="tar" || $ext=='tgz') $mime = 'x-tar';
elseif ($ext=="zip") $mime = 'x-zip';
else exit("<h1>Unknown archive format</h1>");

if (file_exists($fileName)) {
  header("Content-Type: application/$mime");
  header("Content-Disposition: inline; filename=\"download.$ext\"");
  header('Content-Transfer-Encoding: binary');
  header('Content-Length: ' . filesize($fileName));
  ob_clean();
  flush();
  @readfile($fileName);
  @unlink($fileName);
  exit;
}
else exit("<h1>Error readng file</h1>");

function SYSmkTar($files,$path)
{
  chdir(SUBMIT_DIR);
  $files = implode(" ",$files);
  $output_lines = array();

  // Try to create a tar file
  $fileName = "$path.tar";
  @unlink($fileName);
  $return_var = 0;
  $ret=exec("tar -cf $fileName $files",$output_lines,$return_var);

  // If failed, try to create a zip file instead
  if ($ret===false || $return_var != 0) {
    $fileName = "$path.zip";
    @unlink($fileName);
    $return_var = 0;
    $ret=exec("zip $fileName $files", $output_lines, $return_var);
  }

  return ($ret!==false && $return_var==0) ? $fileName : NULL;
}

function PEARmkTar($files,$path)
{
  require_once 'Archive/Tar.php';
  chdir(SUBMIT_DIR);

  $fileName = "$path.tgz";
  $tar_object = new Archive_Tar($fileName, true);

  $tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br/>\n");// print errors

  foreach ($files as $subName) {
    if (!($tar_object->addModify($subName, "submissions"))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
		3, LOG_FILE);
      return NULL;
    }
  }
  return $fileName;
}
