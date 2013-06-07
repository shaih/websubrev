<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, ...)
$revId   = (int) $pcMember[0];
$disFlag = (int) $pcMember[3];
$threaded= (int) $pcMember[4];

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1 && !has_reviewed_anything($revId)) exit("<h1>$revName cannot discuss submissions yet</h1>");

$threaded = ($threaded != 0) ? 0 : 1;   // toggle 0<-->1
$qry= "UPDATE {$SQLprefix}committee SET threaded=$threaded WHERE revId=?";
pdo_query($qry, array($revId));

return_to_caller('discuss.php', '', '#discuss');
?>
