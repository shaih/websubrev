<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
require 'header.php'; // brings in the contacts file and utils file

$confName = CONF_SHORT . ' ' . CONF_YEAR;

if (is_array($confFormats) && count($confFormats)>0) {
  $supportedFormats = '';
  foreach ($confFormats as $ext => $f) {
    $supportedFormats .= $ext . ", ";
  }
  if (strlen($supportedFormats)>100) { // don't display long lines in the form
    $supportedFormats = '<a href="index.php#formats" title="'
      .$supportedFormats.'">click for details</a>';
  }
  else {
    $supportedFormats .= '<a href="index.php#formats">click for details</a>';
  }
} else { // no formats were specified
  $supportedFormats = 'none specified';
}

// If the conference use pre-registration, then use the current form
// for registration and do not require to upload a submission file.
// Otherwise uploading submission file is required
if (!USE_PRE_REGISTRATION) {
  $ddline = SUBMIT_DEADLINE;
  $submitBtn = 'Submit';
  $testForFile = 'if (pat.test(form.sub_file.value)) { st |= 16; }';
  $subFileLine =<<<EndMark
  <tr>
    <td style="text-align: right;">
	<small>(*)</small>&nbsp;Submission&nbsp;File: </td>
    <td><input name="sub_file" size="70" type="file"><br />
        The submission itself, in one of the supported formats
	($supportedFormats).
        <br />
    </td>
  </tr>
EndMark;
} else {
  $ddline = REGISTER_DEADLINE;
  if (PERIOD>PERIOD_PREREG) {
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
      $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			      $_SERVER['PHP_AUTH_PW'], chair_ids());
    if ($chair === false) {
      header("WWW-Authenticate: Basic realm=\"$confShortName\"");
      header("HTTP/1.0 401 Unauthorized");
      exit("<h1>Pre-registration Deadline Expired</h1>Please contact the chair.");
    }
  }
  $submitBtn = 'Register New Submission';
  $testForFile = $subFileLine = '';
}

$checkbox = "";
$checkbox_text = "";
if(defined("OPTIN_TEXT")) {
  $checkbox = "<input type='checkbox' name='optin' value='1'/>";
  $checkbox_text = OPTIN_TEXT;
}

$timeleft = show_deadline($ddline);                   // how much time is left
$subDdline = 'Deadline is '.utcDate('r (T)',$ddline); // when is the deadline

$chairNotice = (PERIOD>PERIOD_SUBMIT || (USE_PRE_REGISTRATION && PERIOD>PERIOD_PREREG))? "<b>Notice: only the PC chair can use this page after the deadline.</b><br/>\n": '';

$links = show_sub_links(3);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>

<style type="text/css">
tr { vertical-align: top; }
</style>

<script type="text/javascript" src="../common/validate.js"></script>
<script language="Javascript" type="text/javascript">
<!--
function checkform( form )
{
  var pat = /^\s*$/;
  // Checking that all the mandatory fields are present
  var st = 0;
  if (pat.test(form.title.value))    { st |= 1; }
  if (pat.test(form.authors.value))  { st |= 2; }
  if (pat.test(form.contact.value))  { st |= 4; }
  if (pat.test(form.abstract.value)) { st |= 8; }
  $testForFile

  if (st != 0) {
    alert( "You must specify all the fields that are marked with (*)" );
    if (st & 1) { form.title.focus(); }
    else if (st & 2) { form.authors.focus(); }
    else if (st & 4) { form.contact.focus(); }
    else if (st & 8) { form.abstract.focus(); }
    else if (st & 16) { form.sub_file.focus(); }
    return false;
  }
  return true ;
}
//-->
</script>

<title>New Submission to $confName</title>
</head>

<body>
$links
<hr/>
$chairNotice
<h1>New Submission to $confName</h1>
<h3 class=timeleft>$subDdline<br/>
$timeleft</h3>

<form name="submit" onsubmit="return checkform(this);" action="act-submit.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">

<table cellpadding="6">
<tbody>
  <tr><td></td>
    <td style="color: green; text-align: right">
     Mandatory fields are marked with a (*)</td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;Title:</td>
    <td><input name="title" size="90" type="string"><br/>
        The title of the paper that you are submitting</td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Authors:</td>
    <td><input name="authors" size="90" type="string"><br />
        Separate multiple authors with '<i>and</i>' (e.g., Alice First 
	<i>and</i> Bob T. Second <i>and</i> C. P. Third). <br />
    </td>
  </tr>

EndMark;
if (USE_AFFILIATIONS){
  print <<<EndMark
  <tr>
    <td style="text-align: right;">Affiliations:</td>
        <td><input name="affiliations" size="70" type="string">
  </tr>

EndMark;
}

print <<<EndMark
  <tr>
    <td style="text-align: right;"><small>(*)</small> Contact Email(s):</td>
    <td><input name="contact" size="70" type="string" onchange="return checkEmailList(this)"><br/>
    Comma-separated list with <b>at least one valid email address</b> of the form user@domain; for example:<br/>
    <tt>first-author@university.edu, secondAuthor@company.com, third.one@somewhere.org</tt><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Abstract:</td>
    <td><textarea name="abstract" rows="15" cols="80"></textarea><br />
        Use only plain ASCII and LaTeX conventions for math, but no HTML tags.
        <br />
    </td>
  </tr>
$subFileLine

EndMark;

if (is_array($categories) && (count($categories)>1)) {
  print '  <tr>
    <td style="text-align: right;">Category:</td>
    <td><select name="category">
        <option selected="selected" value="None">(no category)</option>'."\n";
  foreach ($categories as $c) {
    print "        <option value=\"$c\">$c</option>\n";
  }
  print "      </select>\n    </td>\n  </tr>\n";
}

print <<<EndMark
<tr>
    <td style="text-align: right;">Keywords:</td>
    <td><input name="keywords" size="90" type="string"><br /></td>
  </tr>
  <tr>
    <td style="text-align: right;">Comments to Chair: </td>
    <td><textarea name="comment" rows="4" cols="80"></textarea><br />
        This message will only be seen by the program chair(s).
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">$checkbox</td>
    <td>$checkbox_text</td>
  </tr>
  <tr>
    <td></td>
    <td><input value="$submitBtn" type="submit">
    </td>
  </tr>
</tbody>
</table>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
