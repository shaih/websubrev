<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
//if (defined('REVISE_AFTER_DEADLINE') && REVISE_AFTER_DEADLINE)
//  $bypassAuth = true; // allow access to this script even after the deadline

require 'header.php'; // brings in the constants and utils files

// Camera-ready revisions are now done from cameraready.php
if (PERIOD>=PERIOD_CAMERA) { // redirect to camera-ready interface
  header("Location: cameraready.php?".$_SERVER['QUERY_STRING']);
  exit();
}

$chairNotice = (PERIOD>PERIOD_SUBMIT && !isset($bypassAuth))? "<b>Notice: only the PC chair can use this page after the deadline.</b><br/>\n": '';

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
$title = $authors  = $affiliations = $contact
  = $abstract= $category = $keywords = $comment = $authorIDs = '';

$optin = 0;

if ($subId > 0 && !empty($subPwd)) {
  $qry = "SELECT title, authors, affiliations, contact, abstract, category, keyWords, comments2chair,flags,authorIDs FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=?";

  $row=pdo_query($qry, array($subId, $subPwd))->fetch(PDO::FETCH_NUM);
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
    $authors  = explode('; ',htmlspecialchars($row[1]));
    $affiliations  = explode('; ',htmlspecialchars($row[2]));
    $contact = htmlspecialchars($row[3]);
    $abstract= htmlspecialchars($row[4]);
    $category= htmlspecialchars($row[5]);
    $keywords= htmlspecialchars($row[6]);
    $comment = htmlspecialchars($row[7]);
    $flags = $row[8];
    $authorIDs     = explode('; ',htmlspecialchars($row[9]));
    $optin = $flags & FLAG_IS_CHECKED;
  }
}
else $subId=$subPwd='';

$affRequired= (defined('USE_AFFILIATIONS')&& USE_AFFILIATIONS)? ' required':'';

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
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
<link rel="stylesheet" type="text/css" href="../common/saving.css"/>
<style type="text/css">
h3 { text-align: center; color: blue; }
tr { vertical-align: top; }
</style>
<link rel="stylesheet" type="text/css" href="$JQUERY_CSS"> 
<script src="$JQUERY_URL"></script>
<script src="$JQUERY_UI_URL"></script>
<script src="../common/ui.js"></script>
<script src="../common/authNames.js"></script>

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
<form name="revise" action="act-revise.php" enctype="multipart/form-data" method="post" accept-charset="utf-8">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="referer" value="revise.php">
<table cellspacing="6">
<tr><td style="text-align: right;"><small>(*)</small>&nbsp;Submission&nbsp;ID:</td>
  <td><input name="subId" size="4" type="text" value="$subId" class="required">
    The submission-ID, as returned when the paper was first $submitted.</td>
</tr><tr>
  <td style="text-align: right;"><small>(*)</small> Password:</td>
  <td><input name="subPwd" size="11" value="$subPwd" type="text" class="required">
    The password that was returned with the original $submission.</td>
</tr>
EndMark;

if (empty($subId) || empty($subPwd)) {// put button to Load submission details
  print '<tr>
  <td></td><td><input value="Reload Form with Submission Details (Submission-ID and Password must be specified)" type="submit" name="loadDetails" onclick="return (noCheck=true);"
    (<a href="../documentation/submitter.html#revise" target="documentation" title="this button reloads the revision form with all the submission details filled-in">what\'s this?</a>)</td>
</tr>';
}

print <<<EndMark
<tr><td colspan="2" style="text-align: center;"><hr/>
 <big>Any input below will overwrite existing information;
 no input means the old content remains intact.</big><br/><br/></td>
</tr><tr>
  <td style="text-align: right;">Submission&nbsp;Title:</td>
<td><input name="title" size="90" type="text" value="$title" class="required">
  <br/>The title of your submission</td>
</tr><tr>
<td style="text-align: right;">Contact Email(s):</td>
<td><input name="contact" size="90" type="text" value="$contact" class="required"><br/>
  Comma-separated list of email addresses of the form user@domain</td>
</tr><tr>
<tbody id="authorFields"> <!-- Grouping together the author-related fields -->
  <td style="text-align: right;">Authors:</td>
  <td>List authors in the order they appear on the paper, using names of the form <tt>GivenName M. FamilyName</tt>.
<ol class="compactList authorList">

EndMark;

$nAuthors = count($authors);
if (isset($_GET['nAuthors']) && $_GET['nAuthors']>$nAuthors)
  $nAuthors = (int) $_GET['nAuthors'];
for ($i=0; $i<$nAuthors; $i++) {
  $name= isset($authors[$i])?      $authors[$i]:      '';
  $aff = isset($affiliations[$i])? $affiliations[$i]: '';
  $authID = isset($authorIDs[$i])? $authorIDs[$i]:    '';
print '  <li class="oneAuthor">
  Name:<input name="authors[]" size="42" type="text" class="author" value="'.$name.'">,
    Affiliations:<input name="affiliations[]" size="32" type="text" class="affiliation" value="'.$aff."\">
    <input type='hidden' name='authID[]' class='authID' value='$authID'></li>\n";
}
if ($subId>0 && !empty($subPwd))
  $url = "./revise.php?subId={$subId}&subPwd={$subPwd}&nAuthors=".($nAuthors+3);
else 
  $url = "./revise.php?nAuthors=".($nAuthors+3);
print <<<EndMark
</ol>
<a style="float: right;" class="moreAuthors" href="$url" rel="$nAuthors">more authors</a><br/>
If the list above is not empty, it will replace the curret author list even if these lists have different number of authors.
</td></tr>
</tbody> <!-- End of group of author-related fields -->
<tr><td style="text-align: right;">Abstract:</td>
  <td><textarea name="abstract" rows="15" cols="80">$abstract</textarea><br/></td>
</tr><tr>
  <td style="text-align: right;">Submission&nbsp;File:</td>
  <td><input name="sub_file" size="70" type="file"><br/>
    The submission itself, in one of the supported formats ($supportedFormats).
</td></tr>

EndMark;

if (CONF_FLAGS & FLAG_AUX_MATERIAL) {
  print <<<EndMark
<tr><td style="text-align: right;">
  Supporing&nbsp;Material:</td>
  <td><input name="auxMaterial" size="70" type="file"><br/>
      Auxilieary supporting material (code, data, proofs, etc.).
      (<a target="_blank" href="../documentation/submitter.html#submit">what&prime;s this?</a>)
  <br/></td>
</tr>

EndMark;
}

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

$anonText = ANONYMOUS? 'is anonymous and that it ': '';
print <<<EndMark
<tr>
  <td style="text-align: right;">Keywords:</td>
  <td><input name="keywords" size="90" type="text" value="$keywords"></td>
</tr><tr>
  <td style="text-align: right;">Comments to Chair: </td>
  <td><textarea name="comment" rows="4" cols="80">$comment</textarea><br/>
    This message will only be seen by the program chair(s).</td>
</tr><tr>
  <td style="text-align: right;">$checkbox</td><td>$checkbox_text</td>
</tr><tr>
  <td style="text-align: right;">Format:</td><td>Sign your name below to confirm that this submission {$anonText}adheres to the formatting requirements in the call-for-papers (including page limits) <input name="formality" class="required"/></td>
</tr><tr>
  <td></td><td><input value="{$submit}Revise Submission" type="submit" name="reviseSub"></td>
</tr>
</table>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
