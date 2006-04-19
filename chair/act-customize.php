<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 
chdir('..'); // This script is placed in a sub-directory
$constFile = './includes/confConstants.php';
if (file_exists($constFile)) { // Already customized
  exit("<h1>This installation is already cusomized</h1>");
}

// Some things in confUtils need the BASE_URL constant
$webServer = trim($_POST['webServer']);
if (empty($webServer)) $webServer = $_SERVER['HTTP_HOST'];
$baseURL = $webServer . $_SERVER['PHP_SELF'];             // this file
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/'));   // the directory
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/')+1); // parent directory
define('BASE_URL', $baseURL);

require './includes/confUtils.php'; 

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) 
  $_POST  = array_map('stripslashes_deep', $_POST);

// Read all the fields, stripping spurious white-spaces

$longName    = trim($_POST['longName']) ;
$shortName   = trim($_POST['shortName']);
$confYear    = trim($_POST['confYear']) ;
$confURL     = trim($_POST['confURL'])  ;

$subDeadline = trim($_POST['subDeadline']);
$cameraDeadline= trim($_POST['cameraDeadline']);
$adminEmail  = trim($_POST['adminEmail']);
$chairName   = trim($_POST['chairName']);
$chairEmail  = trim($_POST['chairEmail']);

if (trim($_POST['localMySQL'])=="yes") {
  $sqlHost = 'localhost';
} else {
  $sqlHost   = trim($_POST['MySQLhost']); // MySQL server as seen by web server
  if (empty($sqlHost)) $sqlHost = 'localhost';
}
$sqlRoot     = trim($_POST['rootNm']);
$sqlRootPwd  = trim($_POST['rootPwd']);
$sqlDB       = trim($_POST['confDB']);
$sqlUsr      = trim($_POST['user']);
$sqlPwd      = trim($_POST['pwd']) ;
$emlCrlf   = trim($_POST['emlCrlf']);
if ($emlCrlf != "\\n") $emlCrlf = "\\r\\n";
$emlExtraPrm = trim($_POST['emlExtraPrm']);
if (empty($emlExtraPrm)) $emlExtraPrm = '-f '. $chairEmail;

$nCmmtee = isset($_POST['nCmmtee']) ? (int) $_POST['nCmmtee'] : 0;
if ($nCmmtee > 500) { die("Cannot handle committees larger than 500"); }
for ($i=0; $i<$nCmmtee; $i++) {
  $mmbr = "member_{$i}_";
  $nm  = trim($_POST["{$mmbr}name"]);
  $eml = trim($_POST["{$mmbr}email"]);
  $committee[$i] = array($nm, $eml);
}

$nCats = isset($_POST['nCats']) ? (int) $_POST['nCats'] : 0;
if ($nCats > 100) { die("Cannot handle more than 100 cetegories"); }
if ($nCats > 0) {
  $categories = array();
  for ($i=0; $i<$nCats; $i++) {
    $categories[] = trim($_POST["category_{$i}"]);
  }
}

$nFrmts = isset($_POST['nFrmts']) ? (int) $_POST['nFrmts'] : 0;
if ($nFrmts > 50) { die("Cannot handle more than 50 supported formats"); }
if ($nFrmts > 0) {
  $fileFormats = array();
  for ($i=0; $i<$nFrmts; $i++) {
    $frmt = "frmt_{$i}_";
    $nm   = trim($_POST["{$frmt}desc"]);
    $ext  = trim($_POST["{$frmt}ext"]);
    $mime = trim($_POST["{$frmt}mime"]);
    $fileFormats[$ext] = array($nm, $mime);
  }
}

$maxGrade  = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 6;
if (($maxGrade < 2) || ($maxGrade > 9)) { $maxGrade =6; }

$nCrits = isset($_POST['nCrits']) ? (int) $_POST['nCrits'] : 0;
if ($nCrits > 20) { die("Cannot handle more than 20 evaluation criteria"); }
if ($nCrits > 0) {
  $criteria = array();
  for ($i=0; $i<$nCrits; $i++) {
    $cr = "criterion_{$i}_";
    $nm = trim($_POST["{$cr}name"]);
    $mx = isset($_POST["{$cr}max"]) ? (int) trim($_POST["{$cr}max"]) : 3;
    if (($mx < 2) || ($mx>9)) $mx = 3;
    if (!empty($nm)) {
      $criteria[] = array($nm, $mx);
    }
  }
}

// Check that the required fileds are specified

