<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$qry = "SELECT subId,title,authors,contact FROM {$SQLprefix}submissions WHERE status!='Withdrawn' ORDER BY subId";
$subs = pdo_query($qry)->fetchAll(PDO::FETCH_NUM);

if (empty($subs)) die("<h1>No submissions found</h1>");

header('Content-Type: text/tab-separated-values');
$hdr = 'Content-Disposition: attachment; filename="'
       .CONF_SHORT.CONF_YEAR.'submissions.tsv"';
header($hdr);
print "subId\tTitle\tAuthors\tContact\n";
foreach ($subs as $sb) {
  print $sb[0]."\t".$sb[1]."\t".$sb[2]."\t".$sb[3]."\n";
}
?>