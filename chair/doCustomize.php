<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (PERIOD>PERIOD_SETUP) die("<h1>Installation Already Customized</h1>");

if (isset($_GET['username']) && isset($_GET['password'])) {
  $urlParams = '?username='.$_GET['username'].'&password='.$_GET['password'];
}
else { $urlParams = ''; }

// Read all the fields, stripping spurious white-spaces

$longName    = trim($_POST['longName']) ;
$shortName   = trim($_POST['shortName']);
$confYear    = trim($_POST['confYear']) ;
$confURL     = trim($_POST['confURL'])  ;

$subDeadline = trim($_POST['subDeadline']);
$cameraDeadline= trim($_POST['cameraDeadline']);
$nCats = isset($_POST['nCats']) ? ((int) $_POST['nCats']) : 0;
if ($nCats > 100) { die("Cannot handle more than 100 categories"); }

$nFrmts = isset($_POST['nFrmts']) ? ((int) $_POST['nFrmts']) : 0;
if ($nFrmts > 50) { die("Cannot handle more than 50 supported formats"); }

$maxGrade  = isset($_POST['maxGrade']) ? ((int) trim($_POST['maxGrade'])) : 6;
if (($maxGrade < 2) || ($maxGrade > 9)) { $maxGrade =6; }

$nCrits = isset($_POST['nCrits']) ? ((int) $_POST['nCrits']) : 0;
if ($nCrits > 20) { die("Cannot handle more than 20 evaluation criteria"); }

// Check that the required fileds are specified

if (empty($longName) || empty($shortName) || empty($confYear)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify the conference short and long names and year");
}

if ($confYear < 1970 || $confYear > 2099) {
  print "<h1>Wrong format for the conference year</h1>\n";
  exit("Year must be an integer between 1970 and 2099");
}


/* We are ready to start customizing the installation */
$qry = "UPDATE parameters SET\n"
   . "  longName='"  .my_addslashes($longName, $cnnct)."',\n"
   . "  shortName='" .my_addslashes($shortName, $cnnct)."',\n"
   . "  confYear="   .intval($confYear).",\n";

if (empty($confURL)) $confURL = '.';
$qry .= "  confURL='"   .my_addslashes($confURL, $cnnct)."',\n"
   . "  subDeadline=".intval($subDeadline).",\n"
   . "  cmrDeadline=".intval($cameraDeadline).",\n"
   . "  maxGrade="   .intval($maxGrade).",\n"
   . "  maxConfidence=3,\n";

$flags = 0;
if (isset($_POST['revPrefs']))  $flags |= FLAG_PCPREFS;
if (isset($_POST['anonymous'])) $flags |= FLAG_ANON_SUBS;
if (isset($_POST['affiliations'])) $flags |= FLAG_AFFILIATIONS;
if (isset($_SERVER['HTTPS']))   $flags |= FLAG_SSL;
$qry .= "  flags=$flags,\n  period=".PERIOD_SUBMIT.",\n";

if ($nFrmts <= 0) $confFormats = 'NULL';
else {
  $sc = $confFormats = '';
  for ($i=0; $i<$nFrmts; $i++) {
    $frmt = "frmt_{$i}_";
    $nm   = trim($_POST["{$frmt}desc"]);
    $ext  = trim($_POST["{$frmt}ext"]);
    $mime = trim($_POST["{$frmt}mime"]);
    $confFormats .= $sc . "$nm($ext, $mime)";
    $sc = ';';
  }
  $confFormats = "'".my_addslashes($confFormats, $cnnct)."'";
}
$qry .= "  formats=$confFormats,\n";

if ($nCats <= 0) $categories = 'NULL';
else {
  $sc = $categories = '';
  for ($i=0; $i<$nCats; $i++) {
    $categories .= $sc . trim($_POST["category_{$i}"]);
    $sc = ';';
  }
  $categories = "'".my_addslashes($categories, $cnnct)."'";
}
$qry .= "  categories=$categories,\n";

if ($nCrits <= 0) $criteria = 'NULL';
else {
  $sc = $criteria = '';
  for ($i=0; $i<$nCrits; $i++) {
    $cr = "criterion_{$i}_";
    $nm = trim($_POST["{$cr}name"]);
    $mx = isset($_POST["{$cr}max"]) ? (int) trim($_POST["{$cr}max"]) : 3;
    if (($mx < 2) || ($mx>9)) $mx = 3;
    if (!empty($nm)) {
      $criteria .= $sc . "$nm($mx)";
      $sc = ';';
    }
  }
  $criteria = "'".my_addslashes($criteria, $cnnct)."'";
}
$qry .= "  extraCriteria=$criteria  WHERE isCurrent=1";

db_query($qry, $cnnct, "Cannot set conference parameters: ");

// insert a dummy submission, so numbering will start at 101
$qry = "INSERT INTO submissions SET subId=100, title = 'Dummy',
        authors = 'Dummy', contact = 'Dummy', abstract = 'Dummy',
        status = 'Withdrawn', subPwd = 'Dummy'";
mysql_query($qry, $cnnct);
$qry = "DELETE FROM submissions WHERE subId=100";
mysql_query($qry, $cnnct);

// Set the PC Chair name in the database
$chrName = my_addslashes($shortName.$confYear)." Chair";
$qry = "UPDATE committee SET name='$chrName' WHERE revId=".CHAIR_ID;
db_query($qry, $cnnct);

// All went well, send email to chair and go to confirmation page
$hdr = "From: $chrName <".CHAIR_EMAIL.">";
$sbjct = "Submission and review site for $shortName $confYear is operational";

$prot = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
$baseURL = $prot.'://'.BASE_URL;

$chrPwd = isset($_GET['password']) ?
  ("password ".$_GET['password']) : "the password that was sent to you.";

$msg =<<<EndMark
The submission and review site for $shortName $confYear is customized
and ready for use. The start page for submitting papers is:

  {$baseURL}submit/

The administration page is:

  {$baseURL}chair/

You can login to the administration page using your email address
as username, and with $chrPwd

EndMark;

$sndTo = CHAIR_EMAIL. ", ". ADMIN_EMAIL;
$success = mail($sndTo, $sbjct, $msg, $hdr);

if (!$success) {
  $err = "Cannot send email to $sndTo";
  if (isset($_GET['password'])) $err .= $err = " (pwd=".$_GET['password'].")";
  error_log(date('Y.m.d-H:i:s ')."$err. {$php_errormsg}\n", 3, LOG_FILE);
}

// if in testing mode: insert dummy submissions/reviewers
if (file_exists('testingOnly.php')) {
  header("Location: testingOnly.php{$urlParams}");
}
else header("Location: receipt-customize.php{$urlParams}");
?>
