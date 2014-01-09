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

// Store the committee details in an array
$qry = "SELECT revId,revPwd,name,email,flags,authorID FROM {$SQLprefix}committee ORDER BY revId";
$cmmtee = pdo_query($qry)->fetchAll(PDO::FETCH_ASSOC);

$nNewMembers = 10;
if (!empty($_GET['nNew']) && $_GET['nNew']>0)
  $nNewMembers = intval($_GET['nNew']);

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="$JQUERY_CSS"> 
<script src="$JQUERY_URL"></script>
<script src="$JQUERY_UI_URL"></script>
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
var numToAdd=10; // global variable, how many authors to add per click
//-->
</script>
<script src="../common/authNames.js"></script>
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
<li> Make sure that the names are specified in the format "First M. Last".<br/>
</li>
  
<li> You can add members using the list at the bottom of this form.<br/>
</li>

<li> You can remove PC members by checking the <i>"Remove"</i> check-box.<br/>
</li>

<li> You can reset the passwords of PC members by checking the <i>"Reset 
  Password"</i> checkbox. Also, changing the email address of a member
  automatically resets his/her password.
<br/></li>
</ul>

<p>
When you submit this form, an email message will be sent to any PC member
  whose password was reset, informing him/her of the new password. The chair(s)
  will be CCed on all these emails.
</p>

<form accept-charset="utf-8" action=doManagePCmembership.php enctype="multipart/form-data" method=post>

<input name="reviewSite" value="on" type="hidden">
<p><input value="Update Access to Review Site" type="submit"></p>
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

$bgs = array('class="lightbg"', 'class="darkbg"');
$parity=1;
$firstChair = true;
foreach($cmmtee as $m) {
  $revId = $m['revId'];
  if (empty($m['revPwd'])) {  // Empty password, force reset
    $chk='checked="checked"';
  }
  else { $chk = ''; }
  $m['name'] = htmlspecialchars($m['name']);
  $m['email'] = htmlspecialchars($m['email']);
  print "<tr ".$bgs[$parity].">\n";
  if ($m['flags'] & FLAG_IS_CHAIR) {
    $chairChk = 'checked="checked"';
    $rmGrayed = ' disabled="disabled"';
  } else {
    $chairChk = $rmGrayed = '';
  }
  if ($firstChair &&($m['flags']&FLAG_IS_CHAIR)){ // cannot uncheck the 1st chair
      print '  <td style="text-align: center;">YES
      <input name="members['.$revId.'][3]" type="hidden" value="on"></td>'."\n";
      $firstChair = false;
  }
  else print '  <td style="text-align: center;">
      <input id="pcm'.$revId.'isChair" name="members['.$revId.'][3]" type="checkbox" title="check to make '.$m['name'].' a PC chair" '.$chairChk."></td>\n";
  print <<<EndMark
  <td><input name="members[$revId][0]" size="30" type="text"
       value="{$m['name']}" style="width: 100%;" class="author">
      <input type='hidden' class='authID' value="{$m['authorID']}" name='auxID[$revId]'>
  </td>
  <td><input name="members[$revId][1]" size="35" type="text"
       value="{$m['email']}" onchange="return checkEmail(this)" style="width: 100%;">
  </td>
  <td style="text-align: center;">
      <input id="pcm{$revId}pwdReset" name="members[$revId][2]" type="checkbox" $chk/></td>
  <td style="text-align: center;"><img alt="(X)" width=12 src="../common/stop.GIF">
      <input id="pcm{$revId}remove" title="Check to remove {$m['name']} from the committee" name="mmbrs2remove[$revId]" type="checkbox" $rmGrayed>
  </td>
</tr>

EndMark;
  $parity = 1 - $parity;
}

print <<<EndMark
</tbody></table>
<h2>Add New Program-Commitee Members</h2>
<ol class="authorList compactList">
EndMark;

for ($i=0; $i<$nNewMembers; $i++) {
print <<<EndMark
<li class="oneAuthor">
  Chair? <input name="newChair[]" type="checkbox" title="Check to mark this member as a PC Chair"/>
  Name:<input name="newMembers[]" size="40" type="text" class="author"/>,
  Email:<input name="newEmail[]" size="50" type="text" class="email" onchange="return checkEmail(this)"/>
  <input type='hidden' name='newMemberID[]' class='authID'/>
</li>
EndMark;
}
$nNewMembers += 10;
print <<<EndMark
</ol>
<a class="moreAuthors" href="./managePCmembership.php?nNew=$nNewMembers" rel="$nNewMembers">more PC members</a>
<p><input value="Grant Access to Review Site" type="submit"></p>
</form>
<hr/>
$links
</body>
</html>

EndMark;
?>
