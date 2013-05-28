<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
if(!isset($_POST['newGroup'])) die();
$cnnct = db_connect();
$qry = "SELECT MAX(subId) from submissions";
$res = db_query($qry, $cnnct);
$row = mysql_fetch_assoc($res);
$newId = $row['MAX(subId)'] > 9999 ? $row['MAX(subId)'] + 1: 10000;
$title=$_POST['groupName'];
$titleURL=urlencode($title);
$title=my_addslashes($title);
$title=str_replace(' ','',$title);
$titleTest = explode(',', $title);
foreach($titleTest as $t){
	if(!ctype_digit($t)){
		header("Location: groups.php?failed=noNum");
		exit();
	}
}
$qryTest = "SELECT * FROM submissions WHERE subId in (".$title.") AND status !='Withdrawn'";
if(mysql_num_rows(db_query($qryTest,$cnnct)) != count($titleTest)) {
	header("Location: groups.php?failed=noSub");
	exit();
}
$qry = "INSERT INTO submissions (subId, title, status, flags) VALUES ($newId, '$title', 'Withdrawn', ".FLAG_IS_GROUP.")";
db_query($qry,$cnnct);


header("Location: groups.php?success=$titleURL");
?>
