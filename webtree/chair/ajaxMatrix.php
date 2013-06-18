<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

if (isset($_POST['checkbox'])) { // record changes to a single checkbox
  $revId = (int) $_POST['revId'];
  $subId = (int) $_POST['subId'];
  $assign = (int) $_POST['assign'];
  // update the databse, without overwiting conflicts
  $db->exec("INSERT IGNORE into {$SQLprefix}assignments SET subId=$subId, revId=$revId, sktchAssgn=$assign");
  $qry = "UPDATE {$SQLprefix}assignments SET sktchAssgn=$assign WHERE subId=$subId AND revId=$revId AND assign>=0";
  $db->exec($qry);
  //  error_log(date('Y.m.d-H:i:s ' )."$qry\n",3,LOG_FILE);
  exit(0); // return 0 on success
}
