<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';  // defines $pcMember=array(revId, name, ...)

$revId = (int) $pcMember[0];
$classes = array('zero', 'one', 'two', 'three', 'four', 'five');

// A header for the prefs column (if needed)
$prfHdr = (REVPREFS) ? "\n  <th><small>pref</small></th>" : "";

$links = show_rev_links(4);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">
tr { vertical-align: top; }
td { text-align: center; }
h1 { text-align: center; }
.zero { background: red; }
.one { background: orange; }
.two { background: yellow; }
.three { background: lightgrey; }
.four { background: lightgreen; }
.five { background: green; }
</style>
<title>Watch List for $pcMember[1]</title>
</head>

<body>
$links
<hr />
<h1>Watch List for $pcMember[1]</h1>

<form action="doWatchList.php" enctype="multipart/form-data" method="post">
<table><tbody>
<tr><th><small>rev&#39;ed</small></th>$prfHdr
  <th><small>w.avg</small></th>
  <th><small>status</small></th>
  <th><small>watch</small></th>
</tr>

EndMark;

$cnnct = db_connect();
$qry = "SELECT s.subId, s.title, s.wAvg, s.status,
               a.pref, a.assign, a.watch, r.whenEntered
  FROM submissions s
  LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId
  LEFT JOIN reports r ON r.revId='$revId' AND r.subId=s.subId
  WHERE s.status!='Withdrawn'
  ORDER BY s.subId";
$res = db_query($qry, $cnnct, "Cannot retrieve submission list: ");
while ($row = mysql_fetch_row($res)) {
  list($subId,$title,$wAvg,$status,$pref,$assign,$watch,$revwed) = $row;
  if ($assign==-1) continue; // conflict
  // Get the submission details
  $subId = (int) $subId;
  $title = htmlspecialchars($title);
  $wAvg = isset($wAvg) ? round($wAvg,1) : '*';
  $status = show_status($status);  // show_status in confUtils.php
  if (REVPREFS) { // show preferences
    $pref = isset($pref) ? ((int) $pref) : 3;
    if ($pref < 0 || $pref > 5) $pref = 3;
    $cls = $classes[$pref];
    $prefLine = "\n  <td class=$cls>$pref</td>";
  }
  else $prefLine = '';

  if (isset($watch) && $watch>0) $checked = ' checked="checked"';
  else $checked = '';

  if (isset($revwed)) $revwed = '<img src="../common/smallChk.GIF" alt=x />';
  else $revwed = '';

print <<<EndMark
<tr>
  <td>$revwed</td>$prefLine
  <td>($wAvg)</td>
  <td><small>$status</small></td>
  <td><input type=checkbox name=watch[$subId]{$checked}></td>
  <td>$subId.</td>
  <td style="text-align: left;"><a href="submission.php?subId=$subId">$title</a></td>
</tr>

EndMark;
}

print <<<EndMark
</tbody>
</table>
<br />
<input value="Update Watch List" type="submit" name="updateWatchList">
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
