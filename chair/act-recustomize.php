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
$chrPwd = NULL;
$cnnct = db_connect();

$longName    = CONF_NAME;
$shortName   = CONF_SHORT;
$confYear    = CONF_YEAR;
$confURL     = CONF_HOME;
$chairEml    = CHAIR_EMAIL;
$adminEml    = ADMIN_EMAIL;
$subDeadline = SUBMIT_DEADLINE;
$cmrDeadline = CAMERA_DEADLINE;
$maxGrade    = MAX_GRADE;
$maxConfidence= MAX_CONFIDENCE;
$anonymous   = ANONYMOUS;
$revPrefs    = REVPREFS;
$affiliations= USE_AFFILIATIONS;
$emlCrlf     = addcslashes(EML_CRLF, "\\\n\r");
$emlSender   = EML_SENDER;
$period      = PERIOD;
$cameraInstructions = CAMERA_INSTRUCTIONS;
$accLtr      = ACCEPT_LTR;
$rejLtr      = REJECT_LTR;

// Read all the fields, stripping spurious white-spaces

$x  = isset($_POST['longName'])  ? trim($_POST['longName'])  : NULL;
if (!empty($x) && $x!=$longName) { $changeParams = true; $longName = $x; }

$x  = isset($_POST['shortName']) ? trim($_POST['shortName']) : NULL;
if (!empty($x) && $x!=$shortName) { $changeParams = true; $shortName = $x; }

$x  = isset($_POST['confYear'])  ? trim($_POST['confYear'])  : NULL;
if (!empty($x) && $x!=$confYear) { $changeParams = true; $confYear = $x; }

$x  = isset($_POST['confURL'])   ? trim($_POST['confURL'])   : NULL;
if (!empty($x) && $x!=$confURL) { $changeParams = true; $confURL = $x; }

$x  = isset($_POST['adminEml'])  ? trim($_POST['adminEml'])  : NULL;
if (!empty($x) && strtolower($x)!=strtolower($adminEml)) {
  $changeParams = true; $adminEml = $x;
}

$x = isset($_POST['emlCrlf'])  ? trim($_POST['emlCrlf']) : NULL;
if ($x != "\\n") $x = "\\r\\n";
if (isset($_POST['emlCrlf']) || !defined(EML_CRLF)) $emlCrlf = $x;

$x  = isset($_POST['emlSender']) ? trim($_POST['emlSender']) : NULL;
if (isset($_POST['emlSender']) || !defined(EML_SENDER))
  $emlSender = $x;

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
}

if (isset($_POST['revPrefsFlag'])) {
  $revPrefs = isset($_POST['revPrefs']);
  if (REVPREFS!=$revPrefs) $changeParams = true;
}

// The review parameters cannot be changed after activation of the review site
if (isset($_POST['reviewPrms']) && !defined('REVIEW_PERIOD')) {

  $x = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 0;
  if ($x>=2 && $x<=9 && $x != $maxGrade) {
    $maxGrade = $x; $changeParams = true;
  }

  if (isset($_POST['setCriteria'])) { // Create an array of criteria
    $n_old = isset($criteria) ? count($criteria) : 0;

    $x = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;
    if (is_array($x) && count($x)>0) {
      $changeParams = true;
      $criteria = array();
      foreach ($x as $c) {
	if ($cr = parse_criterion($c)){ $criteria[]= array($cr[0], $cr[1]);}
      }
      $n_criteria = count($criteria);
    } else {
      $criteria = NULL;
      $n_criteria = 0;
      if ($n_old > 0) $changeParams = true;
    }

    // add or remove criteria from the database - if needed
    if ($n_criteria>$n_old) for ($i=$n_old; $i<$n_criteria; $i++) {
      $alterRprtTbl = "ALTER TABLE reports ADD grade_{$i} TINYINT(2) AFTER grade";
      if ($i>0) $alterRprtTbl .= "_" . ($i-1);
      db_query($alterRprtTbl, $cnnct, "Cannot ALTER reports table: ");
    }
    if ($n_criteria<$n_old) for ($i=$n_criteria; $i<$n_old; $i++) {
      $alterRprtTbl = "ALTER TABLE reports DROP COLUMN grade_{$i}";
      db_query($alterRprtTbl, $cnnct, "Cannot ALTER reports table: ");
    }
  } // if (isset($_POST['setCriteria'])) 
}   // if (isset($_POST['reviewPrms']) && !defined('REVIEW_PERIOD'))

