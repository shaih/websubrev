<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, William Blair, Adam Udi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';
$links = show_chr_links();

// If we ere redirected with a message, display it.
$msg = '';
if (isset($_GET['failed'])) {
  if ($_GET['failed']=='noSub')
    $msg = "Use only valid subId's in the list. Try Again";
  elseif ($_GET['failed']=='noNum')
    $msg = 'Use only numbers in the list. Try Again';
  elseif ($_GET['failed']=='tooFew')
    $msg = 'A group must have at least two submissions. Try Again';
} 
elseif (isset($_GET['success'])) 
  $msg = 'Success! A group was created/modified ('.htmlspecialchars($_GET['success']).")";

// Get a list of all the groups, these are submissions with large
// submiddion-ID (>9000) and status 'Withdrawn'

$qry = "SELECT title,subId FROM {$SQLprefix}submissions s WHERE s.subId >= 9000 AND status='Withdrawn' AND (flags & ?)";
$res = pdo_query($qry, array(FLAG_IS_GROUP));

$groups = '';
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  $subId = (int) $row['subId'];
  $title = $row['title'];
  $groups .= "Group $subId: <input name='$subId' value='$title'><br/>\n";
}

print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Add/Modify Groups</title>
</head>
<body>
$links
<hr/>
<h3>$msg</h3>
<h1>Add/Modify Groups</h1>
Use this form to create/modify "submission-groups": these are discussion
boards to discuss/compare a group of submissions. They work just like any
other discussion board, except that reviewers that have a conflict with any
of the members in the group cannot access that board.

<form accept-charset="utf-8" name="newgroups" action="doGroups.php" method="post">
EndMark;

if (!empty($groups)) print <<<EndMark
<h2>Edit/Remove Existing Groups</h2>
<p>You can use this form to change the membership of submissions in groups.
Removing all the submission from a group will result in deleting that group.
</p>
$groups
<p>
<input name="EditGroups" value="Modify Existing Groups" type="submit">
</p>
EndMark;

print <<<EndMark
<h2>Add a New Submission Group</h2>
Submissions in the group
(use a comma separated list of submission-IDs, e.g. '<tt>107,115,134</tt>'):
<input name="IDs" size="40" type="text"><br/>
<p>
<input name="newGroup" value="Create a New Group" type="submit">
</p>
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
