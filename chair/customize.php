<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
$notCustomized = true;
require 'header.php';

if (PERIOD>PERIOD_SETUP) die("<h1>Installation Already Customized</h1>");

if (isset($_GET['username']) && isset($_GET['password'])) {
  $urlParams = '?username='.$_GET['username'].'&password='.$_GET['password'];
}
else { $urlParams = ''; }

if  (defined('CONF_SHORT')) $shortName = CONF_SHORT; else $shortName='';
if (defined('CONF_YEAR')) $year = CONF_YEAR;         else $year = 0;
if ($year == 0) { // guess the year of the conference
  $month = date('n');
  $year = date('Y');
  if ($month>6) $year++;
}

$chkAff = (CONF_FLAGS & FLAG_AFFILIATIONS) ? ' checked="checked"' : '';
$chkRevPrf = (CONF_FLAGS & FLAG_PCPREFS) ? ' checked="checked"' : '';
$chkAnon = (CONF_FLAGS & FLAG_ANON_SUBS) ? ' checked="checked"' : '';

if (CHAIR_NAME=='') $chrEml = CHAIR_EMAIL;
else $chrEml = CHAIR_NAME.' <'.CHAIR_EMAIL.'>';

$star = "<span class=notice>(*)</span>";
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Customizing Submission and Review Site</title>

<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<style type="text/css">
tr {vertical-align: top;}
h1 {text-align: center;}
</style>

<script type="text/javascript" src="../common/validate.js"></script>
<script language="Javascript" type="text/javascript">
<!--
function checkform( form )
{
  // Checking that all the mandatory fields are present
  var pat = /^\s*$/;
  var st = 0;
  if (pat.test(form.longName.value))    { st |= 1; }
  if (pat.test(form.shortName.value))   { st |= 2; }
  if (pat.test(form.confYear.value))    { st |= 4; }

  if (st != 0) {
    alert( "You must specify all the fields that are marked with (*)" );
    if (st & 1)        { form.longName.focus();  }
    else if (st & 2)   { form.shortName.focus(); }
    else if (st & 4)   { form.confYear.focus();  }

    return false ;
  }
  return true ;
}
//-->
</script>
</head>
<body>

<h1>Customizing Submission and Review Site</h1>
This form is used to customize the initial installation of the submission
and review software for your conference. Use it to specify things like the
conference name, deadlines, what formats are acceptable for submissions, etc.
All the conference parameters that you specify here can be changed later from
the administration page.<br/>
<br/>
This form has four sections, corresponding to <a href="#conference">the
conference</a>, <a href="#submissions">submissions</a>, <a href="#committee">
program committee</a>, and <a href="#review">reviews</a>.
<br/>
<form name="customize" onsubmit="return checkform(this);"
 action="confirm-customize.php{$urlParams}" enctype="multipart/form-data" method="post">

<table cellpadding="6"><tbody>
<!-- ============== Details of the conference ================== -->
<tr>
  <td colspan="2" style="text-align: center;"><hr /></td>
</tr>
<tr><td colspan="2" class=rjust>Mandatory fields are marked with $star</td>
</tr>
<tr><td class=rjust><big><b><a NAME="conference">The&nbsp;Conference:</a></b></big></td> <td></td>
</tr>
<tr><td class=rjust>{$star}Conference&nbsp;Name:</td>
  <td><input name="longName" size="90" type="text"><br/>
    E.g., The 18th NBA Annual Symposium on Theory of Basketball</td>
</tr>
<tr><td class=rjust>{$star}Short&nbsp;Name:</td>
  <td><input name="shortName" size="30" type="text" value="$shortName">e.g., BASKET, &nbsp;
    &nbsp; &nbsp; {$star}Year:&nbsp;<input name="confYear" size="4" type="text" value="$year" maxlength="4" onchange="return checkInt(this, 1970, 2099)" value="$year"></td>
</tr>
<tr><td class=rjust>Conference&nbsp;URL:</td>
  <td><input name="confURL" size="90" type="text"><br/>
    URL of the conference home page, where the call-for-papers is found.</td>
</tr>
<tr><td colspan="2" class=rjust><hr/></td></tr>
<!-- ================= Submissions =================== -->
<tr><td class=rjust><big><b><a NAME="submissions">Submissions:</a></b></big></td><td></td>
</tr>
<tr><td class=rjust>Submission&nbsp;Deadline:</td>
  <td><input name="subDeadline" size="90" type="text"></td>
</tr>  
<tr><td class=rjust>Camera&nbsp;ready&nbsp;Deadline:</td>
  <td><input name="cameraDeadline" size="90" type="text"><br/>
    Use a format such as 2005-1-15 18:20 EST. (The date will be converted to UTC.)<br/>
    <b>The software does not enforce these deadlines automatically.</b></td>
</tr>  
<tr><td class=rjust>Categories:</td>
  <td><textarea name="categories" rows="3" cols="70"></textarea><br/>
    A <b><i>semi-colon-separated</i></b> list of categories for the
    submissions (leave empty to forgo categories).</td>
