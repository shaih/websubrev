<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (PERIOD==PERIOD_FINAL) exit("<h1>The Site is Closed</h1>");

if (isset($_POST['shutdown']) && $_POST['shutdown']=="yes") {
  backup_conf_params(PARAMS_VERSION);
  pdo_query("UPDATE {$SQLprefix}parameters SET version=version+1, period=?",
	    array(PERIOD_FINAL));
}

// All went well, go back to administration page
header("Location: index.php");
?>
