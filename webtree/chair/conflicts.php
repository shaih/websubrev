<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

// Get current preferences/assignments

// Prepare an array of submissions and an array of PC members
$qry = "SELECT subId, title, authors FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$res = pdo_query($qry);
$subArray = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  list($subId, $title, $authors) = $row;
  $subId = (int) $subId;
  $subArray[$subId] = array($title, $authors);
}

$qry = "SELECT revId, name FROM {$SQLprefix}committee WHERE !(flags & " . FLAG_IS_CHAIR . ") ORDER BY revId";
$res = pdo_query($qry);
$committee = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $revId = (int) $row[0];
  $name = $row[1];
  $committee[$revId] = $name;
}

// read the current blocked submissions from the database
$qry = "SELECT subId, revId, pref, sktchAssgn FROM {$SQLprefix}assignments ORDER BY revId, subId";
$res = pdo_query($qry);
$current = array();
$authorOf = array();
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = (int) $row[0];
  $revId = (int) $row[1];
  $pref = (int) $row[2];
  $assign = (int) $row[3];
  if (!isset($committee[$revId]) || !isset($subArray[$subId])) continue;

  if (!isset($current[$revId])) $current[$revId] = array();

  $current[$revId][$subId] = array('assign'=>$assign, 'pref'=>$pref);
}
$qry = NULL;

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
.submitButton {
    height:40px;
    font-family:verdana,arial,helvetica,sans-serif;
    font-size:16px;
    font-weight:bold;
}
</style>
<link rel="stylesheet" type="text/css" href="../common/review.css" />

<title>Blocking Access To Specific Submissions</title>
</head>
<body>
$links
<hr />
<h1>Blocking Access To Specific Submissions</h1>

EndMark;

// If the chair made some choises on the form, have him/her 
// confirm them before committing them to the database
if (isset($_POST['blockAccess'])) {

  // erase all the assign=-1 from the $current array (to be
  // replaced by whatever is specified in the $_POST array)
  foreach ($current as $revId => $pcmList) {
    foreach ($pcmList as $subId => $a) if ($a['assign']<0) {
      $current[$revId][$subId]['assign'] = 0;
    }
  }

  // update the current-assignment array with the chair's choises
  foreach ($_POST['blocked'] as $revId => $val) {
    if ($revId <= 0 || !isset($committee[$revId])) continue;

    $val = explode(',', $val);
    foreach ($val as $subId) {
      $subId = (int) trim($subId);
      if ($subId <= 0 || !isset($subArray[$subId])) continue;

      if (!isset($current[$revId][$subId])) {
	if (!isset($current[$revId])) { $current[$revId] = array(); } 
	$current[$revId][$subId] = array('assign'=>-1, 'pref'=>3);
      }
      else $current[$revId][$subId]['assign'] = -1;
    }
  }

  foreach ($_POST['authorOf'] as $revId => $val) {
    if ($revId <= 0 || !isset($committee[$revId])) continue;

    $val = explode(',', $val);
    foreach ($val as $subId) {
      $subId = (int) trim($subId);
      if ($subId <= 0 || !isset($subArray[$subId])) continue;

      if (!isset($current[$revId][$subId])) {
	if (!isset($current[$revId])) { $current[$revId] = array(); } 
	$current[$revId][$subId] = array('assign'=>-2, 'pref'=>3);
      }
      else $current[$revId][$subId]['assign'] = -2;
    }
  }

  // Ask the user to confirm the blocks
  print <<<EndMark
<form accept-charset="utf-8" action="doConflicts.php" enctype="multipart/form-data" method="post">
<h2>Please confirm:
<input type="submit" class="submitButton" value="Yes, block the submissions below"/></h2>
<dl>
EndMark;

  foreach ($current as $revId => $pcmList) {
    $html = '';
    foreach ($pcmList as $subId => $a) if ($a['assign']<0) {
      $authorOf = '';
      if ($a['assign']==-2) $authorOf = "<span style='color: red;'>(author)</span> ";
      list($title, $authors) = $subArray[$subId];
      $html .= "  <dd>{$subId}. {$authorOf}<a href='../review/submission.php?subId={$subId}'>{$title}</a>\n";
      $html .= "  <dd>&nbsp;&nbsp;&nbsp;&nbsp;<i>{$authors}</i>\n";
      $html .= "  <dd><input type='hidden' name='block[$revId][$subId]' value='".$a['assign']."'>\n";
    }
    if (!empty($html)) {
      $name = $committee[$revId];
      print "<dt><b>{$name}:</b>\n{$html}\n<br />\n";
    }
  }
  print <<<EndMark
</dl>
<input type="submit" class="submitButton" value="Confirm: Block the submissions above"/>
</form>
<hr />
<hr />
EndMark;
}      // if (isset($_POST['blockAccess']))

