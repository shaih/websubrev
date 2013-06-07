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
if (PERIOD < PERIOD_FINAL) {
  exit("<h1>Cannot upload preface before the camera-ready deadline</h1>");
}

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
tr { vertical-align: top; }
</style>
<title>Uploading Preface/TOC/Author-index to the server</title>
</head>
<body>
$links
<hr/>
<h1>Uploading Preface/TOC/Author-index to the server</h1>
Use this form to upload to the server an archive file with the
preface, TOC, author-index, etc. You can use this option to have on the
server a complete mirror of the publication material that was sent to
the publisher (e.g., for archiving purposes).

The preface file should be prepared using the same conventions as
the other camera-ready files (possibly by strating from the template
that was generated with <a href="makeTOC.php">this form</a>). Prepare
an archive file (zip/tar/tgz) that includes all the source files that
were used to generate the peface, TOC, author-index, and anything else
that you want to upload, and then upload this one archive file to the
server using the form below.<br/>
<br/>
<form accept-charset="utf-8" action=doUploadPreface.php method=POST enctype="multipart/form-data">
<input type=hidden name="MAX_FILE_SIZE" value=200000000>
Preface archive file:<input name=preface_archive size=50 type="file">
<input name=submit type=submit value="Upload">
</form>

<hr/>
$links
</body>
EndMark;
?>
</html>
