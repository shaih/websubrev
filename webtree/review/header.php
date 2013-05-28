<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 /********* standard header for the /review directory ************/
if (!file_exists('../init/confParams.php')) { // Not yet customized
  header("Location: ../chair/initialize.php");
  exit();
}

require_once('../includes/getParams.php');

if (PERIOD==PERIOD_SETUP) die("<h1>Site Not Active Yet</h1>");

if (empty($errorMsg)) {
  $errorMsg = "A valid username/password is needed to access this page";
}

/* Authenticate a PC member unless $needsAuthentication===false (this
 * means authenticate also when $needsAuthentication is undefined or zero)
 */
//$pcMember = array(2, "Shai Halevi", "shaih@watson.ibm.com", 1, 1);
if ($needsAuthentication !== false) { 
  // returns an array (id, name, email, caDiscuss, threaded) or false 
  $pcMember = false;
  if (isset($_SERVER['PHP_AUTH_USER']) &&  isset($_SERVER['PHP_AUTH_PW']))
    $pcMember = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			       $_SERVER['PHP_AUTH_PW']);
  if ($pcMember === false) {
    $confShortName = CONF_SHORT.' '.CONF_YEAR;
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit($errorMsg);
  }
}

// Before the review period: the chair can access everything,
// but others can only access pages that set $preReview=true
if (PERIOD<PERIOD_REVIEW && !is_chair($pcMember[0])
    && (!isset($preReview) || $preReview!==true)
    && (PERIOD<PERIOD_SUBMIT || !USE_PRE_REGISTRATION)) {
    exit("<h1>This area of the review site is not active yet</h1>");
}

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) {
  $_GET  = array_map('stripslashes_deep', $_GET);
  $_POST  = array_map('stripslashes_deep', $_POST);
}

$php_errormsg = ''; // just so we don't get notices when it is not defined.

require 'revFunctions.php';
?>