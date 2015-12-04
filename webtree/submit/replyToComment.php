<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$bypassAuth = true; // allow access to this script after the deadline
require 'header.php'; // brings in the constants and utils files

if (isset($_GET['notifyOnly'])) exit('<h1>Reply received</h1>');

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$mid= (int) $_GET['mid'];
$token = $_GET['auth'];

if (substr(sha1(CONF_SALT.'auxCom'.$mid),0,12)!=$token)
  exit("authentication failure");

$qry = "SELECT subId, type, textdata FROM {$SQLprefix}misc ms WHERE id=$mid AND type>1";
$record = pdo_query($qry)->fetch(PDO::FETCH_ASSOC);
if (!$record) exit("comment not found");

$subId = (int) $record['subId'];
$pos = strpos($record['textdata'], ';;'); // find 1st ';;' in text
$sbjct = htmlspecialchars(substr($record['textdata'], 0, $pos));
$msg = nl2br(htmlspecialchars(substr($record['textdata'], $pos+2)));

$type = (int) $record['type'];   // 2 - authors, 3 - others
if ($type==3)
  $yourName = '<p>Your name: <input type="text" size="80" name="yourName"></p>';
else
  $yourName = '<input type="hidden" name="yourName" value="Authors">';

if (ANONYMOUS)
  $anonText = 'Please ensure that your reply does not violate the anonymity rules.';
else $anonText = '';

$links = show_sub_links();
print <<<EndMark
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<style type="text/css">
div.darkbg { background-color:#dddddd; }
</style>
<title>Reply to question/comment for $confName submission $subId</title>
</head>
<body>
$links
<hr/>
<h1>Reply to question/comment for $confName submission $subId</h1>

<h3>The question:</h3>
<p><i>Subject:</i> <tt>$sbjct</tt></p>
<div class="darkbg"><tt>$msg</tt></div>

<h3>Your reply:</h3>
<form action="doReply2Comment.php" accept-charset="utf-8" enctype="multipart/form-data" method="POST">
<input type="hidden" name="mid" value="$mid">
<input type="hidden" name="auth" value="$token">
<textarea cols="80" rows="15" name="rply2cmnt">
</textarea>
$yourName
<p>$anonText
Note that <b>you will not be able to modify your reply</b> after submitting this form, so take care to finalize it before submitting.</p>
<input type="submit" value="Submit Reply">
</form>
<hr/>
$links
</body></html>
EndMark;

?>
