<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

$longName    = CONF_NAME;
$shortName   = CONF_SHORT;
$confYear    = CONF_YEAR;
$confURL     = CONF_HOME;
$adminEml    = ADMIN_EMAIL;

$regDeadline = USE_PRE_REGISTRATION? utcDate('r (T)',REGISTER_DEADLINE): '';
$subDeadline = utcDate('r (T)', SUBMIT_DEADLINE);
$cmrDeadline = utcDate('r (T)', CAMERA_DEADLINE);
$emlCrlf = addcslashes(EML_CRLF, "\r\n");
$emlExtraPrm =  EML_EXTRA_PRM;

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
tr { vertical-align: top; }
</style>
<title>Managing Submission Site</title></head>

<body>
$links
<hr/>
<h1>Managing Submissions Site</h1>
<form accept-charset="utf-8" action=doManageSubmissions.php enctype="multipart/form-data" method=POST>
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
EndMark;

$cmrDeadlineHTML = '<tr><td style="text-align: right;">Camera&nbsp;ready&nbsp;Deadline:</td>
  <td><input name=cameraDeadline size=90 type=text value="'.$cmrDeadline.'"><br/>
  Remember that <b>the software does not enforce the deadlines automatically.</b>
</td></tr>';

if (PERIOD<=PERIOD_SUBMIT) {
  print <<<EndMark
<tr><td style="text-align: right;">Pre-registraion&nbsp;Deadline:</td>
  <td><input name=regDeadline size=45 type=text value="$regDeadline">
  (empty field means pre-registration is not required)</td>
</tr>
<tr><td style="text-align: right;">Submission&nbsp;Deadline:</td>
  <td><input name=subDeadline size=90 type=text value="$subDeadline"></td>
</tr>

EndMark;
  $cats = $sc = '';
  if (is_array($categories)) foreach ($categories as $c) {
    $cats .= "{$sc}{$c}";
    $sc = '; ';
  }
  $chkaff = USE_AFFILIATIONS ? 'checked="checked"' : '';
  $chkanon = ANONYMOUS ? 'checked="checked"' : '';
  $chkAux = (CONF_FLAGS & FLAG_AUX_MATERIAL)? 'checked="checked"' : '';
  $chkAuthConf = (CONF_FLAGS & FLAG_AUTH_CONFLICT)? 'checked="checked"' : '';
  $optIn = defined("OPTIN_TEXT")? OPTIN_TEXT: '';
  print <<<EndMark
$cmrDeadlineHTML
<tr><td style="text-align: right;">Categories:</td>
  <td><textarea name=categories rows=3 cols=70>$cats</textarea><br/>
    A semi-colon-separated list of categories for the submissions
    (empty list to forgo categories.)</td>
</tr>
<tr><td  style="text-align: right;">Opt In Text:</td>
  <td><textarea name="checktext" rows="3" cols="70">$optIn</textarea><br/>
    This text will be presented with a check box to the user when they 
    submit a paper. <br />You will then be able to see who opts in from the review
    section of the site. <br />Use this for something like eligibility for an
    award. If this is blank, no checkbox will be shown.
  </td>
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
<tr><td style="text-align: right;">Auxiliary&nbsp;Material:</td>
  <td><input name="auxMaterial" type=checkbox $chkAux>
  Check to allow authors to upload "supporting material", in addition to the
  submission file.
  (<a target="_blank" href="../documentation/submitter.html#submit">what&prime;s
  this?</a>)</td>
</tr>
<tr>
  <td class=rjust>Author-declared&nbsp;Conflicts:</td>
  <td><input name="authConflict" type="checkbox" $chkAuthConf>
    Check to allow authors to specify conflict-of-interest between PC members and their submission (<a target="_blank" href="../documentation/submitter.html#submit">what&prime;s this?</a>)
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
EndMark;
} else { // camera-ready submissions, can only modify the deadline
  print $cmrDeadlineHTML;
}
print <<<EndMark
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
