<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication=true;
require 'header.php';  // defines $pcMember=array(revId, name, ...)

if (!REVPREFS) {  // reviewing preferences is disabled
  header("Location: index.php");
  exit();
}

$revId = (int) $pcMember[0];
$classes = array('zero', 'one', 'two', 'three', 'four', 'five');
$semantics = array(
  'I have a conflict of interests with this submission',
  'I do not want to review this submission',
  'I prefer not to review this submission (but can do it if I have to)',
  'No preferences (default): I can review this submission if need be',
  'I would like to review this submission',
  'This submission is one of my favorites to review'
);

$links = show_rev_links(4);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
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
<title>Review Preferences for $pcMember[1]</title>
</head>

<body>
$links
<hr />
<h1>Review Preferences for $pcMember[1]</h1>
Please indicate your reviewing preferences. For each submisssion
you can specify one of the following options: <br /><br />

<table><tbody>

EndMark;
for ($i=0; $i<6; $i++) {
  print "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td class=\"{$classes[$i]}\">$i</td>
  <td style=\"text-align: left;\">{$semantics[$i]}</td>\n</tr>\n";
}

print <<<EndMark
</tbody></table>

<br /><br />
<form action="act-prefs.php" enctype="multipart/form-data" method="post">
<table><tbody>
<tr><th><small>pref</small></th>
  <th class="zero">0</th> 
  <th class="one">1</th> 
  <th class="two">2</th>
  <th class="three">3</th>
  <th class="four">4</th>
  <th class="five">5</th>
</tr>

EndMark;

$cnnct = db_connect();
$qry = "SELECT s.subId, s.title, a.pref, a.assign
  FROM submissions s LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId
  WHERE s.status!='Withdrawn'
  ORDER BY s.subId";
$res = db_query($qry, $cnnct, "Cannot retrieve submission list: ");
while ($row = mysql_fetch_row($res)) {
  if ($row[3]==-1) continue; // conflict
  // Get the submission details
  $subId = (int) $row[0];
  $title = htmlspecialchars($row[1]);

  $pref = isset($row[2]) ? ((int) $row[2]) : 3;
  if ($pref < 0 || $pref > 5) $pref = 3;

  $checked = array('', '', '', '', '', '');
  $checked[$pref] = ' checked="checked"';
  $cls = $classes[$pref];

  print "<tr>
  <td class=\"$cls\">$pref</td>\n";

  for ($i=0; $i<6; $i++) {
    $cls = $classes[$i];
    $chk = $checked[$i];
    print "  <td class=\"{$cls}\">
    <input type=\"radio\" name=\"sub{$subId}\" value=$i{$chk}>
  </td>\n";
  }

  print "  <td>{$subId}.</td>
  <td style=\"text-align: left;\"><a href=\"submission.php?subId=$subId\">{$title}</a></td>
</tr>\n";
}

print <<<EndMark
</tbody>
</table>
<br />
<input value="Submit My Preferences" type="submit">
</form>
<hr />
$links
</body>
</html>
EndMark;
?>
