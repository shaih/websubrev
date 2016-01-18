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
  if ($trg!=$regDeadline) { 
    $changeParams=true;
    $regDeadline=$trg;
    if ($trg>time()) $period = PERIOD_PREREG;
  }
}
else {
  if (USE_PRE_REGISTRATION && $period <= PERIOD_SUBMIT){ // chair decided not to use pre-reg after all
  $changeParams=true;
  $regDeadline = NULL;
  $period = PERIOD_SUBMIT;
  }}

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

$x = isset($_POST['categories']) ? trim($_POST['categories']) : NULL;
if (!empty($x)) {
  $x = explode(';', $x);
  $categories = array();
  foreach ($x as $cat) $categories[] = trim($cat);
  $changeParams = true;
} else {
  if (!empty($categories)) $changeParams = true;
  $categories = NULL;
}

$checktext = isset($_POST['checktext']) ? $_POST['checktext'] : "";
if ($checktext != OPTIN_TEXT) $changeParams=true;

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

$oldAuxFlag = ((CONF_FLAGS & FLAG_AUX_MATERIAL)? true : false);
$oldAuthConf= ((CONF_FLAGS & FLAG_AUTH_CONFLICT)? true : false);

if (isset($_POST['subFlags'])) {
  $anonymous = isset($_POST['anonymous']);
  $affiliations = isset($_POST['affiliations']);
  $auxMaterial  = isset($_POST['auxMaterial']);
  $authConf     = isset($_POST['authConflict']);
  if (USE_AFFILIATIONS!=$affiliations || ANONYMOUS!=$anonymous
      || $oldAuxFlag!=$auxMaterial || $oldAuthConf!=$authConf)
    $changeParams = true;
} else {
  $affiliations = USE_AFFILIATIONS;
  $anonymous = ANONYMOUS;
  $auxMaterial = $oldAuxFlag;
  $authConf = $oldAuthConf;
}

if ($changeParams) {
  if (empty($confURL)) $confURL = '.';

  $flags = CONF_FLAGS & ~(FLAG_ANON_SUBS | FLAG_AFFILIATIONS | FLAG_AUX_MATERIAL | FLAG_AUTH_CONFLICT);
  if ($anonymous)      $flags |= FLAG_ANON_SUBS;
  if ($affiliations)   $flags |= FLAG_AFFILIATIONS;
  if ($auxMaterial)    $flags |= FLAG_AUX_MATERIAL;
  if ($authConf)       $flags |= FLAG_AUTH_CONFLICT;

  $qry = "UPDATE {$SQLprefix}parameters SET longName=?,shortName=?,confYear=?,confURL=?,subDeadline=?,cmrDeadline=?,flags=?,period=?";
  $prms = array($longName,$shortName,$confYear,$confURL,$subDeadline,$cmrDeadline,$flags,$period);

  if (!empty($regDeadline)) {
    $qry .=",regDeadline=?";
    $prms[] = $regDeadline;
  }
  else $qry .= ",regDeadline=NULL";

  if (is_array($confFormats) && count($confFormats)>0) {
    $fmtString = $sc = '';
    foreach($confFormats as $ext => $f) {
      $fmtString .= $sc.$f[0]."($ext,".$f[1].")";
      $sc = ';';
    }
    $qry .= ",formats=?";
    $prms[] = $fmtString;
  }
  else $qry .= ",formats=NULL";

  if (!empty($categories) && count($categories)>0) {
    $catString = $sc = '';
    foreach ($categories as $c) {
      $catString .= $sc.$c;
      $sc = ';';
    }
    $qry .= ",categories=?";
    $prms[] = $catString;
  }
  else $qry .= ",categories=NULL";

  $qry .= ",optIn=?";
  $prms[] = $checktext;

  //  exit("$qry<pre>".print_r($prms,true)."</pre>");
  pdo_query($qry, $prms, "Cannot UPDATE conference parameters: ");

} /* if ($changeParams) */

// All went well, go back to administration page
header("Location: index.php");
?>
