<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
if(!isset($_POST['assignSubreviewers'])) die();

$papers = (isset($_POST['subId']) && is_array($_POST['subId'])) ? $_POST['subId'] : array();

$filter_papers = array();
foreach($papers as $p) {
  if (ctype_digit($p))
    $filter_papers[] = $p;
}

if(empty($_POST['sendTo']) || empty($filter_papers)) {
	header("Location: assignSubreviewer.php?fail=true");
	exit();
}

$cnnct = db_connect();

$qry = "SELECT subId, format, contact from submissions where status!='Withdrawn' AND subID IN(".
  implode(", ", $filter_papers).
  ")";

$res = db_query($qry, $cnnct);

$emails = explode(',',$_POST['sendTo']);
$attachments = array();
while($row = mysql_fetch_assoc($res)) {
  $base = SUBMIT_DIR."/";
  $filename = $row['subId'].".".$row['format'];
  $attachments[] = array($base, $filename);
  if(in_array($row['contact'], $emails)) {
  	header("Location:assignSubreviewer.php?conflict=true");
  	exit();
  }
}


if(!my_send_mail($_POST['sendTo'], $_POST['subject'], $_POST['emailBody'],
                 array($pcMember[2]), // CC the PC member on the email
                 "Couldn't send to reveiwer", $attachments,
                 $pcMember[1]."<".$pcMember[2].">" )) {
  header("Location: assignSubreviewer.php?fail=true");
  exit();
}

header("Location: assignSubreviewer.php?success=true");
?>
