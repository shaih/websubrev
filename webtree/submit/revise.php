<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants and utils files

// Camera-ready revisions are now done from cameraready.php
if (PERIOD>=PERIOD_CAMERA) { // redirect to camera-ready interface
  header("Location: cameraready.php?".$_SERVER['QUERY_STRING']);
  exit();
}

$chairNotice = (PERIOD>PERIOD_SUBMIT)? "<b>Notice: only the PC chair can use this page after the deadline.</b><br/>\n": '';

if (USE_PRE_REGISTRATION) { // if pre-registration is required
  $submit = 'Submit/';
  $submitted = 'registered';
  $submission = 'registration';
} else {
  $submit = '';
  $submitted = 'submitted';
  $submission = 'submission';
}


$confName = CONF_SHORT . ' ' . CONF_YEAR;
$titleText = "{$submit}Revise a Submission to $confName";
$h1text = "<h1>$titleText</h1>";
$timeleft = show_deadline(SUBMIT_DEADLINE);
$subDdline = 'Deadline is '
           . utcDate('r (T)', SUBMIT_DEADLINE); // when is the deadline

$subId = isset($_GET['subId']) ? trim($_GET['subId']) : '';
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';
$title = $authors  = $affiliations  
  = $contact = $abstract= $category = $keywords = $comment = '';

$optin = 0;

if ($subId > 0 && !empty($subPwd)) {
  $cnnct = db_connect();
  $qry = "SELECT title, authors, affiliations, contact, abstract, category,\n"
    . "   keyWords, comments2chair,flags\n"
    . "FROM submissions WHERE subId='" . my_addslashes($subId, $cnnct) .
    "' AND subPwd='" . my_addslashes($subPwd, $cnnct) . "'";
  $res=db_query($qry, $cnnct);
  $row=@mysql_fetch_row($res);
  if (!$row) {
    $h1text="<h1>Cannot Revise a Non-Existent Submission</h1>\n"
      . "<span style=\"color: red;\">\n"
      . "No submission with ID $subId and password $subPwd was found.\n"
      . "Please enter the correct details below:</span><br/><br/>\n\n";
    $subId = $subPwd = '';
  }
  if (!empty($subId)) {
    $subId = (int) $subId;
    $subPwd = htmlspecialchars($subPwd);
    $title = htmlspecialchars($row[0]);
    $authors  = htmlspecialchars($row[1]);
    $affiliations  = htmlspecialchars($row[2]);
    $contact = htmlspecialchars($row[3]);
    $abstract= htmlspecialchars($row[4]);
    $category= htmlspecialchars($row[5]);
    $keywords= htmlspecialchars($row[6]);
    $comment = htmlspecialchars($row[7]);
    $flags = $row[8];
    $optin = $flags & FLAG_IS_CHECKED;
  }
}

$checkbox = $checkbox_text = "";
if(defined("OPTIN_TEXT")) {
  $checked = $optin ? "checked" : "";
  $checkbox = "<input type='checkbox' name='optin' value='1' $checked/>";
  $checkbox_text = OPTIN_TEXT;
}


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

$links = show_sub_links(4); 
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">
h1 { text-align: center; }
h3 { text-align: center; color: blue; }
tr { vertical-align: top; }
</style>

<script type="text/javascript" src="../common/validate.js"></script>
<script type="text/javascript" language="Javascript">
<!--
function checkform( form )
{
  var pat = /^\s*$/;
  // Checking that all the mandatory fields are present
  st = 0;
  if (pat.test(form.subId.value)) { st |= 1; }
  if (pat.test(form.subPwd.value))   { st |= 2; }

  if (st != 0) {
    alert( "You must specify the submission-ID and password" );
    if (st & 1) { form.subId.focus(); }
    else if (st & 2) { form.subPwd.focus(); }
    return false;
  }
  return true ;
}
//-->
</script>

