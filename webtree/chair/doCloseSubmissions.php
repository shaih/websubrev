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

$updates = "version=version+1, period=?";
$prms = array(PERIOD_REVIEW);

if (!empty($_POST['revPrefsFlag'])) {
  $updates .= ", flags=?";
  if (!empty($_POST['revPrefs'])) $confFlags = CONF_FLAGS | FLAG_PCPREFS;
  else                            $confFlags = CONF_FLAGS & (~FLAG_PCPREFS);
  if (!empty($_POST['revAttach'])) $confFlags |= FLAG_REV_ATTACH;
  else                            $confFlags &= ~FLAG_REV_ATTACH;
  if (!empty($_POST['auxComm'])) $confFlags |= FLAG_SEND_POSTS_BY_EMAIL;
  else                            $confFlags &= ~FLAG_SEND_POSTS_BY_EMAIL;
  $prms[] = $confFlags;
}

$x = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 0;
if ($x>=2 && $x<=9 && $x!=MAX_GRADE) {
  $updates .= ", maxGrade=?";
  $prms[] = intval($x);
}

$crtriaString = $sc = '';
if (isset($_POST['setCriteria'])) { // Create an array of criteria
  $updates .= ",\n extraCriteria=?";
  $x = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;
  if (is_array($x) && count($x)>0) {
    foreach ($x as $c) {
      if($cr=parse_criterion($c)) {
	$crtriaString .= $sc.$cr[0]."(".$cr[1].")";
	$sc = ';';
      }
    }
  }
  $prms[] = $crtriaString;
}

// This is where we close the submission site
backup_conf_params(PARAMS_VERSION);
pdo_query("UPDATE {$SQLprefix}parameters SET $updates", $prms);

// All went well, go back to administration page
header("Location: index.php");
?>
