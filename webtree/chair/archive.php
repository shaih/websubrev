<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (defined('CAMERA_PERIOD')) exit("<h1>Review Site is Closed</h1>");

// Remove archive files, if exist
if (isset($_GET['removeTGZ'])) {
  if (file_exists(SUBMIT_DIR."/all_in_one.tgz")) {
    unlink(SUBMIT_DIR."/all_in_one.tgz");
    exit("<h1>Archive file all_in_one.tgz removed</h1>\n<a href=\".\">Back to main page</a>");
  }
  else exit("<h1>Archive file all_in_one.tgz does not exist</h1>\n<a href=\".\">Back to main page</a>");
}
if (isset($_GET['removeZIP'])) {
  if (file_exists(SUBMIT_DIR."/all_in_one.zip")) {
    unlink(SUBMIT_DIR."/all_in_one.zip");
    exit("<h1>Archive file all_in_one.zip removed</h1>\n<a href=\".\">Back to main page</a>");
  }
  else exit("<h1>Archive file all_in_one.zip does not exist</h1>\n<a href=\".\">Back to main page</a>");
}
if (isset($_GET['removeTAR'])) {
  if (file_exists(SUBMIT_DIR."/all_in_one.tar")) {
    unlink(SUBMIT_DIR."/all_in_one.tar");
    exit("<h1>Archive file all_in_one.tar removed</h1>\n<a href=\".\">Back to main page</a>");
  }
  else exit("<h1>Archive file all_in_one.tar does not exist</h1>\n<a href=\".\">Back to main page</a>");
}


// Try to automatically generate an archive file
if (isset($_GET['makeTar'])){ 
  $fileName = NULL;

  // Try using exec('tar') or exec('zip')
  $fileName = SYSmkTar();

  if (!isset($fileName)) {
    // Try to use the PEAR library, if present
    if (($fp = @fopen('Archive/Tar.php', 'r', 1)) and fclose($fp)) {
      $fileName = PEARmkTar();
    }
  }

  if (isset($fileName)) exit("<h1>Archive file $fileName created</h1>\n<a href=\".\">Back to main page</a>");
  else exit("<h1>Failed to create archive file</h1>\n<a href=\".\">Back to main page</a>");
}


/**** if no parameter is given - display links to the possible actions ****/
$subDir = SUBMIT_DIR;
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Manage archive files</title>
</head>
<body>
$links
<hr />
<h1>Manage archive files</h1>
Use this page to generate an archive file that contains all the
submissions, so reviewers do not have to download each one individually.

<h2>Automatically creating archive files</h2>
This script will attempt to use the utility program <tt>tar</tt>, and
failing that it will try to use the program <tt>zip</tt>. Failing both,
it will attempt to use the Archive_Tar package (which is part of
PEAR) to automatically generate the archive file.
<ul>
<li>If support for compression is installed on the server, then you
    can generate a gzipped tar file called <i>all_in_one.tgz</i> by
    <a href="archive.php?makeTar=yes">following this link</a>.</li>

<li>If the link above does not work, you can try to generate a 
    non-compressed tar file called <i>all_in_one.tar</i> by
    <a href="archive.php?makeTar=yes&noZip=no">following this
    alternate link</a>.</li>
</ul>
You can test the resulting archive file by <a href="../review/">going to
the review page</a> and looking for a link to "Download submissions
in one file". That link will point to the file <i>all_in_one.tgz</i> if it
exists, and failing that it will first try <i>all_in_one.zip</i> and then
<i>all_in_one.tar</i>. (If none of these files exist, the link will not
show on the review page.)

<h2>Manually creating archive files</h2>
If the above methods for creating an archive file do not work, and if you
have administrative access to the server, you can manually create an archive
file in one of these three formats and put it in the submission directory
(which is <code>$subDir</code>). The file must be called either
<i>all_in_one.tgz</i>, <i>all_in_one.zip</i>, or <i>all_in_one.tar</i>.
<br/><br/>
As a last resort, if you do not have have administrative access to the
server, you can download all the submission files to your local machine,
then create the archive file locally and upload it to the server. The
extension of the local archive file must be either .tgz, .tar, or .zip
for this method to work.<br/> 
<form accept-charset="utf-8" action="uploadArchive.php" enctype="multipart/form-data" method="post">
<input type=hidden name="MAX_FILE_SIZE" value=200000000>
Local archive file:<input name=sub_archive size=50 type="file">
<input name=submit type=submit value="Upload">
</form>

<h2>Removing archive files</h2>
If you want to delete archive files, follow one of these links to 
	 <a href="archive.php?removeTGZ=yes">delete all_in_one.tgz</a>,
	 <a href="archive.php?removeZIP=yes">delete all_in_one.zip</a>,
or       <a href="archive.php?removeTAR=yes">delete all_in_one.tar</a>
(if they exist).

<hr />
$links
</body>
</html>
EndMark;
?>

<?php // functions
function PEARmkTar()
{
  require_once 'Archive/Tar.php';
  global $SQLprefix;

  // Notice: the database query must be called BEFORE the chdir command
  // for its error reporting to work

  $qry = "SELECT subId, format, auxMaterial FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER by subId";
  $res = pdo_query($qry);

  chdir(SUBMIT_DIR);

  if (isset($_GET['noZip'])) {
    $tarFileName = "all_in_one.tar";
    $tar_object = new Archive_Tar($tarFileName); 
  } else {
    $tarFileName = "all_in_one.tgz";
    $tar_object = new Archive_Tar($tarFileName, true);
  }
  $tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br />\n");// print errors

  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $subName = $row[0].'.'.$row[1];
    if (!($tar_object->addModify($subName, "submissions"))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
		3, LOG_FILE);
      return NULL;
    }
    $subName = $row[0].'.aux.'.$row[2];
    if (!($tar_object->addModify($subName, "submissions"))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
		3, LOG_FILE);
      return NULL;
    }
  }
  return $tarFileName;
}

function SYSmkTar()
{
  global $SQLprefix;
  $qry = "SELECT subId, format, auxMaterial FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER by subId";
  $res = pdo_query($qry);

  $submissions = '';
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $submissions .= $row[0].'.'.$row[1].' '
                  . $row[0].'.aux.'.$row[2].' ';
  }
  if (empty($submissions)) return NULL;

  chdir(SUBMIT_DIR);
  if (isset($_GET['noZip'])) {
    $tarCmd = 'tar -cf';
    $fileName = "all_in_one.tar";
  } else {
    $tarCmd = 'tar -zcf';
    $fileName = "all_in_one.tgz";
  }

  // Try to create a tar file
  $return_var = 0;
  $output_lines = array();
  $ret=exec("$tarCmd $fileName $submissions", 
       $output_lines, $return_var); // execute the command

  // If failed, try to create a zip file instead
  if ($ret===false || $return_var != 0) {
    $fileName = "all_in_one.zip";
    $return_var = 0;
    $ret=exec("zip $fileName $submissions", $output_lines, $return_var);
  }

  return ($ret!==false && $return_var==0) ? $fileName : NULL;
}
?>
