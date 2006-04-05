<?php
/* Web Submission and Review Software, version 0.51
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
    exit("<h1>Archive file all_in_one.tgz removed</h1>");
  }
  else exit("<h1>Archive file all_in_one.tgz does not exist</h1>");
}
if (isset($_GET['removeZIP'])) {
  if (file_exists(SUBMIT_DIR."/all_in_one.zip")) {
    unlink(SUBMIT_DIR."/all_in_one.zip");
    exit("<h1>Archive file all_in_one.zip removed</h1>");
  }
  else exit("<h1>Archive file all_in_one.zip does not exist</h1>");
}
if (isset($_GET['removeTAR'])) {
  if (file_exists(SUBMIT_DIR."/all_in_one.tar")) {
    unlink(SUBMIT_DIR."/all_in_one.tar");
    exit("<h1>Archive file all_in_one.tar removed</h1>");
  }
  else exit("<h1>Archive file all_in_one.tar does not exist</h1>");
}


// Try to automatically generate an archive file
if (isset($_GET['makeTar'])){ 
  require_once 'Archive/Tar.php';

  // Notice: the utility functions db_connect and db_query must be 
  // called BEFORE the chdir command for their error reporting to work

  $cnnct = db_connect();
  $qry = "SELECT subId, format from submissions WHERE status!='Withdrawn'
  ORDER by subId";
  $res = db_query($qry, $cnnct);

  chdir(SUBMIT_DIR);

  if (isset($_GET['noZip'])) {
    $tarFileName = "all_in_one.tar";
    $tar_object = new Archive_Tar($tarFileName); 
  } else {
    $tarFileName = "all_in_one.tgz";
    $tar_object = new Archive_Tar($tarFileName, true);
  }
  $tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br />\n");// print errors

  while ($row=mysql_fetch_row($res)) {
    $subName = $row[0].'.'.$row[1];
    if (!($tar_object->addModify($subName, "submissions"))) {
      error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
		3, '../log/'.LOG_FILE);
    }
  }

  exit("<h1>Tar file $tarFileName created</h1>");
}


/**** if no parameter is given - display links to the possible actions ****/
$subDir = SUBMIT_DIR;
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Manage archive files</title>
</head>
<body>
$links
<hr />
<h1>Manage archive files</h1>

<h2>Automatically creating archive files</h2>
If the PHP Archive_Tar package is installed on the server (which
is the case in all systems that I know of) then you can use it to 
automatically generate an archive file that contains all the submissions
so reviewers do not have to download each one individually.
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
If neither of the above methods for creating an archive file works,
and if you have administrative access to the server, you can manually
create an archive file in one of these three formats and put it in the
submission directory. (This is the directory <code>$subDir</code>
under the base directory where you installed this software package.)
The file must be called either <i>all_in_one.tgz</i>, <i>all_in_one.zip</i>,
or <i>all_in_one.tar</i>. 

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
