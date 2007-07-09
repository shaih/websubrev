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
$whatSubs = (PERIOD>=PERIOD_CAMERA) ? 'accepted' : '';

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<style type="text/css">
h1 {text-align: center;}
</style>
<title>Send Email to Authors of Submissions to $cName</title>
</head>
<body>
$links
<hr />
<h1>Send Email to Authors of Submissions to $cName</h1>
<form name="emailToAuthors" action="doEmailAuthors.php"
      enctype="multipart/form-data" method="post">

Subject: <input type=text size=80 name=subject><br/><br/>
You can use the keywords <tt><\$subId>, <\$subPwd>, <\$authors>,</tt>
and <tt><\$title></tt> in the message body. They will be replaced by the
authors, title and the submission-ID and password as they appear in the
database. (To be recognized as keywords, these words MUST include the '<'
and '>' characters and the dollar-sign.)<br/>
<textarea cols=80 rows=20 name=message></textarea><br/>
<br/>
Click <input type="submit" value="Send email"> to send the message
above to the authors of all the $whatSubs submissions. 
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