// This is where we close the submission site
if (!defined('REVIEW_PERIOD') && isset($_POST['closeSubmissions'])) {
  define('REVIEW_PERIOD', true);// Review site will be active after this call
  $period = PERIOD_REVIEW;
  $changeParams = true;
}

// Manage access to the review cite
if (isset($_POST['reviewSite'])) {
  $mmbrs2remove = isset($_POST['mmbrs2remove'])? $_POST['mmbrs2remove']: NULL;
  if (is_array($mmbrs2remove)) foreach ($mmbrs2remove as $revId => $x) {
    if ($revId==CHAIR_ID) continue;    // Cannot remove the chair
    $qry = "DELETE from committee WHERE revId='"
      . my_addslashes($revId, $cnnct) ."'";
    db_query($qry, $cnnct, "Cannot remove member with revId='{$revId}': ");
  }

  // Compare PC member details from the databse with the details from
  // the _POST array, and update the database whenever these differ
  $members = $_POST['members'];
  if (is_array($members)) { 

    $qry = "SELECT revId, name, email FROM committee ORDER BY revId";
    $res = db_query($qry, $cnnct);
    while ($row = mysql_fetch_row($res)) {
      $revId = (int) trim($row[0]);
      $m = $members[$revId]; // $m = array(name, email, reset-flag)
      if (isset($m)) {
	$nm = isset($m[0]) ? trim($m[0]) : NULL;
	$eml = isset($m[1]) ? strtolower(trim($m[1])) : NULL;
	if ($nm!=$row[1] || $eml!=strtolower($row[2]) || isset($m[2])) {
	  update_committee_member($cnnct, $revId, $nm, $eml, isset($m[2]));
	}
      }
    }
  }
  $mmbrs2add = isset($_POST['mmbrs2add']) ?
                                  explode(';', $_POST['mmbrs2add']) : NULL;
  if (is_array($mmbrs2add)) foreach ($mmbrs2add as $m) {
    if ($m = parse_email($m))
      update_committee_member($cnnct, NULL, $m[0], $m[1]);
  }
} // if (isset($_POST['reviewSite']))


// Close review and activate final-version submission site
if (isset($_POST['finalVersionInstructions'])) {
  if (!defined('CAMERA_PERIOD')) define('CAMERA_PERIOD', true);
  $period = PERIOD_CAMERA;
  $cameraInstructions = trim($_POST['finalVersionInstructions']);
  $cameraInstructions = str_replace("\r\n", "\n", $cameraInstructions);

  $confFormats = array(
       'tar'    => array('tar', 'application/x-tar'),
       'tar.gz' => array('Compressed tar', 'application/x-tar-gz'),
       'tgz'    => array('Compressed tar', 'application/x-compressed-tar'),
       'zip'    => array('zip', 'application/x-zip')
  );
  $changeParams = true;

  send_camera_instructions($cnnct, $cameraInstructions);
}

if (isset($_POST['shutdown']) && $_POST['shutdown']=="yes") {
  define('SHUTDOWN', true);
  $period = PERIOD_FINAL;
  $changeParams = true;
}

