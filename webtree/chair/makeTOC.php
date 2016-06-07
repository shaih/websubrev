<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD < PERIOD_REVIEW) die("<h1>Too early to produce TOC</h1>");

$cName = CONF_SHORT.' '.CONF_YEAR;

$qry = "SELECT s.subId subId, title, authors, nPages, volume, pOrder
    FROM {$SQLprefix}submissions s
         LEFT JOIN {$SQLprefix}acceptedPapers ac USING(subId)
    WHERE status='Accept' ORDER BY
       IF(ac.volume>0,ac.volume,9999), IF(ac.pOrder>0,ac.pOrder,9999), subId";
$papers = pdo_query($qry)->fetchAll(PDO::FETCH_ASSOC);

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
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
Use this form to automatically generate the table-of-contents, author index,
and external-reviewer list for the proceedings. This form will generate a
LaTeX2e file in the llncs format of Springer, but persumably the various
tables can be used also for other LaTeX styles.<br/>
<br/>
For each accepted paper you should specify the number of pages and its
order in the proceedings (e.g., 1&nbsp;for the first paper in the proceedings,
2&nbsp; for the second paper, etc.). Optionally you can also specify the
volume number for each paper, this will make the page-numbering reset to 1
at the beginning of every volume.
Leave the order field blank for papers that will not appear in the
proceedings (such as merged papers, etc).  You can also correct the
titles and author list of papers (for example to add LaTeX accent
commands for European characters).<br/>
<br/>

<form accept-charset="utf-8" action="doMakeTOC.php"  enctype="multipart/form-data" method="post">
<table cellspacing=3><tbody>
<tr><th>subId</th><th>nPages</th><th>volume</th><th>order</th>
  <th>title &amp; authors (separate authors by semi-colon)</th>
</tr>

EndMark;

// each paper is an array(subId, title, authors, pOrder, nPages). 
foreach ($papers as $p) {
  $subId = (int) $p["subId"];
  $title = htmlspecialchars(trim($p["title"]));
  $authors = trim($p["authors"]);
  $authors = htmlspecialchars(str_replace(" and ", "; ", $authors));
  $volume = (int) trim($p["volume"]);
  if ($volume==0) $volume = '';
  $pOrder = (int) trim($p["pOrder"]);
  if ($pOrder==0) $pOrder = '';
  $nPages = htmlspecialchars(trim($p["nPages"]));
  print <<<EndMark
<tr><td style="text-align: right;">$subId. </td>
  <td><input name="nPages[$subId]" type="text" value="$nPages" size=3></td>
  <td><input name="volume[$subId]" type="text" value="$volume" size=3></td>
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
<hr />
$links
</body>
</html>
EndMark;

?>
