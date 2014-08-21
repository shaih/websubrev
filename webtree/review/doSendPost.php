<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)

$confName = CONF_SHORT . ' ' . CONF_YEAR;
$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];
$isChair = is_chair($revId);

if (isset($_POST['pid'])) { $pid = (int) trim($_POST['pid']); }
else exit("<h1>No Post specified</h1>");

// Get the relevant post from database
$qry = "SELECT subId, revId FROM {$SQLprefix}posts WHERE postId=?";
$post = pdo_query($qry,array($pid))->fetch(PDO::FETCH_ASSOC);
$subId = (int) $post['subId'];
$rid = (int) $post['revId'];
if (!$post || ($rid!=$revId && !$isChair))
  exit("<h1>Post not found or not mine</h1>");

// Check that this reviewer is allowed to discuss submissions
if (!$disFlag || ($disFlag == 2 && !has_reviewed_paper($revId, $subId)))
  exit("<h1>$revName cannot discuss submissions yet</h1>");

// Insert a record for this message
if ($_POST['sendTo']=='others') $type = 3; // send to others now
elseif ($isChair)               $type = 2; // send to authors now
else                            $type = 1; // wait for chair approval

if ($type==3) { // check that we have an email address
  $sendTo = trim($_POST['email']);
  if (empty($sendTo)) exit("Email address must be specified");
}
elseif ($type==2) { // get email address from database
  $qry = "SELECT contact FROM {$SQLprefix}submissions WHERE subId=$subId";
  $sendTo = pdo_query($qry)->fetchColumn();
}

if (!$isChair) $revId = $CHAIR_IDS[0];
$qry = "INSERT INTO {$SQLprefix}misc (subId,revId,type,numdata) VALUES ($subId,$revId,$type,$pid)";
if (!isset($db)) $db = pdo_connect();
$db->exec($qry);
$mid = $db->lastInsertId(); // id of the inserted record

$prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
$token = substr(sha1(CONF_SALT.'auxCom'.$mid),0,12);
$text = $_POST['subject'].';;'.$_POST['msg'];
$qry = "UPDATE {$SQLprefix}misc SET textdata=? WHERE id=? AND type=? AND numdata=?";
pdo_query($qry, array($text,$mid,$type,$pid));

if ($type>1) { // send directly
  $msg = $_POST['msg']
    ."\n--------------------\nTo reply to this comment, use the URL:\n"
    ."  $prot://".BASE_URL."submit/replyToComment.php?mid=$mid&auth=$token\n\n"
    ."Note that *you can only respond once*, the above link will be invalidated\n"
    ."after you submit your response.";

  my_send_mail($sendTo, $_POST['subject'], $msg, chair_emails(), "Send comments externally.");
}
else { // send email to chair for approval
  $msg = "The following message to the authors awaits your approval:\n"
    ."--------------------\n"
    ."Subject: ".$_POST['subject']."\n\n"
    .$_POST['msg']."\n--------------------\n"
    ."Use the following links to approve it:\n"
    ."  $prot://".BASE_URL."chair/approveEmails.php?mid=$mid&approveMsg=yes\n\n"
    ."or see more details at $prot://".BASE_URL."chair/approveEmails.php?mid=$mid\n";

  my_send_mail(chair_emails(), "Approve email to authors of $confName submission $subId", $msg, array());
}
header("Location: index.php?sentMsg=yes");
?>
