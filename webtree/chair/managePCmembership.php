<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; // Just a precaution
require 'header.php';

if (defined('CAMERA_PERIOD')) { exit("<h1>Review Site is Closed</h1>"); }

$cnnct = db_connect();
$qry = "SELECT revId, revPwd, name, email, flags FROM committee ORDER BY revId";
$res = db_query($qry, $cnnct);

// Store the committee details in an array
$cmmtee = array();
while ($row = mysql_fetch_row($res)) { array_push($cmmtee, $row); }

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<script language="Javascript" type="text/javascript">
<!--
function checkEmail( fld )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  var pat = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
  if((fld.value != "") && (pat.test(fld.value)==false)) {
    alert("Not a valid email format");
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
<title>Manage PC Membership</title>
</head>
<body>
$links
<hr/>
<h1>Manage PC Membership</h1>

Below is the list of program-committee members that have access to the
review area of this site. To login to the site, a member must provide
his/her email-address and a password. <i>Please go over this list and
correct any errors</i>. Note the following:
<ul>
<li> Make sure that the names are formatted the way you want them to
  appear on the reports and discussion boards. (E.g., you may prefer the
  format "First M. Last" or "Last, First M.", or whatever.)<br />
  <br /></li>
  
<li> You can add members by putting a <b>semi-colon-separated</b>
  list of new members in the text area at the bottom of the page.
  The list must be of the form "<tt>Name1 &lt;email-address1&gt;; Name2 
  &lt;email-address&gt;; ...</tt>"  Make sure that the names are formatted
  the way you want them (and that <i>they do not include semi-colons</i>).
  <br /><br /></li>

<li> You can remove PC members by checking the <i>"Remove"</i> check-box.<br />
  <br /></li>

<li> You can reset the passwords of PC members by checking the <i>"Reset 
  Password"</i> checkbox. Also, changing the email address of a member
  automatically resets his/her password.<br /><br /></li>

<li>When you submit this form, an email message will be sent to any PC member
  whose password was reset, informing him/her of the new password. The chair(s)
  will be CCed on all these emails.</li>
</ul>

<form action=doManagePCmembership.php enctype="multipart/form-data" method=post>

<input name="reviewSite" value="on" type="hidden">
<table cellpadding="3" cellspacing="0"><tbody>
<tr class="darkbg">
  <th colspan="5" style="text-align: center;"><big>Program Comittee</big></th>
</tr>
<tr class="darkbg">
  <th style="text-align: center;">Chair</th>
  <th style="text-align: center;">Name</th>
  <th style="text-align: center;">Email</th>
  <th style="text-align: center;">Reset Pwd</th>
  <th style="text-align: center;">Remove</th>
</tr>
EndMark;

$bgs = array("class=\"lightbg\"", "class=\"darkbg\"");
$parity=1;
$firstChair = true;
foreach($cmmtee as $m) {
  $revId = $m[0];
  if (empty($m[1])) {  // Empty password, force reset
    $chk='checked="checked"';
  }
  else { $chk = ''; }
  $m[2] = htmlspecialchars($m[2]);
  $m[3] = htmlspecialchars($m[3]);
  print "<tr ".$bgs[$parity].">\n";
  if ($m[4] & FLAG_IS_CHAIR) {
    $chairChk = 'checked="checked"';
    $rmGrayed = ' disabled="disabled"';
  } else {
    $chairChk = $rmGrayed = '';
  }
  if ($firstChair && ($m[4] & FLAG_IS_CHAIR)) { // cannot uncheck the 1st chair
      print '  <td style="text-align: center;">YES
      <input name="members['.$revId.'][3]" type="hidden" value="on"></td>'."\n";
      $firstChair = false;
  }
  else print '  <td style="text-align: center;">
      <input id="pcm'.$revId.'isChair" name="members['.$revId.'][3]" type="checkbox" title="check to make '.$m[2].' a PC-chair" '.$chairChk."></td>\n";
  print <<<EndMark
  <td><input name="members[$revId][0]" size="30" type="text"
       value="$m[2]" style="width: 100%;">
  </td>
  <td><input name="members[$revId][1]" size="35" type="text"
       value="$m[3]" onchange="return checkEmail(this)" style="width: 100%;">
  </td>
  <td style="text-align: center;">
      <input id="pcm{$revId}pwdReset" name="members[$revId][2]" type="checkbox" $chk/></td>
  <td style="text-align: center;"><img alt="(X)" width=12 src="../common/stop.GIF">
      <input id="pcm{$revId}remove" title="Check to remove $m[2] from the committee" name="mmbrs2remove[$revId]" type="checkbox" $rmGrayed>
  </td>
</tr>

EndMark;
  $parity = 1 - $parity;
}

print <<<EndMark
<tr $bgs[$parity]>
  <td style="text-align: center;">New<br />Members:</td>
  <td colspan="4"><textarea name="mmbrs2add" style="width: 100%"></textarea>
      A <i><b>semi-colon-separated</b></i> list of email addresses:
      "<tt>Name1 &lt;email1&gt;; Name2 &lt;email2&gt;;...</tt>" 
  </td>
</tr>
<!-- ================= The Submit Button =================== -->
<tr><td colspan="5">&nbsp;</td></tr>
<tr><td colspan="5" style="text-align: center;">
        <input value="Grant Access to Review Site" type="submit">
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
