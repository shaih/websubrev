<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (PERIOD > PERIOD_SUBMIT) { exit("<h1>Review Site is Closed</h1>"); }

$maxGrade = MAX_GRADE;

$subDdline = utcDate('r (T)', SUBMIT_DEADLINE);
$now= utcDate('r (T)');

$crList = '';
if (is_array($criteria) && count($criteria)>0) {
  foreach ($criteria as $cr) {$crList .= "$cr[0]($cr[1]); "; }
  // Remove the last semi-colon
  $crList = substr($crList, 0, strrpos($crList, ';'));
}
$revPrefs = (CONF_FLAGS & FLAG_PCPREFS) ? 'checked="checked"' : '';
$revAttach= (CONF_FLAGS & FLAG_REV_ATTACH) ? 'checked="checked"' : '';
$auxComm  = (CONF_FLAGS & FLAG_SEND_POSTS_BY_EMAIL) ? 'checked="checked"' : '';

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<script language="Javascript" type="text/javascript">
<!--
function checkInt( fld, mn, mx )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  if (fld.value == "") return true;  // allow empty field

  var pat = /^-?[0-9]+$/;
  if((pat.test(fld.value)==false) || (fld.value<mn) || (fld.value>mx)) {
    alert("Field must contain an integer between "+mn+" and "+mx);
    fld.focus();
    fld.select();
    return false ;
  }
  return true ;
}
//-->
</script>
<style type="text/css">
h1 { text-align: center; }
.lightbg { background-color: rgb(235, 235, 235); } 
.darkbg { background-color: lightgrey; } 
</style>

EndMark;

/********************************************************************/
/******************** END HEADER, CONTENT STARTS HERE ***************/
/********************************************************************/

$links = show_chr_links();
print <<<EndMark

<title>Closing Submissions and Activating the Review Site</title>
</head>
<body>
$links
<hr/>
<h1>Closing Submissions and Activating the Review Site</h1>

<center>
<table><tbody>
<tr><td>Submission deadline:</td><td style="color: red;">$subDdline</td></tr>
<tr><td>The time now is:</td><td style="color: blue;">$now</td></tr>
</tbody></table>
</center>

<h2>Are you sure you want to close the submission site?</h2>
Click on "Close Submissions and Activate Review Site" at the bottom of
the page to move into the review period. You can give PC members access
to the review site by following the link "Manage PC membership" on the
administration page.<br/>
<br/>
On this page you can also modify some of the review parameters such as
the range of grades, etc. These parameters cannot be modified after the
review site is activated.<br/>
<br/>

<form accept-charset="utf-8" name="reviewPrms" action="doCloseSubmissions.php"
      enctype="multipart/form-data" method="post">

<input name="reviewPrms" value="on" type="hidden">
<input name="closeSubmissions" value="on" type="hidden">
<table><tbody>
<tr class="lightbg">
  <th style="text-align: center;" colspan="2"><big>Review Parameters</big></th>
</tr>
<tr class="darkbg">
  <td style="text-align: center;"><a href="../documentation/reviewer.html#revAttach" target="documentation" title="click for more help">Review&nbsp;Attachments:</a></td>
  <td colspan="4"><input name="revAttach" type="checkbox" $revAttach> Check to allow attachments with the reviews.</td>
</tr>
<tr class="lightbg">
  <td style="text-align: center;"><a href="../documentation/chair.html#revPrefs" target="documentation" title="click for more help">Reviewer&nbsp;Preferences:</a>
  <td colspan="4"><input name="revPrefs" type="checkbox" $revPrefs>
  Check to let PC members specify their reviewing preferences.
  <input name="revPrefsFlag" value="on" type="hidden"></td>
</tr>
<tr class="darkbg">
  <td class=rjust><a href="../documentation/chair.html#sendPostByEmail" target="documentation" title="click for more help">External&nbsp;communications:</a></td>
  <td><input name="auxComm" type="checkbox" $auxComm>
    Check to give reviewers a link for sending discussion items to authors
    and sub-reviewers.<br/>
    (Communication with authors is moderated by the chair, emails to
     sub-reviewers are sent directly.)
</tr>
<tr class="lightbg">
  <td style="text-align: center;">Grades:</td>
  <td colspan="4">1 through
      <input name="maxGrade" value="$maxGrade" type="text"
             onchange="return checkInt(this, 2, 9)" maxlength="1" size="2">
      (max-grade must be in the range [2..9])
  </td>
</tr>
<tr class="darkbg" style="vertical-align: top;">
  <td style="text-align: center">Other Evaluation Criteria:</td>
  <td><textarea name="criteria" style="width: 100%">$crList</textarea>
      A <i><b>semi-colon-separated</b></i> list of criteria, each in the format
      "Name (max-val)" (the min-val is always&nbsp;1). <br/>For example
      "<tt>Clear Presentation (3); Bribe Amount (9); ...</tt>"<br/> (<b>Do
      not add "<tt>Confidence(X)</tt>" here</b>, a "<tt>Confidence(3)</tt>"
      field is hard-wired in the software.)
      <input type="hidden" name="setCriteria" value="on">
  </td>
</tr>
<!-- ================= The Submit Button =================== -->
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" style="text-align: center;">
      <input value="Close Submissions and Activate Review Site" type="submit">
    </td>
</tr>
</tbody></table>
</form>
<hr/>
$links
</body>
</html>

EndMark;
?>
