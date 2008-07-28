<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

// Get the BASE directory
chdir('..');
$baseDir = getcwd();
chdir('chair');

$prmsFile = '../init/confParams.php';
if (file_exists($prmsFile)) { // Already customized
  exit("<h1>This installation is already initialized</h1>");
}

// Some things in confUtils need the BASE_URL constant
$webServer = trim($_POST['webServer']);
if (empty($webServer)) $webServer = $_SERVER['HTTP_HOST'];
$baseURL = $webServer . $_SERVER['PHP_SELF'];             // this file
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/'));   // the directory
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/')+1); // parent directory
define('BASE_URL', $baseURL);

require_once('../includes/confConstants.php'); 
require_once('../includes/confUtils.php'); 
require_once('../includes/database.php');

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) 
  $_POST  = array_map('stripslashes_deep', $_POST);

// Read all the fields, stripping spurious white-spaces
$shortName = isset($_POST['shortName']) ? trim($_POST['shortName']) : NULL;
$year = isset($_POST['confYear']) ? trim($_POST['confYear']) : NULL;

$chair = isset($_POST['chair']) ? parse_email($_POST['chair']) : false;
if ($chair) {
  $chairName  = $chair[0];
  $chairEmail = $chair[1];
} else {
  $chairName  = $chairEmail = '';
}

$admin = isset($_POST['admin']) ? parse_email($_POST['admin']) : false;
$adminEmail = $admin ? $admin[1] : '';

if (isset($_POST['localMySQL']) && $_POST['localMySQL']=='yes') {
  $sqlHost = 'localhost';
} else {
  $sqlHost   = trim($_POST['MySQLhost']); // MySQL server as seen by web server
  if (empty($sqlHost)) $sqlHost = 'localhost';
}
$sqlDB       = trim($_POST['confDB']);
$sqlRoot     = trim($_POST['rootNm']);
$sqlRootPwd  = trim($_POST['rootPwd']);
$sqlUsr      = trim($_POST['user']);
$sqlPwd      = trim($_POST['pwd']) ;

$subDir      = trim($_POST['subDir']) ;
if (empty($subDir)) { $subDir = $baseDir.'/subs'; }

// replace '\' by '/', remove trailing '/' if needed
$subDir = str_replace("\\", "/", $subDir);
$lastChar = substr($subDir, -1);
if ($lastChar=="/") $subDir = substr($subDir, 0, -1);

// Check that the required fileds are specified

if (empty($sqlDB) || empty($chairEmail) || empty($adminEmail)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify a name for the database, and the chair and administrator email addresses\n");
}

if ((empty($sqlRoot) || empty($sqlRootPwd)) 
    && (empty($sqlUsr) || empty($sqlPwd))) {
  print "<h1>Cannot create/access MySQL database</h1>\n";
  print "To automatically generate MySQL database, you must specify the MySQL root username and password.<br/>\n";
  exit("Otherwise you must manually create the database and specify the name and password of a user that has access to that database.\n");
}

/* We are ready to initialize the installation */

// Create the error log file (also to check that $subDir is writable)
$logFile = $subDir.'/log'.time();
define('LOG_FILE', $logFile); // needed for error reporting in some functions
if (!file_exists($subDir)) mkdir($subDir);
if (!($fd = fopen(LOG_FILE, 'w'))) {          // Open for write
  exit("<h1>Cannot create log file $logFile</h1>\n");
}
fclose($fd);
error_log(date('Y.m.d-H:i:s ')."Log file created\n", 3, LOG_FILE);

// We generate some randomness for salting the password hashes
$salt = alphanum_encode(md5(uniqid(rand()).mt_rand()));

// If MySQL user & pwd are not specified, create a new database and user
if (empty($sqlUsr) || empty($sqlPwd)) {

  // Test that we can actually connect using the root username/pwd
  $cnnct = db_connect($sqlHost, $sqlRoot, $sqlRootPwd, NULL);

  // Create the database
  $sqlDB = makeName($sqlDB); // make sure this is a valid name
  $qry = 'DROP DATABASE IF EXISTS '.$sqlDB;
  db_query($qry, $cnnct, "Cannot create a new database $sqlDB: ");
  $qry = 'CREATE DATABASE IF NOT EXISTS '.$sqlDB;
  db_query($qry, $cnnct, "Cannot create a new database $sqlDB: ");

  mysql_select_db($sqlDB, $cnnct);
  create_tabels($cnnct); // from database.php, create the tables in the DB

  // Create new user if not specified
  if (empty($sqlUsr)) $sqlUsr = $sqlDB;
  else                $sqlUsr = my_addslashes($sqlUsr, $cnnct);
  $sqlPwd = md5(uniqid(rand()). mt_rand(). $sqlUsr); // returns hex string
  $sqlPwd = alphanum_encode(substr($sqlPwd, 0, 12)); // "compress" a bit

  if ($sqlHost=='localhost') {
    $qry = "GRANT SELECT, INSERT, UPDATE, DELETE ON {$sqlDB}.* "
      ."TO '$sqlUsr'@'localhost' IDENTIFIED BY '{$sqlPwd}'";
  } else {
    $qry = "GRANT SELECT, INSERT, UPDATE, DELETE ON {$sqlDB}.* "
      ."TO '$sqlUsr' IDENTIFIED BY '{$sqlPwd}'";
  }
  db_query($qry, $cnnct, "Cannot GRANT privileges: ");

  mysql_close($cnnct);
}

