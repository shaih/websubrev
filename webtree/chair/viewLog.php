<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';
$links = show_chr_links(2);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<title>View Log File</title>
</head>
<body>
$links
<hr />
<h1 style="text-align: center;">View Log File</h1>
<pre>

EndMark;

if (!file_exists(LOG_FILE) || !readfile(LOG_FILE)) {
  exit("</pre><h1>Cannot Find Log File</h1>\n<hr/>$links</body></html>");
}

print <<<EndMark
</pre>
<hr/>
$links
</body>
</html>
EndMark;
?>