if (empty($longName) || empty($shortName) || empty($confYear) 
    || empty($chairEmail) || empty($adminEmail)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify the conference short and long names and year, administrator email and program chair email\n");
}

if (!preg_match('/^[0-9]{4}$/', $confYear)) {
  exit("<h1>Wrong format for the conference year: $confYear</h1>
       Year must consists of exatly two digits.\n");
}

if ((empty($sqlRoot) || empty($sqlRootPwd)) 
    && (empty($sqlDB) || empty($sqlUsr) || empty($sqlPwd))) {
  exit("<h1>Cannot create/access MySQL database</h1>
       To automatically generate MySQL database, you must specify the
       MySQL root username and password.<br />
       Otherwise, you must manually create the database and specify the
       database name, and also specify MySQL usename and password of a
       user that has access to that database.\n");
}

/* Finally, we are ready to start customizing the installation */

// We generate some randomness for (a) salting the password hashes, and
// (b) giving "random" names for things that are not scripts so that it
// is harder to bypass the script authentication mechanism and instead
// get them by directly specifying their name in the URL
$salt = alphanum_encode(md5(uniqid(rand()).mt_rand()));
define('SUBMIT_DIR', substr($salt, 0, 10));
define('LOG_FILE', substr($salt, -10, 10).'.log');

// Create the error log file
if (!copy('log/errors.log', 'log/'.LOG_FILE)) { 
  exit ("<h1>Cannot create error log file log/".LOG_FILE."</h1>\n");
}
error_log(date('Y.m.d-H:i:s ')."Log file created\n", 3, './log/'.LOG_FILE);

// If MySQL database and username are not specified,
// generate the table and create a new user
if (empty($sqlUsr) || empty($sqlPwd) || empty($sqlDB)) {

  // Test that we can actually connect using the root username/pwd
  $cnnct = db_connect($sqlHost, $sqlRoot, $sqlRootPwd, NULL);

  // Create the database (if not exist)
  if (empty($sqlDB) || !preg_match('/^[a-z][0-9a-z_.\-]*$/i', $sqlDB))
    $sqlDB = substr(makeName($shortName), 0, 16) . $confYear;
  $sqlDB = makeName($sqlDB); // make sure this is a valid name

  $qry = 'DROP DATABASE IF EXISTS ' .  $sqlDB;
  db_query($qry, $cnnct, "Cannot create a new database $sqlDB: ");
  $qry = 'CREATE DATABASE IF NOT EXISTS ' . $sqlDB;
  db_query($qry, $cnnct, "Cannot create a new database $sqlDB: ");

  // Create new user if not exist
  if (empty($sqlUsr) || !preg_match('/^[a-z][0-9a-z_.\-]*$/i', $sqlDB)) {
    $sqlUsr = $sqlDB;
    $sqlPwd = md5(uniqid(rand()). mt_rand(). $sqlUsr); // returns hex string
    $sqlPwd = alphanum_encode(substr($sqlPwd, 0, 12)); // "compress" a bit
  } 
  $sqlUsr = my_addslashes($sqlUsr, $cnnct);
  $sqlPwd = my_addslashes($sqlPwd, $cnnct);

  if ($sqlHost=='localhost') {
    $qry = "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP
          ON {$sqlDB}.* TO '$sqlUsr'@'localhost'
          IDENTIFIED BY '{$sqlPwd}'";
  } else {
    $qry = "GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP
          ON {$sqlDB}.* TO '$sqlUsr'
          IDENTIFIED BY '{$sqlPwd}'";
  }
  db_query($qry, $cnnct, "Cannot GRANT privileges: ");

  @mysql_close($cnnct);
}
 
// The database and user should already exist by now
$cnnct = db_connect($sqlHost, $sqlUsr, $sqlPwd, $sqlDB);

// Create tables
require 'includes/database.php';
create_tabels($cnnct, $nCrits);

// The following stupidity inserts a dummy withdrawn submission with
// a "random id", so the real submissions will be numbered starting
// from a random value. What idiot came up with that idea? 
$rid = rand(100, 119);
$qry = "INSERT INTO submissions SET subId='{$rid}', title = 'Dummy',
        authors = 'Dummy', contact = 'Dummy', abstract = 'Dummy',
        status = 'Withdrawn', subPwd = 'Dummy'";
@mysql_query($qry, $cnnct);
$qry = "DELETE FROM submissions WHERE subId='{$rid}'";
@mysql_query($qry, $cnnct);

// Insert the PC members into the committee table, chair first. (The chair
// is identified by virtue of having id=1 in the database.) Also generates
// password for the chair and send it by email. The value that is written
// to the database is MD5(salt.email.password)
$chrPwd = md5(uniqid(rand()).mt_rand());            // returns hex string
$chrPwd = alphanum_encode(substr($chrPwd, 0, 15));  // "compress" a bit
$chrEml = strtolower($chairEmail);                  // store for later

if (empty($chairName)) $chairName = "{$shortName}{$confYear} Chair";
$chairName = my_addslashes($chairName, $cnnct);
$chairEmail = my_addslashes($chairEmail, $cnnct);
$chairPw = md5($salt . $chrEml . $chrPwd);

$qry = "INSERT INTO {$sqlDB}.committee SET revId='1', revPwd='{$chairPw}', 
        name='{$chairName}', email='{$chairEmail}', canDiscuss='1'";
db_query($qry, $cnnct, "Cannot insert program chair to database: ");

if (isset($committee)) foreach ($committee as $m) {
  $m[0] = my_addslashes($m[0], $cnnct);
  $m[1] = my_addslashes($m[1], $cnnct);
  $qry = "INSERT INTO committee
          SET revPwd = '', name = '{$m[0]}', email = '{$m[1]}'";
  @mysql_query($qry, $cnnct); // No error checking, those can be fixed later
}
@mysql_close($cnnct);

/* Done creating the database, now we customize the confConstants file.
 * To minimize the chances of a non-consistent state, we first create the
 * file with a temporary name, and then rename it to the right name.
 */


if (empty($confURL)) $confURL = '.';

// escape things before storing them in the constant file
$longName = str_replace("\\", "\\\\", $longName);
$longName = str_replace("'", "\\'", $longName);

$shortName = str_replace("\\", "\\\\", $shortName);
$shortName = str_replace("'", "\\'", $shortName);

$confURL = str_replace("\\", "\\\\", $confURL);
$confURL= str_replace("'", "\\'", $confURL);

$chrEml= str_replace("\\", "\\\\", $chrEml);
$chrEml= str_replace("'", "\\'", $chrEml);

$adminEmail= str_replace("\\", "\\\\", $adminEmail);
$adminEmail= str_replace("'", "\\'", $adminEmail);

$subDeadline = str_replace("\\", "\\\\", $subDeadline);
$subDeadline = str_replace("'", "\\'", $subDeadline);

$cameraDeadline= str_replace("\\", "\\\\", $cameraDeadline);
$cameraDeadline= str_replace("'", "\\'", $cameraDeadline);

$sqlHost= str_replace("\\", "\\\\", $sqlHost);
$sqlHost= str_replace("'", "\\'", $sqlHost);

$sqlDB = str_replace("\\", "\\\\", $sqlDB);
$sqlDB = str_replace("'", "\\'", $sqlDB);

$sqlUsr = str_replace("\\", "\\\\", $sqlUsr);
$sqlUsr = str_replace("'", "\\'", $sqlUsr);

$sqlPwd = str_replace("\\", "\\\\", $sqlPwd);
$sqlPwd = str_replace("'", "\\'", $sqlPwd);


$constString = "<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
\n"
 . "define('BASE_URL', '$baseURL');\n"
 . "define('CONST_FILE', '$constFile');\n"
 . "define('CONF_NAME', '$longName');\n"
 . "define('CONF_SHORT', '$shortName');\n"
 . "define('CONF_YEAR', '$confYear');\n"
 . "define('CONF_HOME', '$confURL');\n"
 . "define('CHAIR_ID', 1);\n"
 . "define('CHAIR_EMAIL', '$chrEml');\n"
 . "define('EML_CRLF', \"$emlCrlf\");\n"
 . "define('EML_EXTRA_PRM', '$emlExtraPrm');\n"
 . "define('ADMIN_EMAIL', '$adminEmail');\n"
 . "define('SUBMIT_DEADLINE', '$subDeadline');\n"
 . "define('CAMERA_DEADLINE', '$cameraDeadline');\n"
 . "define('CONF_SALT', '$salt');\n"
 . "define('SUBMIT_DIR', '".SUBMIT_DIR."');\n"
 . "define('LOG_FILE', '".LOG_FILE."');\n"
 . "define('MYSQL_HOST', '$sqlHost');\n"
 . "define('MYSQL_DB', '$sqlDB');\n"
 . "define('MYSQL_USR', '$sqlUsr');\n"
 . "define('MYSQL_PWD', '$sqlPwd');\n";

if (isset($_SERVER['HTTPS']))
  $constString .= "define('HTTPS_ON', true);\n";

if (isset($_POST['affiliations']))
     $constString .="define('USE_AFFILIATIONS', true);\n";
else $constString .="define('USE_AFFILIATIONS', false);\n";

if (isset($_POST['anonymous'])) $constString .="define('ANONYMOUS', true);\n";
else                            $constString .="define('ANONYMOUS', false);\n";

if (isset($_POST['revPrefs'])) $constString .="define('REVPREFS', true);\n";
else                           $constString .="define('REVPREFS', false);\n";
      
$constString .= "define('MAX_GRADE', $maxGrade);\n";
$constString .= "define('MAX_CONFIDENCE', 3);\n";

if ($nFrmts <= 0) $constString .= "\$confFormats = NULL;\n";
else {
  $comma = '';
  $constString .= "\$confFormats = array(";
  foreach($fileFormats as $ext => $f) {
    $constString .= "{$comma}\n  '$ext' => array('$f[0]', '$f[1]')";
    $comma = ',';
  }
  $constString .= "\n);\n";
}

if ($nCats <= 0) $constString .= "\$categories = NULL;\n";
else {
  $comma = '';
  $constString .= "\$categories = array(";
  foreach ($categories as $c) {
    $c = str_replace("\\", "\\\\", $c);
    $c = str_replace("'", "\\'", $c);
    $constString .= "{$comma}\n  '$c'";
    $comma = ',';
  }
  $constString .= "\n);\n";
}

if ($nCrits <= 0) $constString .= "\$criteria = NULL;\n";
else {
  $comma = '';
  $constString .= "\$criteria = array(";
  foreach($criteria as $c) {
    $c[0] = str_replace("\\", "\\\\", $c[0]);
    $c[0] = str_replace("'", "\\'", $c[0]);
    $constString .= "{$comma}\n  array('$c[0]', $c[1])";
    $comma = ',';
  }
  $constString .= "\n);\n";
}

$constString .= '?>'; 
/* Note: "?>" must be the last thing in the file (not even "\n" after it) */

$tFile = $constFile . '.' . date('YmdHis');
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

if (!rename($tFile, $constFile)) { 
  exit ("<h1>Cannot rename customization file $tFile</h1>\n");
}
chmod($constFile, 0664); // Just make debugging a bit easier

// Create the submission directory
if (!mkdir(SUBMIT_DIR, 0775)) { 
  exit ("<h1>Cannot create submission directory ".SUBMIT_DIR."</h1>\n");
}
if (!mkdir(SUBMIT_DIR.'/backup', 0775)) { 
  exit ("<h1>Cannot create submission nackup directory ".SUBMIT_DIR."/backup</h1>\n");
}
if (!mkdir(SUBMIT_DIR.'/final', 0775)) { 
  exit ("<h1>Cannot create camera-ready submission directory ".SUBMIT_DIR."/final</h1>\n");
}
copy('log/index.html', SUBMIT_DIR.'/index.html');
copy('log/index.html', SUBMIT_DIR.'/backup/index.html');
copy('log/index.html', SUBMIT_DIR.'/final/index.html');

// All went well, send email to chair and go to confirmation page
$emlCrlf = ($emlCrlf=="\\n") ? "\n" : "\r\n";
$hdr = "From: $shortName $confYear <$chrEml>" . $emlCrlf;
$hdr .= "Cc: $adminEmail" . $emlCrlf;
$hdr .= 'X-Mailer: PHP/' . phpversion();
$sbjct = "Submission and review site for $shortName $confYear is operational";

$prot = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
$baseURL = $prot.'://'.$baseURL;

$msg =<<<EndMark
The submission and review site for $shortName $confYear is now operational.
The start page for submitting papers is

  $baseURL

The start page for administration is

  {$baseURL}chair/

You can login to the administration page using your email address as username,
and with password $chrPwd.

EndMark;

if (ini_get('safe_mode') || empty($emlExtraPrm))
  $success = mail($chrEml, $sbjct, $msg, $hdr);
else
  $success = mail($chrEml, $sbjct, $msg, $hdr, $emlExtraPrm);

if (!$success)
  error_log(date('Y.m.d-H:i:s ')
	    ."Cannot send passowrd $chrPwd to {$sndto}. {$php_errormsg}\n", 
	    3, './log/'.LOG_FILE);

// if in testing mode: insert dummy submissions/reviewers
if (file_exists('chair/testingOnly.php')) {
  header("Location: testingOnly.php?pwd=$chrPwd");
}
else header("Location: receipt-customize.php?pwd=$chrPwd");
?>
