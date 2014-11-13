<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$revId = (int) $chair[0];
$revName = htmlspecialchars($chair[1]);

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html>
<html>
<head><meta charset="utf-8">
<style type="text/css">
div.darkbg { background-color:#dddddd; }
</style>
<title>Approve Outgoing Messages</title>
</head>
<body>
$links
<hr />
<h1>Approve Outgoing Messages</h1>

EndMark;

if (isset($_GET['mid'])) {
  $mid = (int) $_GET['mid'];

  $qry = "SELECT pst.subject, pst.comments, ms.subId, ms.textdata
    FROM {$SQLprefix}misc ms, {$SQLprefix}posts pst
    WHERE ms.id=$mid AND ms.type=1 AND pst.postId=ms.numdata";
  $record = pdo_query($qry)->fetch(PDO::FETCH_ASSOC);
  if (!$record) exit("message not found\n<hr/>{$links}\n</body></html>");

  if (isset($_GET['rejectMsg'])) {
    pdo_query("DELETE IGNORE FROM {$SQLprefix}misc WHERE id=$mid");
    exit("Message rejected\n<hr/>{$links}\n</body></html>");
  }

  $subId = (int) $record['subId'];
  $pos = strpos($record['textdata'], ';;'); // 1st ';' in text
  $emlSbjct = substr($record['textdata'], 0, $pos);
  $msg = substr($record['textdata'], $pos+2);

  $prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
  $token = substr(sha1(CONF_SALT.'auxCom'.$mid),0,12);
  $msg .= "\n--------------------\nTo reply to this comment, use the URL:\n"
    ."  $prot://".BASE_URL."submit/replyToComment.php?mid=$mid&auth=$token\n\n"
    ."Note that *you can only respond once*, the above link will be invalidated\n"
    ."after you submit your response.";

  $qry = "SELECT contact FROM {$SQLprefix}submissions WHERE subId=$subId";
  $sendTo = pdo_query($qry)->fetchColumn();

  if (isset($_GET['approveMsg'])) {
    my_send_mail($sendTo, $emlSbjct, $msg, chair_emails(), "Send comments externally.");
    pdo_query("UPDATE IGNORE {$SQLprefix}misc SET type=2 WHERE id=$mid");    
    exit("Message sent\n<hr/>{$links}\n</body></html>");
  }

  // If no decision yet, display message and let the chair decide
  $emlSbjct = htmlspecialchars($emlSbjct);
  $msg = nl2br(htmlspecialchars($msg)); 
  $pstSbjct = htmlspecialchars($record['subject']);
  $cmnt = nl2br(htmlspecialchars($record['comments']));
  print <<<EndMark
<dl>
  <dt>Message-board comment</dt>
    <dd><i>Subject:</i> $pstSbjct<br/>
        <div class="darkbg"><tt>$cmnt</tt></div><br/></dd>
  <dt>Email message</dt>
    <dd><i>Subject:</i> $emlSbjct<br/>
        <div class="darkbg"><tt>$msg</tt></div></dd>
</dl> 
<p>
<form action="approveEmails.php">
<input type="hidden" name="mid" value="$mid">
<input type="submit" name="approveMsg" value="Send it!"> or
<input type="submit" name="rejectMsg" value="Reject message">
</form></p><hr/>
$links
EndMark;
  exit("\n</body></html>");
}

// Get all the type-1 records (messages waiting for approval)
$qry = "SELECT ms.id, pst.subject, pst.subId
  FROM {$SQLprefix}misc ms, {$SQLprefix}posts pst
  WHERE ms.numdata=pst.postId AND ms.type=1 ORDER BY pst.subId";
$list = pdo_query($qry)->fetchAll(PDO::FETCH_ASSOC);

echo "<ul>\n";
foreach ($list as $record) {
  echo "<li>Submission ".$record['subId'].': <a href="approveEmails.php?mid='.$record['id'].'">'.$record['subject']."</a></li>\n";
}
echo "</ul>\n<hr/>{$links}\n\n</body></html>";
?>