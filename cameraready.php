<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
require 'header.php'; // brings in the contacts file and utils file

$confName = CONF_SHORT . ' ' . CONF_YEAR;
if (CAMERA_PERIOD!==true)
     die("<h1>Final-version submission site for $confName is closed</h1>");

$h1text = "<h1>Camera-Ready Revision for $confName</h1>";

$subId = isset($_GET['subId']) ? trim($_GET['subId']) : '';
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';
$title = $authors = $affiliations = $contact = $abstract= $nPages = '';

if ($subId > 0 && !empty($subPwd)) {
  $cnnct = db_connect();
  $sid = my_addslashes($subId, $cnnct);
  $pw = my_addslashes($subPwd, $cnnct);
  $qry = "SELECT title, authors, affiliations, contact, abstract, nPages\n"
    . "  FROM submissions sb LEFT JOIN acceptedPapers ac USING(subId)\n"
    . "  WHERE sb.subId='$sid' AND subPwd='$pw' AND status='Accept'\n";
  $res=db_query($qry, $cnnct);
  $row=@mysql_fetch_row($res);
  if (!$row) {
    $h1text="<h1>Non-Existent Accepted Submission</h1>\n"
     . "<span style=\"color: red;\">\n"
     . "No accepted submission with ID $subId and password $subPwd found.\n"
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
    $nPages = (int) $row[5];
    if ($nPages <= 0) $nPages = '';
  }
}

$links = show_sub_links(6);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

<style type="text/css">
h1 { text-align: center; }
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
  st = 0;
  if (pat.test(form.subId.value)) { st |= 1; }
  if (pat.test(form.subPwd.value))   { st |= 2; }

  if (st != 0) {
    alert( "You must specify the submission number and password" );
    if (st & 1) { form.subId.focus(); }
    else if (st & 2) { form.subPwd.focus(); }
    return false;
  }
  return true ;
}
//-->
</script>

<title>Camera-Ready Revision for $confName</title>
</head>
<body>
$links
<hr />
$h1text

<form name="cameraready" onsubmit="return checkform(this);" action="act-revise.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="hidden" name="referer" value="cameraready.php">
<table cellspacing="6">
<tbody>
  <tr>
    <td style="text-align: right;">
         <small>(*)</small>&nbsp;Submission&nbsp;ID:</td>
    <td> <input name="subId" size="4" type="text"
                value="$subId">
         The submission number, as returned when the paper was first submitted.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Password:</td>
    <td><input name="subPwd" size="11" value="$subPwd" type="text">
        The password that was returned with the original submission.
    </td>
  </tr>

EndMark;

if (empty($subId)) { // put a button to "Load submission details"
  print '  <tr>
    <td></td>
    <td><input value="Reload Form with Submission Details (Submission-ID and Password must be specified)" type="submit" name="loadDetails">
    (<a href="documentation/submitter.html#camera" target="documentation" title="this button reloads the revision form with all the submission details filled-in">what\'s this?</a>)
    </td>
  </tr>';
}

print <<<EndMark
  <tr>
    <td colspan="2" style="text-align: center;"><hr />
        <big>Any input below will overwrite existing information;
             no input means the old content remains intact.</big><br /><br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Number&nbsp;of&nbsp;Pages:</td>
    <td><input name="nPages" size="3" type="text" value="$nPages">
     Will be used by the chair to
     automatically generate the table-of-contents and author index.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Title:</td>
    <td><input name="title" size="90" type="text" value="$title"></td>
  </tr>
  <tr>
    <td style="text-align: right;">Authors:</td>
    <td><input name="authors" size="90" type="text" value="$authors"><br/>
        Separate multiple authors with '<i>and</i>' (e.g., Alice First 
	<i>and</i> Bob T. Second <i>and</i> C. P. Third). <br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Affiliations:</td>
        <td><input name="affiliations" size="70" type="text" value="$affiliations">
  </tr>
  <tr>
    <td style="text-align: right;">Contact Email:</td>
    <td><input name="contact" size="70" type="text" value="$contact"
         onchange="return check_email(this)"><br />
        Must be <b>one valid email address</b> of the form user@domain;
        <b>make sure that this is a valid address</b>, it will be used
        for communication with the publisher. <br /><br />
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Abstract:</td>
    <td><textarea name="abstract" rows="15" cols="80">$abstract</textarea><br/>
        Use only plain ASCII and LaTeX conventions for math, but no HTML tags.
        <br/><br/>
    </td>
  </tr>
  <tr>
    <td style="text-align: right;">Submission&nbsp;File: </td>
    <td><input name="sub_file" size="70" type="file"><br />
        The archive file (tar, tzg, etc.) with all the necessary files.
        See <a href="index.php">the instructions</a>.<br/><br/>
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input value="Submit camera-ready devision" type="submit">
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
