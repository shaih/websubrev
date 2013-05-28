<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

if (PERIOD!=PERIOD_REVIEW) exit("<h1>Review Site Is Not Active</h1>");

// Get assignments and reviews
if (isset($_POST["setDiscussFlags"])) {
  $cnnct = db_connect();
  $qry = "SELECT revId, canDiscuss from committee ORDER BY revId";
  $res = db_query($qry, $cnnct);
  $changed = '';
  $flags = '';
  while ($row = mysql_fetch_row($res)) {
    $revId = (int) $row[0];
    $oldFlag = (int) $row[1];
    if(isset($_POST["dscs"][$revId])){
    	$newFlag = $_POST["dscs"][$revId];
    } else {
    	$newFlag = $oldFlag;
    }
    if ($newFlag!=$oldFlag) {
    	$changed .= $revId.",";
    	$flags .= $newFlag.",";
    }
  }
  substr($changed,0,-1);
  substr($flags,0,-1);
  $changed = explode(',', $changed);
  $flags = explode(',', $flags);
  $i = 0;
  if (!empty($changed)) {
  	for ($i = 0; $i < count($flags); $i++) {
  		echo $i;
  		$fl = $flags[$i];
  		$ch = $changed[$i];
  		if(!$ch) continue;		
    	$qry = "UPDATE committee SET canDiscuss= ".$fl." WHERE revId = ".$ch;
    	db_query($qry, $cnnct);
  	}
  }
}
header("Location: overview.php");
?>