<title>$titleText</title>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
</head>
<body>
$links
<hr />
$chairNotice
$h1text
<h3 class=timeleft>$subDdline<br/>
$timeleft</h3>
<form name="revise" onsubmit="return checkform(this);" action="act-revise.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="referer" value="revise.php">
<table cellspacing="6">
<tbody>
  <tr>
    <td style="text-align: right;">
         <small>(*)</small>&nbsp;Submission&nbsp;ID:</td>
    <td> <input name="subId" size="4" type="text"
                value="$subId">
         The submission-ID, as returned when the paper was first $submitted.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Password:</td>
    <td><input name="subPwd" size="11" value="$subPwd" type="text">
        The password that was returned with the original $submission.
    </td>
  </tr>

EndMark;

if (empty($subId) || empty($subPwd)) {// put button to Load submission details
  print '  <tr>
    <td></td>
    <td><input value="Reload Form with Submission Details (Submission-ID and Password must be specified)" type="submit" name="loadDetails">
    (<a href="../documentation/submitter.html#revise" target="documentation" title="this button reloads the revision form with all the submission details filled-in">what\'s this?</a>)
    </td>
  </tr>';
}

print <<<EndMark

  <tr>
    <td colspan="2" style="text-align: center;"><hr />
        <big>Any input below will overwrite existing information;
             no input means the old content remains intact.</big><br/><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Title:</td>
    <td><input name="title" size="90" type="text" value="$title"><br/>
        The title of your submission</td>
  </tr>
  <tr>
    <td style="text-align: right;">Authors:</td>
    <td><input name="authors" size="90" type="text" value="$authors"><br />
        Separate multiple authors with '<i>and</i>' (e.g., Alice First 
	<i>and</i> Bob T. Second <i>and</i> C. P. Third). <br />
    </td>
  </tr>

EndMark;
if (USE_AFFILIATIONS) {
  print <<<EndMark
  <tr>
    <td style="text-align: right;">Affiliations:</td>
        <td><input name="affiliations" size="70" type="text" value="$affiliations">
  </tr>

EndMark;
}

print <<<EndMark
  <tr>
    <td style="text-align: right;">Contact Email(s):</td>
    <td><input name="contact" size="70" type="text"  value="$contact" onchange="return checkEmailList(this)"><br/>
    Comma-separated list with <b>at least one valid email address</b> of the form user@domain; for example:<br/>
    <tt>first-author@university.edu, secondAuthor@company.com, third.one@somewhere.org</tt><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Abstract:</td>
    <td><textarea name="abstract" rows="15" cols="80">$abstract</textarea><br/>
        Use only plain ASCII and LaTeX conventions for math, but no HTML tags.
        <br /> <br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Submission&nbsp;File: </td>
    <td><input name="sub_file" size="70" type="file"><br />
        The submission itself, in one of the supported formats
	($supportedFormats).
        <br />
    </td>
  </tr>

EndMark;

if (is_array($categories) && (count($categories)>1)) {
  $select = empty($category) ? 'selected="selected" ' : '';
  print '  <tr>
    <td style="text-align: right;">Category:</td>
    <td><select name="category">
        <option '.$select.'value="">(no change)</option>'."\n";
  foreach ($categories as $c) {
    $select = ($c==$category) ? 'selected="selected" ' : '';
    print "        <option {$select}value=\"$c\">$c</option>\n";
  }
  print "        <option value=\"None\">Reset to (no category)</option>\n";
  print "      </select>\n    </td>\n  </tr>\n";
}

print <<<EndMark
  <tr>
    <td style="text-align: right;">Keywords:</td>
    <td><input name="keywords" size="90" type="text" value="$keywords">
        <br/><br/></td>
  </tr>
  <tr>
    <td style="text-align: right;">Comments to Chair: </td>
    <td><textarea name="comment" rows="4" cols="80">$comment</textarea><br/>
        This message will only be seen by the program chair(s).
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">$checkbox</td>
    <td>$checkbox_text</td>
  </tr>
  <tr>
    <td></td>
    <td><input value="{$submit}Revise Submission" type="submit" name="reviseSub">
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