if ($changeParams) {
  // If we got here after undo's, delete paramater-sets that
  // have larger version numbers than the new one. 
  $qry = "DELETE FROM parameters WHERE version>".PARAMS_VERSION;
  mysql_query($qry, $cnnct); //  no need to abort of failure

  $qry = "INSERT INTO parameters SET\n  version=".(PARAMS_VERSION+1).",\n"
    . "  isCurrent=1,\n"
    . "  longName='"  .my_addslashes($longName, $cnnct)."',\n"
    . "  shortName='" .my_addslashes($shortName, $cnnct)."',\n"
    . "  confYear="   .intval($confYear).",\n";

  if (empty($confURL)) $confURL = '.';
  $qry .= "  confURL='"   .my_addslashes($confURL, $cnnct)."',\n"
    . "  subDeadline=".intval($subDeadline).",\n"
    . "  cmrDeadline=".intval($cmrDeadline).",\n"
    . "  maxGrade="   .intval($maxGrade).",\n"
    . "  maxConfidence=3,\n";

  $flags = 0;
  if ($revPrefs)       $flags |= FLAG_PCPREFS;
  if ($anonymous)      $flags |= FLAG_ANON_SUBS;
  if ($affiliations)   $flags |= FLAG_AFFILIATIONS;
  if ($emlCrlf=="\\n") $flags |= FLAG_EML_HDR_CRLF;
  if (defined('HTTPS_ON') || isset($_SERVER['HTTPS'])) $flags |= FLAG_SSL;
  $qry .= "  flags=$flags,\n"
    . "  emlSender='".my_addslashes($emlSender, $cnnct)."',\n"
    . "  baseURL='"    .my_addslashes(BASE_URL, $cnnct)."',\n"
    . "  period=$period,\n";

  if (is_array($confFormats) && count($confFormats)>0) {
    $fmtString = $sc = '';
    foreach($confFormats as $ext => $f) {
      $fmtString .= $sc.$f[0]."($ext,".$f[1].")";
      $sc = ';';
    }
    $fmtString = "'".my_addslashes($fmtString, $cnnct)."'";
  }
  else $fmtString = 'NULL';
  $qry .= "  formats=$fmtString,\n";

  if (is_array($categories) && count($categories)>0) {
    $catString = $sc = '';
    foreach ($categories as $c) {
      $catString .= $sc.$c;
      $sc = ';';
    }
    $catString = "'".my_addslashes($catString, $cnnct)."'";
  }
  else $catString = 'NULL';
  $qry .= "  categories=$catString,\n";

  if (is_array($criteria) && count($criteria)>0) {
    $crtriaString = $sc = '';
    foreach($criteria as $cr) {
      $crtriaString .= $sc.$cr[0]."(".$cr[1].")";
      $sc = ';';
    }
    $crtriaString = "'".my_addslashes($crtriaString, $cnnct)."'";
  }
  else $crtriaString = 'NULL';
  $qry .= "  extraCriteria=$crtriaString,\n";

  if (empty($cameraInstructions)) $cameraInstructions='NULL';
  else $cameraInstructions="'".my_addslashes($cameraInstructions,$cnnct)."'";

  $accLtr = empty($accLtr) ? 'NULL' : ("'".my_addslashes($accLtr,$cnnct)."'");
  $rejLtr = empty($rejLtr) ? 'NULL' : ("'".my_addslashes($rejLtr,$cnnct)."'");
  $qry .= "  cmrInstrct=$cameraInstructions,\n"
    . "  acceptLtr=$accLtr,\n"
    . "  rejectLtr=$rejLtr";
  db_query($qry, $cnnct, "Cannot INSERT conference parameters: ");

  // and also reset the current (soon to be previous) parameter-set
  $qry = "UPDATE parameters SET  isCurrent=0 WHERE version=".PARAMS_VERSION;
  db_query($qry, $cnnct, "Cannot reset parameters: ");
} /* if ($changeParams) */

// All went well, go to confirmation page (or directly to administration page)
if (!defined('REVIEW_PERIOD')) {
  $param = isset($chrPwd) ? "?pwd=$chrPwd" : "";
  header("Location: receipt-customize.php{$param}");
}
else
  header("Location: index.php");

exit();

