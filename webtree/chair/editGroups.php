<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
$links = show_chr_links();
$msg = '';
if (isset($_GET['failed'])) {
	if (strcmp($_GET['failed'],'noSub') == 0) {
		$msg = 'Use only valid subId\'s in the list. Try Again';
	} else {
		$msg = 'At least one group update failed. It\'s likely one group was deleted before the form was submitted.';
	}
} else if (isset($_GET['success'])) $msg = 'Success! The new list of groups was added to the database';
$cnnct = db_connect();
$qry = "SELECT title, subId FROM submissions s WHERE s.subId >= 10000";
$res = db_query($qry, $cnnct);
$html = '';
while ($row = mysql_fetch_assoc($res)) {
	$html .= "<input name='".$row['subId']."' value='".$row['title']."'><br>";
}
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Add a group</title>
</head>
<body>
$links
<hr />
<h2>$msg</h2>
<h1>Edit Paper Group Membership</h1>
<form name="editGroups" action="doEditGroups.php" method="post">
$html
<input name="doEditGroup" value="Submit" type="submit">
</form>
<hr />
$links
</body>
</html>
EndMark;

?>
