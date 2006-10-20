<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (defined('REVIEW_PERIOD')) { exit("<h1>Submission Site is Closed</h1>"); }

$longName    = CONF_NAME;
$shortName   = CONF_SHORT;
$confYear    = CONF_YEAR;
$confURL     = CONF_HOME;
$chairEml    = CHAIR_EMAIL;
$adminEml    = ADMIN_EMAIL;
$subDeadline = utcDate('r (T)', SUBMIT_DEADLINE);
$cmrDeadline = utcDate('r (T)', CAMERA_DEADLINE);
$emlCrlf = addcslashes(EML_CRLF, "\r\n");
$emlExtraPrm =  EML_EXTRA_PRM;

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
<style type="text/css">
h1 { text-align: center; }
tr { vertical-align: top; }
</style>
<title>Managing Submissions Site</title></head>

<body>
$links
<hr/>
<h1>Managing Submissions Site</h1>
<form action=doManageSubmissions.php enctype="multipart/form-data" method=POST>
<table cellpadding=6><tbody>
<tr><td><big><b>The&nbsp;Conference:</b></big></td><td></td>
</tr>
<tr><td style="text-align: right;">Conference&nbsp;Name:</td>
  <td><input name=longName size=90 type=text value="$longName"></td>
</tr>
<tr><td style="text-align: right;">Short&nbsp;Name:</td>
  <td><input name=shortName size=30 type=text value="$shortName">
    e.g., BASKET, &nbsp; &nbsp; &nbsp; &nbsp;Year: <input name=confYear
    size=4 maxlength=4 type=text value="$confYear"></td>
</tr>
<tr><td style="text-align: right;">Conference&nbsp;URL:</td>
  <td><input name=confURL size=90 type=text value="$confURL"></td>
</tr>
<tr><td><big><b>Submission:</b></big></td><td><br></td>
</tr>
<tr><td style="text-align: right;">Submission&nbsp;Deadline:</td>
  <td><input name=subDeadline size=90 type=text value="$subDeadline"></td>
</tr>  
<tr><td style="text-align: right;">Camera&nbsp;ready&nbsp;Deadline:</td>
  <td><input name=cameraDeadline size=90 type=text value="$cmrDeadline"><br/>
  Remember that <b>the software does not enforce these deadlines automatically.
  </b></td>
</tr>  

EndMark;
$cats = $sc = '';
if (is_array($categories)) foreach ($categories as $c) {
  $cats .= "{$sc}{$c}";
  $sc = '; ';
}
$chkaff = USE_AFFILIATIONS ? 'checked="checked"' : '';
$chkanon = ANONYMOUS ? 'checked="checked"' : '';
print <<<EndMark
<tr><td style="text-align: right;">Categories:</td>
  <td><textarea name=categories rows=3 cols=70>$cats</textarea><br/>
    A semi-colon-separated list of categories for the submissions
    (empty list to forgo categories.)</td>
</tr>
<tr><td style="text-align: right;">Require&nbsp;Affiliations:</td>
  <td><input name=affiliations type=checkbox $chkaff>
      Check to require submitters to specify their affiliations</td>
</tr>
<tr><td style="text-align: right;">Anonymous&nbsp;Submissions:</td>
  <td><input name=anonymous type=checkbox $chkanon>
      Check to hide author names from the reviewers.
      <input name=subFlags type=hidden value=on></td>
</tr>

EndMark;
	
if (is_array($confFormats)) {
  print <<<EndMark
<tr><td style="text-align: right;">
    Supported Formats:<br/>(UNcheck to remove)
    <input type=hidden name=formats value=on></td>
  <td>

EndMark;
  foreach ($confFormats as $ext => $f) { // one row for each format
    print <<< EndMark
    <input name="keepFormats_{$ext}" type=checkbox checked="checked">
    <b>$f[0]</b> &nbsp;(.$ext, &nbsp;$f[1])<br/>

EndMark;
  }
}

print <<<EndMark
  </td>
</tr>
<tr><td style="text-align: right;">Add supported formats:</td>
  <td><textarea name=addFormats rows=3 cols=70></textarea><br/>
      A semi-colon-separated list of formats "<tt>Name1(ext1,MIME1);
      Name2(ext2, MIME2)...</tt>"</td>
</tr>
<tr><td colspan=2 style="text-align: center;"> 
  <input value="         Submit         " type="submit"></td>
</tr>
</tbody></table>
</form>
<hr/>
$links
</body></html>
EndMark;
?>
