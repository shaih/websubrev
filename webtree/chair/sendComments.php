<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");
$cName = CONF_SHORT.' '.CONF_YEAR;

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
}

</style>
<title>Send Comments to Authors</title>
</head>
<body>
$links
<hr />
<h1>Send Comments to Authors</h1>
<h2>$cName</h2>
The comments-for-authors will be send when you hit the "Send Comments"
button at the bottom of this page. You can customize the header of
these emails below. You can include in the text any of the keywords
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
<form accept-charset="utf-8" name="sendComments" action="doEmailAuthors.php"
  enctype="multipart/form-data" method="post">
Subject: <input type=text size=80 name=subject value="Reviewer comments for $cName submission &lt;&#36;subId&gt;">
<br/>
<textarea cols=80 rows=20 name=message>Dear &lt;&#36;authors&gt,

Below please find the reviewer comments on your paper

  "&lt;&#36;title&gt;"

That was submitted to $cName Thank you again for submitting
your work to $cName.

Sincerely,

The program chair(s)
************************************************************************

&lt;&#36;comments&gt;
</textarea>

<h3>Who to send this email to</h3>
<ul>
<li>
<input type="radio" ID="send2all" name="emailTo" value="all" checked="true">
Authors of all submissions<br/><br/>
</li>
<li>
Only submissions with status:
  <input type="radio" ID="send2AC" name="emailTo" value="AC"> AC,
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
<p><input type="submit" value="Send email">
<br/><input name="withGrades" type="checkbox" value="true">
Check to include score and confidence in the email sent to the authors<br/>
EndMark;

if (!active_rebuttal()) print <<<EndMark

<input name="allowFeedback" type="checkbox" value="true">
Check to let authors provide feedback on the reviews. 
  <b><span style="color:red">This is NOT MEANT FOR REBUTTAL!!</span> This option should only be used for the final reviews, after all the decisions are made</b>, see <a href="../documentation/chair.html#feedback">documentation</a>.

EndMark;
print <<<EndMark
</p>
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
