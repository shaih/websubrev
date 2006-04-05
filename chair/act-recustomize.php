<?php
/* Web Submission and Review Software, version 0.51
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

$changeConstFile = false;
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
$emlExtraPrm = EML_EXTRA_PRM;

// Read all the fields, stripping spurious white-spaces

$x  = isset($_POST['longName'])  ? trim($_POST['longName'])  : NULL;
if (!empty($x) && $x!=$confName) { $changeConstFile = true; $longName = $x; }

$x  = isset($_POST['shortName']) ? trim($_POST['shortName']) : NULL;
if (!empty($x) && $x!=$shortName) { $changeConstFile = true; $shortName = $x; }

$x  = isset($_POST['confYear'])  ? trim($_POST['confYear'])  : NULL;
if (!empty($x) && $x!=$confYear) { $changeConstFile = true; $confYear = $x; }

$x  = isset($_POST['confURL'])   ? trim($_POST['confURL'])   : NULL;
if (!empty($x) && $x!=$confURL) { $changeConstFile = true; $confURL = $x; }

$x  = isset($_POST['adminEml'])  ? trim($_POST['adminEml'])  : NULL;
if (!empty($x) && strtolower($x)!=strtolower($adminEml)) {
  $changeConstFile = true; $adminEml = $x;
}

$x = trim($_POST['emlCrlf']);
if ($x != "\\n") $x = "\\r\\n";
if (isset($_POST['emlCrlf']) || !defined(EML_CRLF)) $emlCrlf = $x;

$x  = trim($_POST['emlExtraPrm']);
if (isset($_POST['emlExtraPrm']) || !defined(EML_EXTRA_PRM))
  $emlExtraPrm = $x;

$x = isset($_POST['subDeadline']) ?  trim($_POST['subDeadline']) : NULL;

if (!empty($x) && $x!=$subDeadline) {$changeConstFile=true; $subDeadline=$x;}

$x = isset($_POST['cameraDeadline']) ? trim($_POST['cameraDeadline']) : NULL;
if (!empty($x) && $x!=$cmrDeadline) {$changeConstFile=true; $cmrDeadline=$x;}

$x = isset($_POST['categories']) ? explode(';', $_POST['categories']) : NULL;
if (is_array($x)) {
  $categories = array();
  foreach ($x as $cat)
    $categories[] = trim($cat);
}
else $categories = NULL;


if (isset($_POST['formats'])) {

  // This contrived logic is because it is more intuitive
  // for the user to "check to keep" than to "check to remove"
  if (is_array($confFormats) && count($confFormats)>0) {
    foreach($confFormats as $ext => $x) { // go over all the formats
      if (!isset($_POST["keepFormats_{$ext}"])) { // should we keep this one?
	unset($confFormats[$ext]);                //   nope: remove it
	$changeConstFile = true;
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
    $changeConstFile = true;
}

if (isset($_POST['revPrefsFlag'])) {
  $revPrefs = isset($_POST['revPrefs']);
  if (REVPREFS!=$revPrefs) $changeConstFile = true;
}

// The review parameters cannot be changed after activation of the review site
if (isset($_POST['reviewPrms']) && !defined('REVIEW_PERIOD')) {

  $x = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 0;
  if ($x>=2 && $x<=9 && $x != $maxGrade) {
    $maxGrade = $x; $changeConstFile = true;
  }

  if (isset($_POST['setCriteria'])) { // Create an array of criteria
    $n_old = isset($criteria) ? count($criteria) : 0;

    $x = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;
    if (is_array($x) && count($x)>0) {
      $changeConstFile = true;
      $criteria = array();
      foreach ($x as $c) {
	if ($cr = parse_criterion($c)){ $criteria[]= array($cr[0], $cr[1]);}
      }
      $n_criteria = count($criteria);
    } else {
      $criteria = NULL;
      $n_criteria = 0;
      if ($n_old > 0) $changeConstFile = true;
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
  $changeConstFile = true;
}

// Manage access to the review cite
if (isset($_POST['reviewSite'])) {
  $mmbrs2remove = $_POST['mmbrs2remove'];
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
      $m = $members[$revId];
      if (isset($m)) {
	$nm = isset($m[0]) ? trim($m[0]) : NULL;
	$eml = isset($m[1]) ? strtolower(trim($m[1])) : NULL;
	if ($nm!=$row[1]
	    || $eml!=strtolower($row[2]) || isset($m[2])) {
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
  $cameraInstructions = trim($_POST['finalVersionInstructions']);
  $cameraInstructions = str_replace("\r\n", "\n", $cameraInstructions);

  $confFormats = array(
       'tar'    => array('tar', 'application/x-tar'),
       'tar.gz' => array('Compressed tar', 'application/x-tar-gz'),
       'tgz'    => array('Compressed tar', 'application/x-compressed-tar'),
       'zip'    => array('zip', 'application/x-zip')
  );
  $changeConstFile = true;

  send_camera_instructions($cnnct, $cameraInstructions);
}

if ($_POST['shutdown']=="yes") {
  define('SHUTDOWN', true);
  $changeConstFile = true;
}

if ($changeConstFile) {
  $constString = "<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
\n"
    . "define('BASE_URL', '".BASE_URL."');\n"
    . "define('CONST_FILE', '".CONST_FILE."');\n"
    . "define('CONF_NAME', '$longName');\n"
    . "define('CONF_SHORT', '$shortName');\n"
    . "define('CONF_YEAR', '$confYear');\n"
    . "define('CONF_HOME', '$confURL');\n"
    . "define('CHAIR_ID', ".CHAIR_ID.");\n"
    . "define('CHAIR_EMAIL', '$chairEml');\n"
    . "define('ADMIN_EMAIL', '$adminEml');\n"
    . "define('SUBMIT_DEADLINE', '$subDeadline');\n"
    . "define('CAMERA_DEADLINE', '$cmrDeadline');\n"
    . "define('CONF_SALT', '".CONF_SALT."');\n"
    . "define('SUBMIT_DIR', '".SUBMIT_DIR."');\n"
    . "define('LOG_FILE', '".LOG_FILE."');\n"
    . "define('MYSQL_HOST', '".MYSQL_HOST."');\n"
    . "define('MYSQL_DB', '".MYSQL_DB."');\n"
    . "define('MYSQL_USR', '".MYSQL_USR."');\n"
    . "define('MYSQL_PWD', '".MYSQL_PWD."');\n"
    . "define('EML_CRLF', \"$emlCrlf\");\n"
    . "define('EML_EXTRA_PRM', '$emlExtraPrm');\n";

  if (defined('HTTPS_ON') || isset($_SERVER['HTTPS']))
    $constString .= "define('HTTPS_ON', true);\n";
 
  if ($affiliations) $constString .= "define('USE_AFFILIATIONS', true);\n";
  else               $constString .= "define('USE_AFFILIATIONS', false);\n";

  if ($anonymous) $constString .= "define('ANONYMOUS', true);\n";
  else            $constString .= "define('ANONYMOUS', false);\n";

  if ($revPrefs) $constString .= "define('REVPREFS', true);\n";
  else           $constString .= "define('REVPREFS', false);\n";

  $constString .= "define('MAX_GRADE', $maxGrade);\n";
  $constString .= "define('MAX_CONFIDENCE', $maxConfidence);\n";

  if (defined('SHUTDOWN')) {
    $constString .= "define('REVIEW_PERIOD', false);\n";
    $constString .= "define('CAMERA_PERIOD', false);\n";
    $constString .= "define('SHUTDOWN', true);\n";
  } else if (defined('CAMERA_PERIOD')) {
    $constString .= "define('REVIEW_PERIOD', false);\n";
    $constString .= "define('CAMERA_PERIOD', true);\n";
    $constString .= "\$cameraInstructions='"
      . addslashes($cameraInstructions) . "';\n";
  } else if (defined('REVIEW_PERIOD'))
    $constString .= "define('REVIEW_PERIOD', true);\n";

  if (is_array($confFormats) && count($confFormats)>0) {
    $comma = '';
    $constString .= "\$confFormats = array(";
    foreach($confFormats as $ext => $f) {
      $constString .= "{$comma}\n  '$ext' => array('$f[0]', '$f[1]')";
      $comma = ',';
    }
    $constString .= "\n);\n";
  }
  else $constString .= "\$confFormats = NULL;\n";

  if (is_array($categories) && count($categories)>0) {
    $comma = '';
    $constString .= "\$categories = array(";
    foreach ($categories as $c) {
      $constString .= "{$comma}\n  '$c'";
      $comma = ',';
    }
    $constString .= "\n);\n";
  }
  else $constString .= "\$categories = NULL;\n";

  if (is_array($criteria) && count($criteria)>0) {
    $comma = '';
    $constString .= "\$criteria = array(";
    foreach($criteria as $cr) {
      $constString .= "{$comma}\n  array('$cr[0]', $cr[1])";
      $comma = ',';
    }
    $constString .= "\n);\n";
  }
  else $constString .= "\$criteria = NULL;\n";

  $constString .= '?>'; 
  /* Note: "?>" must be the last thing in the file (not even "\n" after it) */

  $tFile = CONST_FILE . '.' . date('YmdHis');
  if (file_exists($tFile)) unlink($tFile); // just in case

  // Open for write
  if (!($fd = fopen($tFile, 'w'))) {
    exit("<h1>Cannot create the customization file at $tFile</h1>\n");
  }

  if (!fwrite($fd, $constString)) {
    exit ("<h1>Cannot write into customization file $tFile</h1>\n");
  }

  // Close the temporary file and rename it to it's final name
  fclose($fd);

  // Move the old file to backup
  $bkFile = CONST_FILE . '.bak.php';
  if (file_exists($bkFile)) unlink($bkFile);
  rename(CONST_FILE, $bkFile);

  if (!rename($tFile, CONST_FILE)) {
    if (!file_exists(CONST_FILE)) // recover from backup
      rename($bkFile, CONST_FILE);
    exit ("<h1>Cannot rename customization file $tFile</h1>\n");
  }
  chmod(CONST_FILE, 0664); // makes debugging a bit easier
}

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
  global $changeConstFile;

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

    if (empty($row[0])) $reset=true;               // Set initial password
    if ($reset || (isset($email) && $email!=$row[2])) {// reset pwd
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
      if ($revId == CHAIR_ID) { $changeConstFile = true; }
    }

    // Make sure that the chair's email is the same
    // in the database and the constant file
    if (!empty($email) && $revId==CHAIR_ID) 
      $chairEml = $email;

    if (!empty($updates)) {
      $qry = "UPDATE committee SET $updates WHERE revId='{$revId}'";
      db_query($qry, $cnnct, "Cannot update PC member $name <$email>: ");
      email_password($email, $pwd, ($revId == CHAIR_ID));
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

  $emlCrlf = (EML_CRLF == "\n") ? "\n" : "\r\n";
  $hdr = "From: $shortName $confYear <$chairEml>".$emlCrlf;
  if ($isChair) $hdr .= 'Cc: '.ADMIN_EMAIL.$emlCrlf;
  else $hdr .= 'Cc: ' . $chairEml . $emlCrlf;
  $hdr .= 'X-Mailer: PHP/' . phpversion();
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

  if (ini_get('safe_mode') || !defined('EML_EXTRA_PRM'))
    $success = mail($emailTo, $sbjct, $msg, $hdr);
  else
    $success = mail($emailTo, $sbjct, $msg, $hdr, EML_EXTRA_PRM);

  if (!$success) error_log(date('Y.m.d-H:i:s ')."Cannot send password $pwd to {$emailTo}. {$php_errormsg}\n", 3, './log/'.LOG_FILE);
}

function send_camera_instructions($cnnct, $text)
{
  $cName = CONF_SHORT.' '.CONF_YEAR;

  $emlCrlf = (EML_CRLF == "\n") ? "\n" : "\r\n";
  $hdr = "From: {$cName} Chair <".CHAIR_EMAIL.">".$emlCrlf;
  $hdr .= "Cc: " .CHAIR_EMAIL .$emlCrlf;
  $hdr .= "X-Mailer: PHP/" . phpversion();

  $sbjct = "Final-version instructions for $cName";

  $qry = "SELECT subId, title, authors, contact FROM submissions
  WHERE status='Accept'";
  $res = db_query($qry, $cnnct);
  while ($row = mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $contact = $row[3];

  if (ini_get('safe_mode') || !defined('EML_EXTRA_PRM'))
    $success = mail($contact, $sbjct, $text, $hdr);
  else
    $success = mail($contact, $sbjct, $text, $hdr, EML_EXTRA_PRM);

  if (!$success) error_log(date('Y.m.d-H:i:s ')."Cannot send instructions for submission {$subId} to {$contact}. {$php_errormsg}\n", 3, './log/'.LOG_FILE);
  }
}
?>
