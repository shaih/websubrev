<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
$errorMsg="You can contact the program chair to reset your password manually.";
$preReview=true;      // page is available also before the review peiod

require 'header.php'; // defines $pcMember=array(id, name, email...)

$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);

if (!isset($_POST['pwdChange'])) {  // display the form

  $errNo = isset($_GET['error']) ? intval($_GET['error']) : NULL;
  $errMsg = '';
  if (isset($errNo)) {
    $errs = array("You must specify a User-name",
		  "User-name/Old-password do not match $revName",
		  "New password is empty, or two new-password fields do not match");

    $errMsg = $errs[$errNo];
    if(!isset($errMsg)) $errMsg = "Unknown error";

    $errMsg = "<span style=\"color: red;\"><b>$errMsg</b></span><br />";
  }

  $links = show_rev_links(5);
  print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head><meta charset="utf-8">

<style type="text/css">
h1 { text-align: center; }
</style>

<script language="javascript" type="text/javascript">
<!--
function checkform( form )
{
  // Check that the username field is not empty
  var pat = /^\s*$/;
  if (pat.test(form.user.value)) {
    alert( "You must specify a User-name" );
    form.user.focus();
    return false;
  }

  // Check that the two occurences of the new password are the same
  if (form.pwd1.value != form.pwd2.value) {
    alert( "The two new-password fields do not match" );
    form.pwd1.focus();
    return false;
  }

  return true;
}
-->
</script>

<title>Change password for $revName</title>
</head>

<body>
$links
<hr />
<h1>Change password for $revName</h1>

Use the form below to choose a new password. (If you forgot your old
password, contact the chair to have it reset.) Upon successful
change, you will be sent an email message containing the new password.
<br /><br />

$errMsg
<form accept-charset="utf-8" onsubmit="return checkform(this);" action="password.php" method="post"
      enctype="multipart/form-data">
<input type="hidden" name="pwdChange" value="on">
<table><tbody>
<tr><td style="text-align: right;">User-name:</td>
    <td><input type="text" name="user" size=40/></td>
</tr>
<tr><td style="text-align: right;">Old Password:</td>
    <td><input type="password" name="oldPwd" size=40/></td>
</tr>
<tr><td style="text-align: right;">New Password:</td>
    <td><input type="password" name="pwd1" size=40/></td>
</tr>
<tr><td style="text-align: right;">Repeat New Password:</td>
    <td><input type="password" name="pwd2" size=40/></td>
</tr>
<tr><td></td>
    <td><input value="Change Password" type="submit">
    (you will be prompted for the new password)</td>
</tr>
</tbody></table>
</form>
<hr />
$links
</body>
</html>
EndMark;
exit();
}  // if (!isset($_POST['pwdChange']))

// If $_POST['pwdChange'] is set, process the form

// Read the input fileds
$user = strtolower(trim($_POST['user']));
$oldPwd = trim($_POST['oldPwd']);
$pwd1 = trim($_POST['pwd1']);
$pwd2 = trim($_POST['pwd2']);

// Check that the mandatory conditions are met
if (empty($user)) returnError(0);
if (($user != strtolower(trim($_SERVER['PHP_AUTH_USER'])))
    || ($oldPwd != trim($_SERVER['PHP_AUTH_PW']))) {
  returnError(1);
}
if (empty($pwd1) || ($pwd1 != $pwd2)) returnError(2);

// All is well, change/reset the password in the database
$pwd1 = sha1(CONF_SALT . $user . $pwd1);
$qry = "UPDATE {$SQLprefix}committee SET revPwd=? WHERE revId=?";
pdo_query($qry, array($pwd1,$revId), "Cannot change passwords for $revName: ");
header("Location: index.php?newPwd=ok");

function returnError($errNo)
{
  header("Location: password.php?error=$errNo");
  exit();  
}
?>
