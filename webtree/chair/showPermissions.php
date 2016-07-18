<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

$qry = "SELECT subId,title,flags FROM {$SQLprefix}submissions WHERE status='Accept'";
$res = pdo_query($qry);

print <<<EndMark
<!doctype HTML>
<html>
<head><meta charset="utf-8">
<title>Show Permissions</title>
<style type="text/css">
  td.yes {color:green;}
  td.no {color:red;}
</style>
</head>

<body>
<h1>Show Permission</h1>
<p>
For each accepted submission, the table below show whether the authors agreed to
make public their presentation slides, a recorded viedo of their presentation,
and other "auxiliary material" related to their work (such as data or source
code).
</p>
<table>
<tbody>
<tr>
<th>subId</th><th>Title</th><th>Slides</th><th>Video</th><th>Others</th>
</tr>
EndMark;
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  print "<tr><td>".$row[0]."</td><td>".substr($row[1],0,60)."</td>\n";
  print ($row[2]&FLAG_CONSENT_SLIDES)? 
    "<td class='yes'>yes</td>" :  "<td class='no'>NO</td>";
  print ($row[2]&FLAG_CONSENT_VIDEO)? 
    "<td class='yes'>yes</td>" :  "<td class='no'>NO</td>";
  print ($row[2]&FLAG_CONSENT_OTHER)? 
    "<td class='yes'>yes</td>" :  "<td class='no'>NO</td>";
  print "</tr>\n";
}
?>
</tbody>
</table>
</body>
</html>