// The database and user should already exist by now
$cnnct = db_connect($sqlHost, $sqlUsr, $sqlPwd, $sqlDB);

// If we got here, then database and all tables were created

// Store database parameters in file
if (file_exists($prmsFile)) unlink($prmsFile); // just in case
if (!($fd = fopen($prmsFile, 'w'))) {          // Open for write
  exit("<h1>Cannot create the parameters file at $prmsFile</h1>\n");
}
$prmsString = "<?php\n"
  . "/* Parameters for a new installation: this file is formatted as a PHP\n"
  . " * file to ensure that accessing it directly by mistake does not cause\n"
  . " * the server to send this information to a client.\n"
  . "MYSQL_HOST=$sqlHost\n"
  . "MYSQL_DB=$sqlDB\n"
  . "MYSQL_USR=$sqlUsr\n"
  . "MYSQL_PWD=$sqlPwd\n"
  . "SUBMIT_DIR=$subDir\n"
  . "LOG_FILE=$logFile\n"
  . "ADMIN_EMAIL=$adminEmail\n"
  . "CONF_SALT=$salt\n"
  . "BASE_URL=$baseURL\n"
  . " ********************************************************************/\n"
  . "?>\n";
if (!fwrite($fd, $prmsString)) {
  exit ("<h1>Cannot write into parameters file $tFile</h1>\n");
}
fclose($fd);
chmod($prmsFile, 0440);

// Create the submission sub-directories
if (!mkdir("$subDir/backup", 0775)) { 
  exit ("<h1>Cannot create submission backup directory $subDir/backup</h1>\n");
}
if (!mkdir("$subDir/final", 0775)) { 
  exit ("<h1>Cannot create camera-ready submission directory $subDir/final</h1>\n");
}
if (!mkdir("$subDir/attachments", 0775)) { 
  exit ("<h1>Cannot create attachments directory $subDir/attachments</h1>\n");
}
copy('../init/.htaccess', $subDir.'/.htaccess');
copy('../init/index.html', $subDir.'/index.html');
copy('../init/index.html', $subDir.'/backup/index.html');
copy('../init/index.html', $subDir.'/final/index.html');
copy('../init/index.html', $subDir.'/attachments/index.html');

// Insert the PC chair into the committee table. Also generates password
// for the chair and send it by email. Initialy, the password is written
// to the database "in the clear" (to be consistent with the non-web-based
// method of initialization). After customization, the value that will
// be written to the database is MD5(salt.email.password)
$chrPwd = md5(uniqid(rand()).mt_rand());            // returns hex string
$chrPwd = alphanum_encode(substr($chrPwd, 0, 15));  // "compress" a bit
$chairEmail = strtolower($chairEmail);
$chrEml = my_addslashes($chairEmail, $cnnct);
$chrNam = my_addslashes($chairName, $cnnct);

$qry = "INSERT INTO committee SET revId=1, revPwd='$chrPwd', name='$chrNam', email='$chrEml', canDiscuss=1";
db_query($qry, $cnnct, "Cannot insert program chair to database: ");

// Send email to chair and admin with the password for using this site
$hdr   = "From: $adminEmail";
$sndTo = "$adminEmail, $chairEmail";
$sbjct = "New submission and review site initialized";

$prot = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
$baseURL = $prot.'://'.$baseURL;
$msg =<<<EndMark
A new submission and review site was initialized, it now needs to be
customized for its conference. The administration page is accessible
from

  {$baseURL}chair/

using username $chairEmail and password $chrPwd

EndMark;
mail($sndTo, $sbjct, $msg, $hdr);
header("Location: customize.php?username=$chairEmail&password=$chrPwd");
?>
