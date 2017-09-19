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
  $subFileLine =<<<EndMark
<tr><td style="text-align: right;">
  <small>(*)</small>&nbsp;Submission&nbsp;File:</td>
  <td><input name="sub_file" size="70" type="file" class="required"><br/>
    The submission itself, in one of the supported formats ($supportedFormats).
  <br/></td>
</tr>

EndMark;
  if (CONF_FLAGS & FLAG_AUX_MATERIAL) {
    $subFileLine .=<<<EndMark
<tr><td style="text-align: right;">
  Supporting&nbsp;Material:</td>
  <td><input name="auxMaterial" size="70" type="file"><br/>
      Auxiliary supporting material (code, data, proofs, etc.).
      (<a target="_blank" href="../documentation/submitter.html#submit">what&prime;s this?</a>)
  <br/></td>
</tr>

EndMark;
  }
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
  $fileReq = $subFileLine = '';
}

$affRequired= (defined('USE_AFFILIATIONS')&& USE_AFFILIATIONS)? ' required':'';

$checkbox = "";
$checkbox_text = "";
if (defined("OPTIN_TEXT")) {
  $checkbox = "<input type='checkbox' name='optin' value='1'/>";
  $checkbox_text = OPTIN_TEXT;
}

$timeleft = show_deadline($ddline);                   // how much time is left
$subDdline = 'Deadline is '.utcDate('r (T)',$ddline); // when is the deadline

$chairNotice = ((PERIOD>PERIOD_SUBMIT) || (USE_PRE_REGISTRATION && (PERIOD>PERIOD_PREREG)))? "<b>Notice: only the PC chair can use this page after the deadline.</b><br/>\n": '';

$links = show_sub_links(3);

print <<<EndMark
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
<link rel="stylesheet" type="text/css" href="../common/saving.css"/>
<style type="text/css">
tr { vertical-align: top; }
</style>
<link rel="stylesheet" type="text/css" href="$JQUERY_CSS"> 
<script src="$JQUERY_URL"></script>
<script src="$JQUERY_UI_URL"></script>
<script src="../common/ui.js"></script>
<script src="../common/authNames.js"></script>

<title>New Submission to $confName</title>
</head>

<body>
$links
<hr/>
$chairNotice
<h1>New Submission to $confName</h1>
<h3 class=timeleft>$subDdline<br/>
$timeleft</h3>

<form name="submit" action="act-submit.php" enctype="multipart/form-data" method="post" accept-charset="utf-8">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">

<table cellpadding="6">
<tr><td></td><td style="color: green; text-align: right">
    Mandatory fields are marked with a (*)</td>
</tr><tr>
<td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;Title:</td>
<td><input name="title" size="90" type="text" class="required"><br/>
    The title of the paper that you are submitting</td>
</tr><tr>
<td style="text-align: right;"><small>(*)</small>&nbsp;Contact&nbsp;Email(s):</td>
<td><input name="contact" size="90" type="text"  class="required"><br/>
Comma-separated list with <b>at least one valid email address</b> of the form user@domain; for example:<br/>
<tt>first-author@university.edu, secondAuthor@company.com, third.one@somewhere.org</tt><br/>
</td>
</tr><tr>
<tbody id="authorFields"> <!-- Grouping together the author-related fields -->
<td style="text-align: right;"><small>(*)</small> Authors:</td>
<td>List <b style="color: red;">one author per line</b>, in the order they appear on the paper, using names of the form <tt>GivenName M. FamilyName</tt>.
<ol class="authorList compactList">
  <li class="oneAuthor">
  Name:<input name="authors[]" size="42" type="text" class="author required"/>,
  Affiliations:<input name="affiliations[]" size="32" type="text" class="affiliation{$affRequired}"/>
  <input type='hidden' name='authID[]' class='authID'/></li>

EndMark;
$nAuthors = empty($_GET['nAuthors'])? 5: intval(trim($_GET['nAuthors']));

while (--$nAuthors > 0) { // pre-decrement since we printed one already
  print '  <li class="oneAuthor">
  Name:<input name="authors[]" size="42" type="text" class="author"/>,
  Affiliations:<input name="affiliations[]" size="32" type="text" class="affiliation"/>
  <input type="hidden" name="authID[]" class="authID"/></li>'."\n";
}
print <<<EndMark
</ol>
<a style="float: right;" class="moreAuthors" href="./submit.php?nAuthors=6" rel="3">more authors</a>
</td></tr>
</tbody> <!-- End of group of author-related fields -->
<tr><td style="text-align: right;"><small>(*)</small> Abstract:</td>
  <td><textarea name="abstract" rows="15" cols="80" class="required"></textarea><br/>
</td></tr>
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

$anonText = ANONYMOUS? 'is anonymous and that it ': '';
$confHome = '<a href="'.CONF_HOME.'" target="_blank">'.CONF_SHORT.' '.CONF_YEAR.' site</a>';
print <<<EndMark
<tr><td style="text-align: right;">Keywords:</td>
  <td><input name="keywords" size="90" type="text"><br /></td>
</tr><tr>
  <td style="text-align: right;">Comments to Chair: </td>
  <td><textarea name="comment" rows="4" cols="80"></textarea><br />
    This message will only be seen by the program chair(s).
  </td>
</tr><tr>
  <td style="text-align: right;">$checkbox</td><td>$checkbox_text</td>
</tr><tr>
  <td style="text-align: right;">Format:</td><td>Sign your name below to confirm that this submission {$anonText}adheres to the formatting requirements in the call-for-papers (including page limits) <input name="formality" class="required"/></td>
</tr><tr><td></td><td><input value="$submitBtn" type="submit">
EndMark;

if (CONF_FLAGS & FLAG_AUTH_CONFLICT) { print <<<EndMark
</td></tr>
<tr><td style="text-align: right;"><input type='checkbox' name='noConflicts' value='1'></td>
  <td><b>No Conflicts:</b>
  Check if this submission has <b>NO CONFLICTS</b> with any
  of the PC members.</br>
  If unchecked, you will be able to specify the conflicts on the next page.
  See the $confHome
  for the conflict-of-interest rules and the list of PC members.
</td>
EndMark;
} else {
  print "<input type='hidden' name='noConflicts' value='1'></td>";
}

print <<<EndMark
</tr></table></form>
<hr />
$links
</body>
</html>
EndMark;
?>
