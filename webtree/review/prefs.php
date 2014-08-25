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

// Display a warning if too many submissions are marked 0 or 1
$qry = "SELECT COUNT(*) FROM {$SQLprefix}assignments WHERE revId=? AND pref<=1";
$nExtreme = pdo_query($qry, array($revId))->fetchColumn();

$qry = "SELECT COUNT(*) FROM {$SQLprefix}submissions WHERE status!='Withdrawn'";
$nSubmisions = pdo_query($qry)->fetchColumn();

if ($nSubmisions>20 && $nExtreme > $nSubmisions/2) {
  $warningHtml = '<blockquote  style="border: solid red; color: red">'."\n"
    . 'NOTICE: You have '.$nExtreme.' submissions in the 0 and 1 categories. This is an unusually high number, and it may make the chair&#39;s task of assigning submissions to everyone so much harder. Are you sure that you really have so many submissions in these "extreme" categories?'."\n</blockquote>\n";
}
else $warningHtml = "<br/><br/>\n";

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
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
a.tooltips { text-align:left; }
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

$bodyHTML =<<<EndMark
<form accept-charset="utf-8" action="doPrefs.php" enctype="multipart/form-data" method="post">
<table><tbody>
<tr><th></th><th><small>pref</small></th>
  <th class="zero">0</th> 
  <th class="one">1</th> 
  <th class="two">2</th>
  <th class="three">3</th>
  <th class="four">4</th>
  <th class="five">5</th>
  <th><small>detail</small></th>
</tr>

EndMark;

$prefCount = array(0, 0, 0, 0, 0, 0);
$qry = "SELECT s.subId,s.title,s.authors,s.affiliations,s.abstract,s.category,s.keyWords,s.auxMaterial,a.pref,a.assign
  FROM {$SQLprefix}submissions s LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId
  WHERE s.status!='Withdrawn' ORDER BY s.subId";
$res = pdo_query($qry, array($revId), "Cannot retrieve submission list: ");
$zIdx = 2000;
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  if ($row['assign']<0) continue; // conflict
  // Get the submission details
  $subId = (int) $row['subId'];
  $zIdx--;
  $title = htmlspecialchars($row['title']);
  if (ANONYMOUS) $authors = $affiliations = '';
  else {
    $authors = htmlspecialchars($row['authors']);
    $affiliations = htmlspecialchars($row['affiliations']);
    if (empty($affiliations)) $authors .= "<br/>";
    else $authors .= " ($affiliations)<br/>";
  }
  $abstract = $row['abstract'];
  if (strlen($abstract)>1000) $abstract = substr($abstract,0,997) . ' [...]';
  $abstract = '<br/><br/><b>Abstract:</b> ' . htmlspecialchars($abstract);
  $abstract = nl2br($abstract);
  $category = empty($row['category'])? '' :
    htmlspecialchars($row['category']).'/';
  $category .= htmlspecialchars($row['keyWords']);

  $checked = array('', '', '', '', '', '');
  if (isset($row['pref'])) {
    $pref = (int) $row['pref'];
    if ($pref < 0 || $pref > 5) $pref = 3;

    $checked[$pref] = ' checked="checked"';
    $cls = "class=\"$classes[$pref]\"";
  }
  else {
    $pref = 3;
    $cls = '';
  }
  if (isset($row['auxMaterial'])) {
    $aux = "<a href=\"download.php?subId=$subId&amp;aux=yes\" title=\"Download supporting material\"><img src=\"../common/auxi.gif\" alt=\"Aux\" border=0></a>";
  }
  else $aux = '';

  $prefCount[$pref]++;
  $bodyHTML .= "<tr><td><a href=\"download.php?subId=$subId\" title=\"download\"><img src=\"../common/download.gif\" alt=\"download\" border=0></a>{$aux}</td><td $cls>$pref</td>\n";

  for ($i=0; $i<6; $i++) {
    $cls = $classes[$i];
    $chk = $checked[$i];
    $bodyHTML .= "  <td class=\"{$cls}\">
    <input type=\"radio\" name=\"sub{$subId}\" value=$i{$chk}>
  </td>\n";
  }
  $bodyHTML .=<<<EndMark
  <td><a class="tooltips" href="submission.php?subId=$subId" target="_blank" style="z-index:$zIdx;">&nbsp;<img title="" alt="abs" src="../common/smalleye.gif"/><span>
<b>$title</b><br/>
$authors
$category
$abstract
</span></a></td>
  <td>{$subId}.</td>
  <td style="text-align: left;">$title</td>
</tr>

EndMark;
}

$bodyHTML .=<<<EndMark
</tbody>
</table>
<br/>
<input value="Submit My Preferences" type="submit">
</form>

EndMark;

// Display a count of how many submissions are marked at each level
for ($i=0; $i<6; $i++) {
  print "<tr><td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <td class=\"{$classes[$i]}\">$i</td>
  <td style=\"text-align: right;\">({$prefCount[$i]} submissions):&nbsp;</td>
  <td style=\"text-align: left;\">{$semantics[$i]}</td>\n</tr>\n";
}
print "</tbody></table>\n";
print $warningHtml."\n";

print <<<EndMark
$bodyHTML
<hr />
$links
</body>
</html>
EndMark;
?>
