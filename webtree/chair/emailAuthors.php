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
$chkAC = (PERIOD>=PERIOD_CAMERA)? ' checked="true"': '';

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
  h1 {text-align: center;}
</style>
<title>Send Email to Authors of Submissions to $cName</title>
</head>
<body>
$links
<hr />
<h1>Send Email to Authors of Submissions to $cName</h1>
You can include in the text any of the keywords
<code>&lt;&#36;authors&gt;</code>, 
<code>&lt;&#36;title&gt;</code>,
<code>&lt;&#36;subId&gt;</code>,
<code>&lt;&#36;subPwd&gt;</code>, and
<code>&lt;&#36;comments&gt;</code>, and they will be replaced by the
authors, title, submission-ID, password, and comments-to-authors as
they appear in the database. To be recognized as keywords, these words
MUST include the '&lt;' and '&gt;' characters and the dollar-sign.
The keywords <code>&lt;&#36;title&gt;</code> and
<code>&lt;&#36;subId&gt;</code> can also be included in the subject
line.<br/>
<br/>
<form accept-charset="utf-8" name="emailToAuthors" action="doEmailAuthors.php"
  enctype="multipart/form-data" method="post">
Subject: <input type=text size=80 name=subject><br/>
<textarea cols=80 rows=20 name=message></textarea>

<h3>Who to send this email to</h3>
<ul>
<li>
<input type="radio" ID="send2all" name="emailTo" value="all">
Authors of all submissions<br/><br/>
</li>
<li>
Only submissions with status:
  <input type="radio" ID="send2AC" name="emailTo" value="AC"{$chkAC}> AC,
  <input type="radio" ID="send2MA" name="emailTo" value="MA"> MA,
  <input type="radio" ID="send2DI" name="emailTo" value="DI"> DI, 
  <input type="radio" ID="send2NO" name="emailTo" value="NO"> NO,
  <input type="radio" ID="send2MR" name="emailTo" value="MR"> MR, 
  <input type="radio" ID="send2RE" name="emailTo" value="RE"> RE</input>
<br/><br/>
</li>
<li>
<input ID="send2these" type="radio" name="emailTo" value="these">
Only submissions with the IDs specified below:</br>
<input type="text" name="subIDs" size="100" onfocus="document.getElementById('send2these').checked = true">
</li>
</ul>
<p><input type="submit" value="Send email"></p>
</form>
<hr/>
$links
EndMark;
?>
</body>
</html>
