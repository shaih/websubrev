<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)
$revId = (int) $pcMember[0];
if (($subId = (int) $_GET['subId']) <= 0)
  exit("Submission must be specified");
$notice = '';
if (isset($_GET['save']) && $_GET['save']=='Okay')
  $notice = '<span style="background-color: green;">Tags saved successfully</span>';

$title = pdo_query("SELECT title FROM {$SQLprefix}submissions WHERE subId=$subId")->fetchColumn();
if (empty($title)) exit("Submission not found");

if ($isChair = is_chair($revId)) {
  $chairOnly = 'The chair(s) can set a chair-only tag by preceding it with the special character \'<b><tt>$</tt></b>\', making it visible only to the PC chair(s), rather than all the reviewers.';
  $chrOnly2 = "; &#36;Submissions to vote on";
  $chrOnly3 = 'a chair-only tag "&#36;Apple",';
} else {
  $chairOnly = $chrOnly2 = $chrOnly3 = '';
}

$qry = "SELECT tagName FROM {$SQLprefix}tags WHERE subId=? AND ";
if ($isChair) $qry .= '(type=? OR type<=0)';
else          $qry .= 'type IN (?,0)';
$qry .= ' ORDER BY tagName';
$res = pdo_query($qry,array($subId,$revId));
$tags = array();
while ($t = $res->fetchColumn()) $tags[] = $t;
$tags = implode('; ', $tags);

print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<title>Edit Tags</title>
<link rel="stylesheet" type="text/css" href="../common/review.css"/>
</head>
<body>
$notice
<h1>Edit Tags for submssion $subId</h1>
<h2>$title</h2>
<p>Tags must consist only of alphanumeric, dash, and underscore characters (matching the regular expression <tt>[a-zA-Z0-9\-_]+</tt>). They are case-insensitive, so for example "Apple" and "apple" are treated as the same tag.
</p>
  <p>Tags are "public" by default, so they are visible to everyone and everyone can add and remove them. (Note that removing a public tag will remove it from everyone&#39;s view.) You can set a private tag by preceding it with a tilde (the character '<b><tt>~</tt></b>'), making it visible only to you and no one else.
$chairOnly
There are also public "sticky" tags, which are preceded by '<b><tt>^</tt></b>', that are visible to everyone but can only be added or removed by the chair(s).
</p>
<p>All these variations are considered different tags, and a single submission can have any combination of them. For example it can have a public tag "Apple", another sticky public tag "^Apple", $chrOnly3 and several private tags "~Apple" for several different PC members.
</p>
<p>When searching by tags, preceeding a tag by a minus sign '-' means that the submissions should NOT have that tag. So for example searching for "apple; -~orange" would return all the submissions that have the public tag 'apple' but not the private tag '~orange'.
</p>
<form accept-charset="utf-8" action="doEditTags.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="subId" value="$subId"/>
<textarea style="width: 90%;" name="tags">$tags
</textarea><br/>
A <b><i>semi-colon-separated</i></b> list of tags. For example
<tt>"Inter-disciplinary; ~revisit{$chrOnly2}"</tt>
<p><input type="submit" value="Set Tags"></p>
</form>
</body></html>
EndMark;
