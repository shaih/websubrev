<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';
$confName = CONF_SHORT . ' ' . CONF_YEAR;
$now = utcDate('r');

header("Content-Type: text/plain");
header('Content-Disposition: attachment; filename="sketchAssignments.txt"');
print "# Sketch Assignments for $confName, saved on $now\n";

$qry = "SELECT a.subId,a.revId,s.title,c.name FROM {$SQLprefix}assignments a,{$SQLprefix}submissions s,{$SQLprefix}committee c WHERE sktchAssgn=1 AND s.subId=a.subId AND c.revId=a.revId ORDER BY a.subId,a.revId";
$res = pdo_query($qry);
$curSubId = -1;
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $subId = (int) $row[0];
  $revId = (int) $row[1];
  if ($subId != $curSubId) {
    print "\nSubmission $subId: ".substr($row[2],0,60)."\n";
    $curSubId = $subId;
  }
  print "Reviewer $revId: ".$row[3]."\n";
}
?>
