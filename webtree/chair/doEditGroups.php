<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
if(!isset($_POST['doEditGroup'])) die();
$cnnct = db_connect();
$qry = "SELECT title, subId FROM submissions s WHERE s.subId >= 10000";
$res = db_query($qry, $cnnct);
$html = '';
$success = true;
$qryArr = array();
while ($row = mysql_fetch_assoc($res)) {
	$title = $_POST[$row['subId']];
	$titleURL=urlencode($title);
	$title=my_addslashes($title);
	$title=str_replace(' ','',$title);
	$titleTest = explode(',', $title);
	$qry= "UPDATE submissions s SET s.title='".$title."' WHERE s.subId='".$row['subId']."';";
	
	$qryTest = "SELECT * FROM submissions WHERE subId in (".$title.") AND status !='Withdrawn'";
	if(mysql_num_rows(db_query($qryTest,$cnnct)) != count($titleTest)) {
		header("Location: editGroups.php?failed=noSub");
		exit();
	}
	$qryArr[] = $qry;
}
foreach ($qryArr as $qry) {
	db_query($qry, $cnnct);
}
header("Location: editGroups.php?success=TRUE");
