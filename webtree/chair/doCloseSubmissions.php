<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (PERIOD<PERIOD_SUBMIT) exit("<h1>Submissions Site is not yet Open</h1>");
if (PERIOD>PERIOD_SUBMIT) exit("<h1>Submissions Site is already Closed</h1>");


$updates = "version=version+1";
if (isset($_POST['revPrefsFlag'])) {
  if (isset($_POST['revPrefs'])) $confFlags = CONF_FLAGS | FLAG_PCPREFS;
  else                           $confFlags = CONF_FLAGS & (~FLAG_PCPREFS);
  if (isset($_POST['revAttach'])) $confFlags= CONF_FLAGS | FLAG_REV_ATTACH;
  else                           $confFlags = CONF_FLAGS & (~FLAG_REV_ATTACH);
  $updates .= ", flags=$confFlags";
}

$x = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 0;
if ($x>=2 && $x<=9 && $x!=MAX_GRADE) {
  $updates .= ", maxGrade=$x";
}

$updates .= ", period=".PERIOD_REVIEW;

if (isset($_POST['setCriteria'])) { // Create an array of criteria
  $crtriaString = $sc = '';
  $x = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;
  if (is_array($x) && count($x)>0) {
    foreach ($x as $c) {
      if($cr=parse_criterion($c)) {
	$crtriaString .= $sc.$cr[0]."(".$cr[1].")";
	$sc = ';';
      }
    }
    $crtriaString = "'".my_addslashes($crtriaString, $cnnct)."'";
  }
  else $crtriaString = 'NULL';

  $updates .= ",\n extraCriteria=$crtriaString";
}

// This is where we close the submission site
$cnnct = db_connect();
backup_conf_params($cnnct, PARAMS_VERSION);
db_query("UPDATE parameters SET $updates", $cnnct);

// All went well, go back to administration page
header("Location: index.php");
?>
