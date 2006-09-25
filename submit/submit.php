<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
require 'header.php'; // brings in the contacts file and utils file

if (defined('CAMERA_PERIOD')) exit("<h1>Submission Deadline Expired</h1>");

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$deadline = show_deadline(SUBMIT_DEADLINE);

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

$links = show_sub_links(3);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>

<style type="text/css">
tr { vertical-align: top; }
</style>

<script language="Javascript" type="text/javascript">
<!--
function check_email( fld )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  var pat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
  if ((fld.value != "") && (pat.test(fld.value)==false)) {
    alert("Not a valid email format");
    fld.focus();
    fld.select();
    return false ;
  }
  return true ;
}
function checkform( form )
{
  var pat = /^\s*$/;
  // Checking that all the mandatory fields are present
  var st = 0;
  if (pat.test(form.title.value))    { st |= 1; }
  if (pat.test(form.authors.value))  { st |= 2; }
  if (pat.test(form.contact.value))  { st |= 4; }
  if (pat.test(form.abstract.value)) { st |= 8; }
  if (pat.test(form.sub_file.value)) { st |= 16; }

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
<hr />
<h1>New Submission to $confName</h1>
<h3 class=timeleft>$deadline</h3>

<form name="submit" onsubmit="return checkform(this);" action="act-submit.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">

<table cellpadding="6">
<tbody>
  <tr><td></td>
    <td style="color: green; text-align: right">
     Mandatory fields are marked with a (*)</td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Title:</td>
    <td><input name="title" size="90" type="string"><br/>
        The title of your submission</td>
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
    <td style="text-align: right;"><small>(*)</small> Contact Email:</td>
    <td><input name="contact" size="70" type="string"
         onchange="return check_email(this)"><br />
        Must be <b>one valid email address</b> of the form user@domain;
        will be used for communication. <br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Abstract:</td>
    <td><textarea name="abstract" rows="15" cols="80"></textarea><br />
        Use only plain ASCII and LaTeX conventions for math, but no HTML tags.
        <br />
    </td>
  </tr>
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
    <td></td>
    <td><input value="Submit" type="submit">
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
