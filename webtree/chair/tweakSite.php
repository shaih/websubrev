<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$sender = defined('EML_SENDER') ? EML_SENDER : '';
$suggestSndr = $_SERVER['SERVER_ADMIN'];

$chkLF = $chkCRLF = $chkXmlr = $chkXprm = '';

if (CONF_FLAGS & FLAG_EML_HDR_CRLF)     $chkLF   = ' checked="checked"';
else                                    $chkCRLF = ' checked="checked"';

if (CONF_FLAGS & FLAG_EML_HDR_X_MAILER) $chkXmlr = ' checked="checked"';
if (CONF_FLAGS & FLAG_EML_EXTRA_PRM)    $chkXprm = ' checked="checked"';

if (isset($_GET['tweaked'])) 
     $confirm = '<center color="red">Changes Recorded</center>';
else $confirm = '';

if (defined('TIME_SHIFT') && TIME_SHIFT>0) 
     $timeShift = TIME_SHIFT;
else $timeShift = '';

$links = show_chr_links();print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<title>Tweak Site Settings</title>

<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<style type="text/css">
tr {vertical-align: top;}
h1 {text-align: center;}
</style>
</head>

<body>
$links
<hr/>
$confirm
<h1>Tweak Site Settings</h1>

This form is used to tweak some settings of the site, which is sometimes needed
to work around server bugs or environment problems. It is recommended that
you do not mess with these settings unless you know what you are doing, see
the <a href="../documentation/chair.html#emailSettings" target="documentation">
documentation</a>.<br/>

<form accept-charset="utf-8" action=doTweakSite.php enctype="multipart/form-data" method=POST>
<h3>Server time</h3>
<ul>
<li>Add <input name="timeShift" type="number" value="$timeShift"\> seconds
to the server&prime;s time (put a negative number to subtract).<br/>
If the server&prime;s clock is off, you can specify a time-shift amount that will be added to the dates that are displayed by the software.</li>
</ul>
<h3>Email settings</h3>
<ul>
<li>Separate header lines by
    <input name="emlCrlf" value=0 type="radio"{$chkCRLF}>
    <tt>"\\r\\n"</tt> &nbsp; or by
    <input name="emlCrlf" value=1 type="radio"{$chkLF}><tt>"\\n"</tt>.<br/>
    The default is <tt>"\\r\\n"</tt>. Change it only if you know that the
    server has a bug in the way it handles email headers.<br/><br/>
</li>
<li><input type=checkbox name=xMailer{$chkXmlr}>
  Send header line <tt>"X-Mailer: PHP/version"</tt> with each message.<br/>
  Sending this header line is considered standard net etiquette, but some
  spam-filters out there mark as spam anything that has this header line.
  UNcheck this box if this causes email loss.<br/><br/>
</li>
<li>Specify the <tt>Sender:</tt> <input type=text size=40
  name=sender value="$sender"> (suggested name: <tt>$suggestSndr</tt>).<br/>
  If you know how the web-server is calling itself on email messages that
  it sends, you can specify it here. This is often something like
  <tt>userName@machineName</tt> where <tt>userName</tt> is
  the username of the web-server (e.g., <tt>www</tt>, <tt>apache</tt>,
  <tt>admin</tt>, etc.) and <tt>machineName</tt> is either the DNS name
  of the machine (e.g., <tt>www.mit.edu</tt>) or sometimes just
  <tt>localhost</tt>.
  <br/><br/>
  <b>Explanation:</b>
  When the web-server sends email, the name of the web-server itself is set
  as the "envelope sender" of this message. It was observed that some ISPs
  filter as spam email messages where the <tt>From:</tt> header line does not
  match the "envelope sender". Hopefully specifying a matching <tt>"Sender:"
  </tt> line would prevent these emails from being discarded.<br/><br/>
</li>

EndMark;

if (!ini_get('safe_mode')) { print <<<EndMark
<li><input type=checkbox name=emlExtraPrm{$chkXprm}>
Specify the "envelope sender" in the mail function.<br/>
Sometimes you can explicitly specify the "envelope sender" to be used, by
supplying the parameter <tt>"-f name-to-use"</tt> to the PHP mail function.
This only works on (some) Unix systems, and does not work when PHP is in "safe
mode". Also, using this option typically adds an <tt>"X-Warning:"</tt> header
line to the message, which can again increase the odds of this message being
discarded en-route.
</li>

EndMark;
}

print <<<EndMark
</ul>
<input type=submit name=tweakSettings value="Submit Changes">
</form>

<hr/>
$links
</body>
</html>
EndMark;

?>