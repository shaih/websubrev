<?php
/* Web Submission and Review Software, version 0.51
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
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Submission Error log</title>
</head>
<body>
$links
<hr />
<h1 style="text-align: center;">Submission Error log</h1>
<pre>

EndMark;

if (file_exists('log/'.LOG_FILE))
     $lines = file('log/'.LOG_FILE); // read file into array
if (is_array($lines)) foreach ($lines as $line) {
   print htmlspecialchars($line);
}
else print "No errors recorded";

print <<<EndMark
</pre>
<hr />
$links
</body>
</html>
EndMark;
?>