function update_committee_member($cnnct, $revId, $name, $email, $reset=false)
{
  global $chrPwd;
  global $chairEml;
  global $changeParams;

  if (!empty($name)) $nm = my_addslashes(trim($name), $cnnct);
  if (!empty($email)) {
    $email = strtolower(trim($email));
    $eml = my_addslashes($email, $cnnct);
  }

  if (empty($revId)) { // insert a new member
    if (empty($email)) return;  // member cannot have an empty email

    if (empty($name)) { // set to username from email address if missing
      $name = substr($email, 0, strpos($email, '@'));
      if (empty($name)) return; // email address without '@' ??
      $nm = my_addslashes(trim($name), $cnnct);
    }

    $pwd = md5(uniqid(rand()).mt_rand());          // returns hex string
    $pwd = alphanum_encode(substr($pwd, 0, 15));   // "compress" a bit
    $pw = md5(CONF_SALT. $email . $pwd);

    $qry = "INSERT INTO committee SET"
         . " name = '{$nm}', email = '{$eml}', revPwd = '{$pw}'";
    db_query($qry, $cnnct, "Cannot add PC member $name <$email>: ");
    email_password($email, $pwd);
    return;
  }

  // Modify an existing member: first get current details
  $qry = "SELECT revPwd, name, email FROM committee WHERE revId={$revId}";
  $res = db_query($qry, $cnnct, "Cannot find PC member with revId='$revId': ");
  if ($row=mysql_fetch_row($res)) {  // Found PC member
    $updates = $comma = '';

    if (empty($row[0])) $reset=true;                   // Set initial password
    if (isset($email) && $email!=$row[2]) $reset=true; // Change email address

    if ($reset) { // reset pwd
      $pwd = md5(uniqid(rand()).mt_rand());        // returns hex string
      $pwd = alphanum_encode(substr($pwd, 0, 15)); // "compress" a bit
      $pw = md5(CONF_SALT. $email . $pwd);
      $updates .= "revPwd='$pw'";
      $comma = ", ";

      if ($revId == CHAIR_ID) $chrPwd = $pwd;
    }

    if (!empty($name) && $name!=$row[1]) {
      $updates .= $comma . "name='$nm'";
      $comma = ", ";
    }

    if (!empty($email) && $email!=$row[2]) {
      $updates .= $comma . "email='$eml'";
    }

    if (!empty($email) && $revId==CHAIR_ID) 
      $chairEml = $email;

    if (!empty($updates)) {
      $qry = "UPDATE committee SET $updates WHERE revId='{$revId}'";
      db_query($qry, $cnnct, "Cannot update PC member $name <$email>: ");
      if ($reset) email_password($email, $pwd, ($revId == CHAIR_ID));
    } 
  }
}

function email_password($emailTo, $pwd, $isChair=false)
{
  global $shortName;
  global $confYear;
  global $chairEml;

  $prot = (defined('HTTPS_ON') || isset($_SERVER['HTTPS']))? 'https' : 'http';
  $baseURL = $prot.'://'.BASE_URL;

  if ($isChair) $cc = ADMIN_EMAIL;
  else          $cc = $chairEml;

  $sbjct = "New/reset password for submission and review site for $shortName $confYear";

  $msg =<<<EndMark
You now have access to the submission and review site for $shortName $confYear.
The review start page is

  {$baseURL}review/

EndMark;

  if ($isChair)
    $msg .= "\nThe start page for administration is\n\n  {$baseURL}chair/\n\n";

  $msg .= <<<EndMark

You can login to the review site using your email address as username,
and with password $pwd

EndMark;

  $success = my_send_mail($emailTo, $sbjct, $msg, $cc, "password $pwd to $emailTo");
}

function send_camera_instructions($cnnct, $text)
{
  $sbjct = "Final-version instructions for ".CONF_SHORT.' '.CONF_YEAR;

  $qry = "SELECT subId, title, authors, contact FROM submissions WHERE status='Accept'";
  $res = db_query($qry, $cnnct);
  $count=0;
  while ($row = mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $contact = $row[3];

    my_send_mail($contact, $sbjct, $text, CHAIR_EMAIL,
	       "camera-ready instructions for subID $subId, contact $contact");

    $count++;
    if (($count % 25)==0) { // rate-limiting, avoids cutoff
      sleep(1);
    }
  }
}
?>
