<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 /********* standard header for the /review directory ************/

chdir('..');     // This is a header file for a sub-directory
if (!file_exists('./includes/confConstants.php')) { // Not yet customized
  header("Location: ../chair/customize.php");
  exit();
}
require './includes/confConstants.php'; 
require './includes/confUtils.php';

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
if (!defined('REVIEW_PERIOD') && 
    (!isset($preReview) || $preReview!==true) && $pcMember[0]!=CHAIR_ID) {
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