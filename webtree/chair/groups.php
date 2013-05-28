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
	if (strcmp($_GET['failed'],'noSub') == 0) $msg = 'Use only valid subId\'s in the list. Try Again';
	if (strcmp($_GET['failed'],'noNum') == 0) $msg = 'Use only numbers in the list. Try Again';
} else if (isset($_GET['success'])) $msg = 'Success! Your group was created ('.htmlspecialchars($_GET['success']).")\n";
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
<h1>Add a New Paper Group</h1>
<form name="groups" action="doGroups.php" method="post">
Papers for discussion:<input name="groupName" type="text"> <br>
Use a comma separated list of subId's without spaces (i.e. 101,102,103)<br>
<input name="newGroup" value="Submit" type="submit">
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
