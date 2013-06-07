<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD!=PERIOD_REVIEW) {
  exit("<h1>Can only purge submissions during the review period</h1>");
}

$qry = "SELECT subId,subPwd,title,authors,contact,comments2chair FROM {$SQLprefix}submissions WHERE status!='Withdrawn' AND format IS NULL ORDER BY subID";
$subArray = pdo_query($qry)->fetchAll(PDO::FETCH_ASSOC);

$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
tr {vertical-align: top;}
div {width: 90%;}
.fixed { font: 14px monospace; }
div.indented {position: relative; left: 25px;}
</style>
<title>Purge pre-registrations to $cName</title>
</head>
<body>
$links
<hr/>
<h1>Purge pre-registrations to $cName</h1>
<p>Below is a list of pre-registered submissions that did not upload a
submission file to the server. You can use this form to withdraw these
submissions: either use the 'withdraw' links to withdraw individual
submissions, or the 'Withdraw All' button at the bottom of this form
to withdraw all the checked submissions at once.</p>

<form accept-charset="utf-8" action="doPurgeNonSubmissions.php" enctype="multipart/form-data" method=post>
<table><tbody>
EndMark;

foreach($subArray as $sb) {
  $subId = $sb['subId'];
  $subPwd = htmlspecialchars($sb['subPwd']);
  $authors = htmlspecialchars($sb['authors']);
  if (empty($sb['comments2chair'])) $comment = '';
  else $comment = "<br/><b>Comment:</b> ".htmlspecialchars($sb['comments2chair']);

  print <<<EndMark
<tr><td><input type=checkbox name="purged[$subId]" checked="checked"/></td>
  <td>$subId.</td>
  <td><a target=_blank href="../review/submission.php?subId=$subId">{$sb['title']}</a> <small><span style="background: lightgrey;">[<a target=_blank href="../submit/withdraw.php?subId=$subId&amp;subPwd=$subPwd">withdraw</a>]</span></small><br/>
{$sb['authors']}<br/>Contact: <tt>{$sb['contact']}</tt>$comment</td></tr>
<tr><td colspan=3></td></tr>

EndMark;
}

print <<<EndMark
</tbody></table>
<input type=checkbox name="notifyByEmail"/> Send notification email to authors of withdrawn papers. <small>(If this box is UNchecked, withrdawal notification emails will be sent only to the program chair.)</small><br/>
<input type=submit value="Withdraw All"/>
</form>
<hr/>
$links
</body></html>
EndMark;
?>
