<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
require 'header.php'; // brings in the contacts file and utils file
$confName = CONF_SHORT . ' ' . CONF_YEAR;
$subId = isset($_GET['subId']) ? trim($_GET['subId']) : '';
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';

$subId = htmlspecialchars($subId);
$subPwd = htmlspecialchars($subPwd);

if (defined('CAMERA_PERIOD')) {
  $chair = false;
  if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
    $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			    $_SERVER['PHP_AUTH_PW'], chair_ids());
  if ($chair === false) {
    header("WWW-Authenticate: Basic realm=\"$confName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Contact the chair to withdraw the submission</h1>");
  }
}
$timeleft = show_deadline(SUBMIT_DEADLINE);
$subDdline = 'Deadline is '
           . utcDate('r (T)', SUBMIT_DEADLINE); // when is the deadline

$chairNotice = '';
if (PERIOD>PERIOD_SUBMIT)
  $chairNotice = "<b>Notice: only the PC chair can withdraw submissions after the deadline, <span style=\"color:red;\">and AUTHORS WILL NOT BE NOTIFIED of the withdrawal!</span></b><br/>\n";

$links = show_sub_links(5);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<script language="Javascript" type="text/javascript">
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

<title>Withdraw a Submission from $confName</title>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
</head>
<body>
$links
<hr />
$chairNotice
<h1 style="text-align: center;">Withdraw a Submission from $confName</h1>
<h3 class=timeleft>$subDdline<br/>
$timeleft</h3>

<form name="withdraw" onsubmit="return checkform(this);" action="act-withdraw.php" enctype="multipart/form-data" method="post" accept-charset="utf-8">

<table cellspacing="6">
<tbody>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Submission-ID:</td>
    <td> <input name="subId" size="4" type="string"
                value="$subId">
         The submission-ID, as returned when the paper was first submitted.
    </td>
  </tr>
  <tr>
    <td style="text-align: right;"><small>(*)</small> Password:</td>
    <td><input name="subPwd" size="11" value="$subPwd" type="string">
        The password that was returned with the original submission.
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input value="I want to withdraw my submission" type="submit">
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
