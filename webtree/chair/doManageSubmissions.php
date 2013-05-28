<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (defined('SHUTDOWN')) {
  exit("<h1>The Site is Closed</h1>");
}

$changeParams = false;
$cnnct = db_connect();

$longName    = CONF_NAME;
$shortName   = CONF_SHORT;
$confYear    = CONF_YEAR;
$confURL     = CONF_HOME;
$regDeadline = REGISTER_DEADLINE;
$subDeadline = SUBMIT_DEADLINE;
$cmrDeadline = CAMERA_DEADLINE;
$period      = PERIOD;

// Read all the fields, stripping spurious white-spaces

$x  = isset($_POST['longName'])  ? trim($_POST['longName'])  : NULL;
if (!empty($x) && $x!=$longName) { $changeParams = true; $longName = $x; }

$x  = isset($_POST['shortName']) ? trim($_POST['shortName']) : NULL;
if (!empty($x) && $x!=$shortName) { $changeParams = true; $shortName = $x; }

$x  = isset($_POST['confYear'])  ? trim($_POST['confYear'])  : NULL;
if (!empty($x) && $x!=$confYear) { $changeParams = true; $confYear = $x; }

$x  = isset($_POST['confURL'])   ? trim($_POST['confURL'])   : NULL;
if (!empty($x) && $x!=$confURL) { $changeParams = true; $confURL = $x; }


$x = isset($_POST['regDeadline']) ?  trim($_POST['regDeadline']) : NULL;
if (!empty($x)) {
  $trg = strtotime($x);
  if ($trg===false || $trg==-1)
     die ("<h1>Unrecognized time format for pre-registration deadline</h1>");
  if ($trg!=$regDeadline) { $changeParams=true; $regDeadline=$trg; }
  //  $period = PERIOD_PREREG;
}
else if (USE_PRE_REGISTRATION && $period <= PERIOD_SUBMIT){ // chair decided not to use pre-reg after all
  $changeParams=true;
  $regDeadline = "NULL";
  //  $period = PERIOD_SUBMIT;
}
if (!isset($regDeadline)) $regDeadline = "NULL";
     
$x = isset($_POST['subDeadline']) ?  trim($_POST['subDeadline']) : NULL;
if (!empty($x)) {
  $tsb = strtotime($x);
  if ($tsb===false || $tsb==-1)
     die ("<h1>Unrecognized time format for submission deadline</h1>");   
  if ($tsb!=$subDeadline) { $changeParams=true; $subDeadline=$tsb; }
}

$x = isset($_POST['cameraDeadline']) ? trim($_POST['cameraDeadline']) : NULL;
if (!empty($x)) {
  $tcr = strtotime($x);
  if ($tcr===false || $tcr==-1)
    die ("<h1>Unrecognized time format for camera-ready deadline</h1>");
  if ($tcr!=$cmrDeadline) { $changeParams=true; $cmrDeadline=$tcr; }
}


$x = isset($_POST['categories']) ? explode(';', $_POST['categories']) : NULL;
if (is_array($x)) {
  $categories = array();
  foreach ($x as $cat)
    $categories[] = trim($cat);
  $changeParams = true;
}
else $categories = NULL;


if (isset($_POST['formats'])) {

  // This contrived logic is because it is more intuitive
  // for the user to "check to keep" than to "check to remove"
  if (is_array($confFormats) && count($confFormats)>0) {
    foreach($confFormats as $ext => $x) { // go over all the formats
      if (!isset($_POST["keepFormats_{$ext}"])) { // should we keep this one?
	unset($confFormats[$ext]);                //   nope: remove it
	$changeParams = true;
      }
    }
  }

  $frmts2add = isset($_POST['addFormats']) ?
                                    explode(';', $_POST['addFormats']) : NULL;
  if (is_array($frmts2add) && (count($frmts2add)>0)) {
    foreach ($frmts2add as $f) if (($x = parse_format($f))!==false) {
      list($desc, $ext, $mime) = $x;
      $confFormats[$ext] = array($desc, $mime);
    }
  }
}

if (isset($_POST['subFlags'])) {
  $anonymous = isset($_POST['anonymous']);
  $affiliations = isset($_POST['affiliations']);
  if (USE_AFFILIATIONS!=$affiliations || ANONYMOUS!=$anonymous) 
    $changeParams = true;
} else {
  $affiliations = USE_AFFILIATIONS;
  $anonymous = ANONYMOUS;
}

############ modified upto here ##############
if ($changeParams) {
  $qry = "UPDATE parameters SET\n"
    . "  longName='"  .my_addslashes($longName, $cnnct)."',\n"
    . "  shortName='" .my_addslashes($shortName, $cnnct)."', "
    . "  confYear="   .intval($confYear).",\n";

  if (empty($confURL)) $confURL = '.';
  $qry .= "  confURL='"   .my_addslashes($confURL, $cnnct)."',\n"
    . "  regDeadline=$regDeadline,\n"
    . "  subDeadline=".intval($subDeadline).",\n"
    . "  cmrDeadline=".intval($cmrDeadline).",\n";

  $flags = CONF_FLAGS & ~(FLAG_ANON_SUBS | FLAG_AFFILIATIONS);
  if ($anonymous)      $flags |= FLAG_ANON_SUBS;
  if ($affiliations)   $flags |= FLAG_AFFILIATIONS;
  $qry .= "  flags=$flags,\n";

  //  if ($period != PERIOD) $qry .= "  period=$period,\n";

  if (is_array($confFormats) && count($confFormats)>0) {
    $fmtString = $sc = '';
    foreach($confFormats as $ext => $f) {
      $fmtString .= $sc.$f[0]."($ext,".$f[1].")";
      $sc = ';';
    }
    $qry .= "  formats='".my_addslashes($fmtString, $cnnct)."',\n";
  }
  else $qry .= "  formats=NULL,\n";

  if (is_array($categories) && count($categories)>0) {
    $catString = $sc = '';
    foreach ($categories as $c) {
      $catString .= $sc.$c;
      $sc = ';';
    }
    $qry .= "  categories='".my_addslashes($catString, $cnnct)."'";
  }
  else $qry .= "  categories=NULL";

  db_query($qry, $cnnct, "Cannot UPDATE conference parameters: ");

} /* if ($changeParams) */

// All went well, go back to administration page
header("Location: index.php");
?>
