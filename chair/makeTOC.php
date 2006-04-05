<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

$cName = CONF_SHORT.' '.CONF_YEAR;
if (!defined('CAMERA_PERIOD')) {
  exit("<h1>Final-versions for $cName not available yet</h1>");
}

$cnnct = db_connect();
$qry = "SELECT s.subId subId, title, authors, nPages, pOrder
    FROM submissions s LEFT JOIN acceptedPapers ac USING(subId)
    WHERE status='Accept' ORDER BY IF(ac.pOrder>0, ac.pOrder, 9999), subId";
$res = db_query($qry, $cnnct);
$papers = array();
while ($row = mysql_fetch_assoc($res)) { $papers[] = $row; }

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 {text-align: center;}
tr { vertical-align: top; }
</style>
<title>Generate Indexes for $cName Proceedings</title>
</head>
<body>
$links
<hr />
<h1>Generate Indexes for $cName Proceedings</h1>
Use this form to aurtomatically generate the table-of-contents, author index,
and external-reviewer list for the proceedings. This form will generate a
LaTeX2e file in the llncs format of Springer, but persumably the various
tables can be used also for other LaTeX styles.<br/>
<br/>
For each accepted paper you should specify the number of pages and its
order in the proceedings (e.g., 1&nbsp;for the first paper in the proceedings,
2&nbsp; for the second paper, etc.). Leave the order field blank for papers
that will not appear in the proceedings (such as merged papers, etc).
You can also correct the titles and author list of papers (for example
to add LaTeX accent commands for European characters).<br/>
<br/>

<form action="act-makeTOC.php"  enctype="multipart/form-data" method="post">
<table cellspacing=3><tbody>
<tr><th>subId</th><th>nPages</th><th>order</th>
  <th>title &amp; authors (separate authors by semi-colon)</th>
</tr>

EndMark;

// each paper is an array(subId, title, authors, pOrder, nPages). 
foreach ($papers as $p) {
  $subId = (int) $p["subId"];
  $title = htmlspecialchars(trim($p["title"]));
  $authors = trim($p["authors"]);
  $authors = htmlspecialchars(str_replace(" and ", "; ", $authors));
  $pOrder = (int) trim($p["pOrder"]);
  if ($pOrder==0) $pOrder = NULL;
  $nPages = htmlspecialchars(trim($p["nPages"]));
  print <<<EndMark
<tr><td style="text-align: right;">$subId. </td>
  <td><input name="nPages[$subId]" type="text" value="$nPages" size=3></td>
  <td><input name="pOrder[$subId]" type="text" value="$pOrder" size=3></td>
  <td><input name="title[$subId]" type="text" value="$title" size=80></br>
      <input name="authors[$subId]" type="text" value="$authors" size=80><br/>
      <br/>
  </td>
</tr>

EndMark;
}

print <<<EndMark
</tbody></table>
<input type="hidden" name="makeTOC" value="on">
<input type="submit" value="Generate Table-of-Contents and Author-Index">
</form>
<hr/>
$links
</body>
</html>
EndMark;

?>
