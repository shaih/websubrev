<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
$notCustomized = true;
require 'header.php';

if (PERIOD>PERIOD_SETUP) die("<h1>Installation Already Customized</h1>");

// Read all the fields, stripping spurious white-spaces

$longName    = trim($_POST['longName']) ;
$shortName   = trim($_POST['shortName']);
$confYear    = trim($_POST['confYear']) ;
$confURL     = trim($_POST['confURL'])  ;
if (empty($confURL)) $confURL = '.';

$regDeadline = trim($_POST['regDeadline']);
$firstPeriod = empty($regDeadline)? PERIOD_SUBMIT : PERIOD_PREREG;

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

$checktext = isset($_POST['checktext']) ? $_POST['checktext'] : "";

// Check that the required fields are specified
if (empty($longName) || empty($shortName) || empty($confYear)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify the conference short and long names and year");
}

if ($confYear < 1970 || $confYear > 2099) {
  print "<h1>Wrong format for the conference year</h1>\n";
  exit("Year must be an integer between 1970 and 2099");
}

$committee = $_POST['cmte'];
if (!is_array($committee) || empty($committee)) 
  exit("<h1>Mandatory fields are missing</h1>\nMust specify at least one chair");
if (count($committee) > 500) die("Cannot handle committees larger than 500");

/* We are ready to start customizing the installation */

$flags = CONF_FLAGS;
$flags &= ~(FLAG_PCPREFS | FLAG_ANON_SUBS | FLAG_AFFILIATIONS
            | FLAG_SSL | FLAG_REV_ATTACH | FLAG_REVISE_AFTER_DEADLINE
            | FLAG_SEND_POSTS_BY_EMAIL | FLAG_AUX_MATERIAL
            | FLAG_AUTH_CONFLICT);
if (isset($_POST['revPrefs']))  $flags |= FLAG_PCPREFS;
if (isset($_POST['revAttach'])) $flags |= FLAG_REV_ATTACH;
if (isset($_POST['anonymous'])) $flags |= FLAG_ANON_SUBS;
if (isset($_POST['affiliations'])) $flags |= FLAG_AFFILIATIONS;
if (isset($_SERVER['HTTPS']))   $flags |= FLAG_SSL;
if (isset($_POST['lateRevisions'])) $flags |= FLAG_REVISE_AFTER_DEADLINE;
if (isset($_POST['sendPostByEmail'])) $flags |= FLAG_SEND_POSTS_BY_EMAIL;
if (isset($_POST['auxMaterial'])) $flags |= FLAG_AUX_MATERIAL;
if (isset($_POST['authConflict'])) $flags |= FLAG_AUTH_CONFLICT;

$sc = $confFormats = '';
for ($i=0; $i<$nFrmts; $i++) {
  $frmt = "frmt_{$i}_";
  $nm   = trim($_POST["{$frmt}desc"]);
  $ext  = trim($_POST["{$frmt}ext"]);
  $mime = trim($_POST["{$frmt}mime"]);
  $confFormats .= $sc . "$nm($ext, $mime)";
  $sc = ';';
}

$sc = $categories = '';
for ($i=0; $i<$nCats; $i++) {
  $categories .= $sc . trim($_POST["category_{$i}"]);
  $sc = ';';
}

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

// Store the conference parameters in the database
$fields = "version,longName,shortName,confYear,confURL,"
  . "regDeadline,subDeadline,cmrDeadline,maxGrade,maxConfidence,"
  . "flags,period,formats,categories,optIn,extraCriteria";
$prms = array(1,$longName,$shortName,$confYear,$confURL,
	      $regDeadline,$subDeadline,$cameraDeadline,$maxGrade,3,
	      $flags,$firstPeriod,$confFormats,$categories,$checktext,$criteria);
$qry = "INSERT INTO {$SQLprefix}parameters ($fields) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
pdo_query($qry, $prms, "Cannot set conference parameters: ");

// Insert PC members to the database

pdo_query("DELETE FROM {$SQLprefix}committee"); // start from scratch

if (!empty($_GET['password']))      // set pwd for 1st chair
  $pwd = $_GET['password'];
else {
  $pwd = sha1(uniqid(rand()).mt_rand());        // returns hex string
  $pwd = alphanum_encode(substr($pwd, 0, 15));  // "compress" a bit
}
$i=1;
foreach ($committee as $m) {
  $nm = $m['name'];
  $eml = $m['email'];
  if ($m['flags']) { // PC chair(s): first one gets a password
    if (!empty($pwd)) {
      $chrEml = $eml;
      $chrPwd = $pwd;
      $pwd = sha1(CONF_SALT . $eml . $pwd);
    }
    if (empty($nm))
      $nm = $shortName.$confYear." Chair".(($i>1)? $i: '');

    $qry= "INSERT INTO {$SQLprefix}committee (revId,revPwd,name,email,canDiscuss,flags)"
      . " VALUES(?,?,?,?,?,?)";
    pdo_query($qry, array($i,$pwd,$nm,$eml,1,FLAG_IS_CHAIR));
    $pwd = ''; // other chairs (if any) will not get their passwords yet
  } 
  else {  // regular PC member
    $qry= "INSERT INTO {$SQLprefix}committee (revId,revPwd,name,email) VALUES(?,?,?,?)";
    pdo_query($qry, array($i,'',$nm,$eml));
  }
  $i++;
}

// insert a dummy submission, so numbering will start at 101
$qry = "INSERT INTO {$SQLprefix}submissions (subId,title,authors,contact,abstract,status,subPwd)"
  . " VALUES(100,'Dummy','Dummy','Dummy','Dummy','Withdrawn','Dummy')";
pdo_query($qry);
pdo_query("DELETE FROM {$SQLprefix}submissions WHERE subId=100");

// All went well, send email to 1st chair
$hdr = "From: $chrEml";
$sbjct = "Submission and review site for $shortName $confYear is operational";
  
$prot = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
$baseURL = $prot.'://'.BASE_URL;
$passwd = $chrPwd;
$msg =<<<EndMark
Attention: this email contains your new password.

The submission and review site for $shortName $confYear is customized
and ready for use. The start page for submitting papers is:

{$baseURL}submit/

The administration page is:

{$baseURL}chair/

You can login to the administration page using your email address
as username {$chrEml} and with password {$chrPwd}
EndMark;

$sndTo = "$chrEml, ". ADMIN_EMAIL;
if (!mail($sndTo, $sbjct, $msg, $hdr)) {
  $err = "Cannot send email to $chrEml (pwd=$chrPwd)";
  error_log(date('Y.m.d-H:i:s ')."$err. {$php_errormsg}\n", 3, LOG_FILE);
}
$urlParams = '?username='.$chrEml.'&password='.$chrPwd;

// if in testing mode: insert dummy submissions/reviewers
if (file_exists('testingOnly.php')) {
  header("Location: testingOnly.php{$urlParams}");
}
else header("Location: receiptCustomize.php{$urlParams}");
