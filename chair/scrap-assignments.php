<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';
$cnnct = db_connect();

if (isset($_POST["clearAll"])) {
  db_query("UPDATE assignments SET sktchAssgn=0 WHERE sktchAssgn!=-1", $cnnct);
}
else if (isset($_POST["reset2visible"])) {
  db_query("UPDATE assignments SET sktchAssgn=assign", $cnnct);
}

header('Location: assignments.php');
?>
