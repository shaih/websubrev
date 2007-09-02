<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';
$subId=0;
$revId = (int) $pcMember[0];
$sttsCodes = array("None"=>"NO",
		   "Reject"=>"RE",
		   "Perhaps Reject"=>"MR",
		   "Needs Discussion"=> "DI",
		   "Maybe Accept"=>"MA",
		   "Accept"=>"AC");

$cnnct = db_connect();
foreach ($_POST as $key => $val) {
  if (strncmp($key, 'subStts', 7)!=0 || empty($val))
    continue;

  $subId = (int) substr($key, 7);
  if ($subId<=0) continue;

  $status = my_addslashes(trim($val), $cnnct);
  $qry = "UPDATE submissions SET status='$status', lastModified=NOW() WHERE subId={$subId} AND status!='$status'";
  db_query($qry, $cnnct);

  // If status changed, send email to those who asked for it
  if (mysql_affected_rows($cnnct)==1) {
    $qry = "SELECT c.email, c.flags FROM assignments a, committee c
  WHERE c.revId=a.revId AND a.subId=$subId AND a.revId!=$revId AND a.assign!=-1 AND a.watch=1";
    $res = db_query($qry, $cnnct);
    $notify = $comma = '';
    while ($row = mysql_fetch_row($res)) {
      $flags = $row[1];
      if ($flags & FLAG_EML_WATCH_EVENT) {
	$notify .= $comma . $row[0];
	$comma = ', ';
      }
    }
    if (!empty($notify)) {
      $sbjct = "Submission $subId to ".CONF_SHORT.' '.CONF_YEAR
	.': moved to '.$sttsCodes[$status];
      my_send_mail($notify, $sbjct, '');
    }
  }

  // insert an entry to the acceptedPapers table if needed
  if ($status='Accept') {
    $qry = "SELECT 1 from acceptedPapers where subId={$subId}";
    $res = db_query($qry, $cnnct);
    if (mysql_num_rows($res)<=0) {
      db_query("INSERT INTO acceptedPapers SET subId={$subId}", $cnnct);
    }
  }
}

if ($subId>0 && !isset($_POST['noAnchor']))
     $anchor="#stts{$subId}";
else $anchor="";
return_to_caller('index.php', '', $anchor);
?>