/*********************************************************************/
/******************** Display the main form  *************************/
/*********************************************************************/

print <<<EndMark
<h2>Specify blocked submissions</h2>
<p>For each PC member, put a comma-separated list of submission-IDs that
this member should NOT have access to. You can optionally also mark a
PC member as an author of a submission, this has the same effect as
blocking the reviewer from seeing submission, and in addition mark this 
submission as a PC-member submission. (This will make it easier for you
to identify PC-members submissions in the various lists.)</p>
<p>
A list of submissions and their IDs is found <a href="#sublist">at the
bottom of this page</a></p>.

<form accept-charset="utf-8" action="conflicts.php" enctype="multipart/form-data" method="post">
<table>
<tbody>
<tr><th>PC member</th>
  <th>Maybe&nbsp;author&nbsp;of?&nbsp;</th>
  <th>Mark&nbsp;author&nbsp;of&nbsp;</th>
  <th>Other&nbsp;blocked&nbsp;submissions&nbsp;</th>
  <th>Asked to block</th></tr>

EndMark;

$class = 'darkbg';
$stmt = $db->prepare("SELECT subId FROM {$SQLprefix}submissions WHERE authors like ?");
foreach ($committee as $revId => $name) {
  $sep1 = $sep2 = $sep3 = $asked2block = $blocked = $authorOf = '';
  if (isset($current[$revId]))
    foreach ($current[$revId] as $subId => $a) {
    if ($a['pref']==0) { $asked2block .= $sep1 . $subId; $sep1 = ', '; }
    if ($a['assign']==-1) { $blocked  .= $sep2 . $subId; $sep2 = ', '; }
    if ($a['assign']==-2) { $authorOf .= $sep3 . $subId; $sep3 = ', '; }
  }
  $maybeAuthor = $sep4 = '';
  if ($stmt->execute(array($name)))
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
      $maybeAuthor .= $sep4 . $row[0];
      $sep4 = ', ';
    }
  print "<tr class='$class'><td>$name:</td>
  <td class='ctr'>$maybeAuthor</td>
  <td><input name='authorOf[$revId]' size='15' value='$authorOf'/></td>
  <td><input name='blocked[$revId]' type='text' size='30' value='$blocked'/></td>
  <td>&nbsp;{$asked2block}</td>
</tr>\n";
  if ($class=='darkbg') $class = 'lightbg';
  else                  $class = 'darkbg';
}

print <<<EndMark
</tbody></table>
<input type="submit" value="Block Access to Submissions">
<input type="hidden" name="blockAccess" value="on">
</form>

<h2><a name="sublist">Submission list</a></h2>
<dl>

EndMark;

foreach($subArray as $subId => $sb) {
  list($title, $authors) = $sb;
  if (strlen($title)>102) $title = substr($title, 0, 100) . '...';
  print "<dt>{$subId}. <a href=\"../review/submission.php?subId={$subId}\">"
    . htmlspecialchars($title) . "</a>\n";
  print "  <dd><i>".htmlspecialchars($authors)."</i><br /><br />\n";
}

print "</dl>
<hr />
$links
</body>
</html>\n";
?>