</tr>
<tr><td class=rjust>Require&nbsp;Affiliations:</td>
  <td><input name="affiliations" type="checkbox"{$chkAff}>
    Check to require submitters to specify their affiliations</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#formats" target="documentation" title="click for more help">Supported Formats:</a></td>
  <td><input checked="checked" name="formatPDF" type="checkbox">PDF 
    &nbsp; <input name="formatPS" type="checkbox">PostScript 
    &nbsp; <input name="formatTEX" type="checkbox">LaTeX 
    &nbsp; <input name="formatHTML" type="checkbox">HTML 
    &nbsp; <input name="formatZIP" type="checkbox">Zip Archive
  </td>
</tr>
<tr>
  <td></td>
  <td><input name="formatMSword" type="checkbox">MS-Word 
   &nbsp; <input name="formatPPT" type="checkbox">PowerPoint
   &nbsp; <input name="formatODT" type="checkbox">OpenOffice Document
   &nbsp; <input name="formatODP" type="checkbox">OpenOffice Presentation
  </td>
</tr>
<tr>
  <td class=rjust>Another&nbsp;Format&nbsp;#1:</td>
  <td>Format Name:<input name="format1desc" size="20" type="text">
    &nbsp; Extension:<input name="format1ext" size="3" type="text">
    &nbsp; MIME-type:<input name="format1mime" size="15" type="text">
  </td>
</tr>
<tr>
  <td class=rjust>Another&nbsp;Format&nbsp;#2:</td>
  <td>Format Name:<input name="format2desc" size="20" type="text">
    &nbsp; Extension:<input name="format2ext" size="3" type="text">
    &nbsp; MIME-type:<input name="format2mime" size="15" type="text">
  </td>
</tr>
<tr>
  <td class=rjust>Another&nbsp;Format&nbsp;#3:</td>
  <td>Format Name:<input name="format3desc" size="20" type="text">
    &nbsp; Extension:<input name="format3ext" size="3" type="text">
    &nbsp; MIME-type:<input name="format3mime" size="15" type="text">
  </td>
</tr>
<tr><td colspan="2" class=rjust><hr /></td></tr>
<!-- ================= The Program Committee =================== -->
<tr><td class=rjust><big><b><a NAME="committee">Program&nbsp;Committee:</a></b></big></td><td></td>
</tr>
<tr><td class=rjust>{$star}<a href="../documentation/chair.html#PCemail" target="documentation" title="click for more help">Chair&nbsp;Email:</a></td>
  <td><input name="chair" size="90" type="text" value="$chrEml" onchange="return checkEmail(this)"><br/>
     Only one address (e.g., <tt>chair@basket06.org</tt> or <tt>Earvin Johnson
     &lt;Magic.Johnson@retirement.net&gt;</tt>)</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#PCemail" target="documentation" title="click for more help">Program&nbsp;Committee:</a></td>
  <td><textarea name="committee" rows=15 cols=70>Shaquille O'Neal &lt;shaq@MiamiHeat.nba.com&gt;;
Larry J. Bird &lt;the-bird@old-timers.org&gt;;
Jordan, Michael &lt;Air-Jordan@nike.com&gt;</textarea><br/>
    A <i><b>semi-colon-separated</b></i> list of email addresses (including the
    chair's personal email address).<br/> Each address should be in the format
    "Name &lt;email-address&gt;". (The names that you enter here <br/>will be
    displayed on the reports and discussion boards.)</td>
</tr>
<tr><td colspan=2 class=rjust><hr /></td></tr>
<!-- ================= Reviews =================== -->
<tr><td class=rjust><big><b><a NAME="review">Reviews:</a></b></big></td>
  <td>(the default options below should work just fine for most cases)</td>
</tr>
<tr><td class=rjust>Anonymous&nbsp;Submissions:</td>
  <td><input name="anonymous" type="checkbox"{$chkAnon}>
    Check to hide author names from the reviewers.</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#revPrefs" target="documentation" title="click for more help">Reviewer&nbsp;Preferences:</a></td>
  <td><input name="revPrefs" type="checkbox"{$chkRevPrf}>
    Check to let PC members specify their reviewing preferences.</td>
</tr>
<tr><td class=rjust>Overall&nbsp;Score:</td>
  <td>Min: <input disabled size="1" type="text" value="1"> , &nbsp;
     Max: <input name="maxGrade" size="2" type="text" value="6"
	   maxlength="1" onchange="return checkInt(this, 2, 9)"> &nbsp;
     (later you can assign semantics to these scores).</td>
</tr>
<tr><td class=rjust>Other&nbsp;Evaluation&nbsp;Criteria:</td>
  <td>Other than overall score, you can have upto five different grades for
  specific criteria such as <br/>presentation, IP status, etc. The min grade
  value is always 1 and the max value must be in [2,9]. 
  <textarea name="criteria" cols="70">Technical(3); Editorial(3); Suitability(3)</textarea><br/>
  A <i><b>semi-colon-separated</b></i> list of upto five criteria, each criterion in the
  format <tt>Name(max-val)</tt>.<br/><b>Do not add</b> <tt>Confidence(X)</tt>
  above, a field <tt>Confidence(3)</tt> is hard-wired in the software.</td>
</tr>
<tr><td colspan="2" class=rjust><hr /></td></tr>
<!-- ================= The Submit Button =================== -->
<tr><td colspan="2" class=ctr><input value="   Submit   " type="submit"></td>
</tr>
</tbody></table>
</form>
EndMark;
?>
</body>
</html>
