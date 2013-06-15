<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD<PERIOD_CAMERA) exit('<h1>Final-verions are not available yet</h1>');

// check if the PEAR package Archive_Tar is available
$pearAvailable = (($fp=@fopen('Archive/Tar.php', 'r', 1)) && @fclose($fp));

/*********************************************************************
 If PEAR is not available then try to use the system tar/zip utilities.
 In this case the archive will have the default format, reflecting the
 structure of the submit/final directory: all files are in the same
 directory, and they are named by their submission number. 

 If PEAR is allow also vailable then enable also the LNCS format:
 Namely, each submission file is in a separate subdirectory XXXXnnnn,
 where XXXX is the volume number and nnnn is the first page of this
 submission within the volume. We first ask the chair what format
 he/she wants to use, and then create the archive file accordingly.
**********************************************************************/

// Display a page asking the chair what format to use
if ($pearAvailable && !isset($_GET['format'])) {
  $links = show_chr_links();
  print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8"><title>Create Camera-Ready Archive</title>
<script language="Javascript" type="text/javascript">
<!--
function checklncs() { // check the radio button for lncs
  document.getElementById('lncs').checked=true;
  return true;
}
// -->
</script>
</head>
<body>
$links
<hr/>
<h1 align=center>Create Camera-Ready Archive</h1>
You can create the archive in one of two formats:
<ul>
<li><i>Default:</i> All the camera-ready files are in one directory.<br/>
<br/></li>
<li><i>LNCS:</i> Each file resides in its own sub-directory, and the
sub-directory is named <i>XXXXnnnn</i> where <i>XXXX</i> is the volume
number and <i>nnnn</i> is the first page of that submission.
(For example, <i>40270015</i> for volume number 4027 and a paper that
begins on page 15).<br/>
<br/>
Page numbers are calculated when you generate the LaTeX preface template.
Accepted submissions for which the page number is not yet set, as well
as the preface of the volume (if you uploaded it to the server), will
be placed in the sub-directory <i>XXXX0000</i>.
</li>
</ul>

<form accept-charset="utf-8" action=cameraArchive.php method=GET>
<input type=radio name=format value="default" checked> Use the default format<br/>
<input type=radio name=format value="lncs" id=lncs> Use LNCS format with volume number <input type=text name=volume size=5 onfocus="return checklncs();"><br/>
<input type=submit>
</form>
<hr/>
$links
</body></html>
EndMark;
  exit();
}

$ext = SYSmkTar();     // Use system utilities, is possible
if (empty($ext) && $pearAvailable) 
  $ext = PEARmkTar();  // otherwise try PEAR if available
if (empty($ext)) die('Failed to create an archive file');

// backup old file (if exists) and rename new tar to premanent name
if (file_exists("all_in_one.$ext.bak")) unlink("all_in_one.$ext.bak");
if (file_exists("all_in_one.$ext")) rename("all_in_one.$ext", "all_in_one.$ext.bak");
 
if (!rename("all_in_one.tmp.$ext", "all_in_one.$ext")) {
  error_log(date('Ymd-His: ')."rename(all_in_one.tmp.$ext, all_in_one.$ext) failed\n", 3, LOG_FILE);
  exit("<h1>Cannot rename all_in_one.tmp.$ext to all_in_one.$ext</h1>\nContact the administrator.\n<a href=\".\">Back to main page</a>");
}
exit("<h1>Archive file all_in_one.$ext created</h1>\n<a href=\".\">Back to main page</a>");

function SYSmkTar()
{
  global $SQLprefix;
  $qry = "SELECT subId, format FROM {$SQLprefix}submissions WHERE status='Accept' ORDER by subId";
  $res = pdo_query($qry);

  $submissions = '';
  while ($row=$res->fetch(PDO::FETCH_NUM)) {
    $submissions .= $row[0].'.'.$row[1].' ';
  }
  if (empty($submissions)) return NULL;

  // Try to create a tar file
  chdir(SUBMIT_DIR.'/final');
  $return_var = 0;
  $output_lines = array();
  if (file_exists("all_in_one.tmp.tar")) unlink("all_in_one.tmp.tar");
  $ret=exec("tar -cf all_in_one.tmp.tar $submissions", 
       $output_lines, $return_var); // execute the command
  if (file_exists("all_in_one.tmp.tar")) return 'tar';

  // If failed, try to create a zip file instead
  $return_var = 0;
  if (file_exists("all_in_one.tmp.zip")) unlink("all_in_one.tmp.zip");
  $ret=exec("zip all_in_one.tmp.zip $submissions", $output_lines, $return_var);
  if (file_exists("all_in_one.tmp.zip")) return 'zip';

  return '';
}

function PEARmkTar()
{
  require_once 'Archive/Tar.php';
  global $SQLprefix;

  // $lncs is defined if we need touse the LNCS format
  $lncs = (isset($_GET['format']) && $_GET['format']=='lncs')?
    intval($_GET['volume']) : NULL;
  
  $qry = "SELECT s.subId,s.format,a.pOrder,a.nPages FROM {$SQLprefix}submissions s, {$SQLprefix}acceptedPapers a WHERE s.status='Accept' AND a.subId=s.subId ORDER by " . (isset($lncs)? "a.pOrder,s.subId" : "s.subId");
  $res = PDO_query($qry);
  // create a tar with temporary name
  chdir(SUBMIT_DIR.'/final');
  if (file_exists("all_in_one.tmp.tar")) unlink("all_in_one.tmp.tar");
  $tar_object = new Archive_Tar("all_in_one.tmp.tar");
  $tar_object->setErrorHandling(PEAR_ERROR_PRINT,"%s<br/>\n");// print errors

  // Add the preface to the archive file (if available)
  $dir = isset($lncs)? ($lncs.'0000'): 'final';
  foreach (array('tar','zip','tgz') as $suffix) {
    $subName = "preface." . $suffix;
    if (file_exists($subName) && !($tar_object->addModify($subName,$dir))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add file $subName to tar file",
		3, LOG_FILE);
      return '';
    }
  }

  // Add all the submissions to the tar file
  $curPage = 1;
  while ($row=$res->fetch(PDO::FETCH_ASSOC)) {
    $subName = $row['subId'].'.'.$row['format'];
    if (isset($lncs)) { // use lncs convention
      if ($row['pOrder']>0) { // paper has defined order in the program
	$dir = $lncs . sprintf("%04d",$curPage);
	$curPage += $row['nPages'];
      }
      else $dir = $lncs . '0000';
    }
    if (!($tar_object->addModify($subName,$dir))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add file $subName to tar file",
		3, LOG_FILE);
      return '';
    }
  }

  return 'tar';
}
?>
