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
if ($lines=file($prmsFile)) { // file already there
  $MYSQL_HOST = $MYSQL_DB = $MYSQL_USR = $MYSQL_PWD = $SUBMIT_DIR
   = $LOG_FILE = $ADMIN_EMAIL = $CONF_SALT = $MYSQL_PREFIX = '';
  foreach ($lines as $line) {
    $i = strpos($line, '=');           // look for NAME=value
    if ($i==0 || substr($line,0,2)=='//') 
      continue; // comment or no 'NAME=' found
    $nm = substr($line,0,$i);
    $vl = rtrim(substr($line,$i+1));
    if ($nm=='MYSQL_HOST'      || $nm=='MYSQL_DB'   || $nm=='MYSQL_USR'
	|| $nm=='MYSQL_PWD'    || $nm=='SUBMIT_DIR' || $nm=='LOG_FILE'
	|| $nm=='ADMIN_EMAIL'  || $nm=='CONF_SALT'  || $nm=='BASE_URL'
	|| $nm=='MYSQL_PREFIX') {
      if (empty($vl)) die("<h1>Parameter $nm cannot be empty</h1>");
      $$nm = $vl;
    }
  }
}

// Some things in confUtils need the BASE_URL constant
if (empty($BASE_URL)) {
  $webServer = trim($_POST['webServer']);
  if (empty($webServer)) $webServer = $_SERVER['HTTP_HOST'];
  $BASE_URL = $webServer . $_SERVER['PHP_SELF'];              // this file
  $BASE_URL = substr($BASE_URL, 0, strrpos($BASE_URL, '/'));  // the directory
  $BASE_URL = substr($BASE_URL, 0, strrpos($BASE_URL, '/')+1);// parent dir
}
define('BASE_URL', $BASE_URL);

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

if (empty($ADMIN_EMAIL)) {
  $admin = isset($_POST['admin']) ? parse_email($_POST['admin']) : false;
  $ADMIN_EMAIL = $admin ? $admin[1] : '';
}

if (empty($MYSQL_HOST)) {
  if (isset($_POST['localMySQL']) && $_POST['localMySQL']=='yes') {
    $MYSQL_HOST = 'localhost';
  } else {
    $MYSQL_HOST= trim($_POST['MySQLhost']);// MySQL server as seen by web server
    if (empty($MYSQL_HOST)) $MYSQL_HOST = 'localhost';
  }
}

if (empty($MYSQL_DB))
  $MYSQL_DB = makeName(trim($_POST['confDB'])); // make sure its a valid name
$sqlRoot     = trim($_POST['rootNm']);
$sqlRootPwd  = trim($_POST['rootPwd']);
if (empty($MYSQL_USR))
  $MYSQL_USR = trim($_POST['user']);
if (empty($MYSQL_PWD))
  $MYSQL_PWD = trim($_POST['pwd']);
if (empty($MYSQL_PREFIX) && !empty($_POST['SQLprefix']))
  $MYSQL_PREFIX = makeName(trim($_POST['SQLprefix']));

// $_POST['newDB'] should be either 'newDB', 'newTbls', or 'existing'
if (!empty($_POST['newDB'])) $newDB = trim($_POST['newDB']);
else {  // this shouldn't happen, try to use sensible defaults
  if (!empty($sqlRoot) && !empty($sqlRootPwd))
       $newDB = "newDB";
  else $newDB = "newTbls";
}

if (empty($SUBMIT_DIR)) {
  $SUBMIT_DIR = trim($_POST['subDir']) ;
  if (empty($SUBMIT_DIR)) { $SUBMIT_DIR = $baseDir.'/subs'; }
}
// replace '\' by '/', remove trailing '/' if needed
$SUBMIT_DIR = str_replace("\\", "/", $SUBMIT_DIR);
$lastChar = substr($SUBMIT_DIR, -1);
if ($lastChar=="/") $SUBMIT_DIR = substr($SUBMIT_DIR, 0, -1);

// Check that the required fileds are specified

if (empty($MYSQL_DB) || empty($chairEmail) || empty($ADMIN_EMAIL)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify a name for the database, and the chair and administrator email addresses\n");
}

if ((empty($sqlRoot) || empty($sqlRootPwd)) 
    && (empty($MYSQL_USR) || empty($MYSQL_PWD))) {
  print "<h1>Cannot create/access MySQL database</h1>\n";
  print "To automatically generate MySQL database, you must specify the MySQL root username and password.<br/>\n";
  exit("Otherwise you must manually create the database and specify the name and password of a user that has access to that database.\n");
}

/* We are ready to initialize the installation */

// Create the error log file (also to check that $SUBMIT_DIR is writable)
if (empty($LOG_FILE))
  $LOG_FILE = $SUBMIT_DIR.'/log'.time();
define('LOG_FILE', $LOG_FILE); // needed for error reporting in some functions
if (!file_exists($SUBMIT_DIR)) mkdir($SUBMIT_DIR);
if (!($fd = fopen(LOG_FILE, 'w'))) { // Open for write, create if not there
  exit("<h1>Cannot open/create log file $LOG_FILE</h1>\n");
}
fclose($fd);
error_log(date('Y.m.d-H:i:s ')."Log file created\n", 3, LOG_FILE);

// We generate some randomness for salting the password hashes
if (empty($CONF_SALT))
  $CONF_SALT = alphanum_encode(sha1(uniqid($MYSQL_DB.rand()).mt_rand()));

// If MySQL admin details are specified, use them to create database/user
if (!empty($sqlRoot) && !empty($sqlRootPwd)) {

  // Test that we can actually connect using the root username/pwd
  $rootDB = new PDO("mysql:host=$MYSQL_HOST;charset=utf8",$sqlRoot,$sqlRootPwd,
		    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

  // Create the database if needed
  if ($newDB == 'newDB') {
    $rootDB->query('CREATE DATABASE IF NOT EXISTS '.$MYSQL_DB);
  }
  $rootDB->query("use $MYSQL_DB");

  if ($newDB == 'newDB' || $newDB == 'newTbls')
    create_tabels($rootDB,$MYSQL_PREFIX); // from database.php

  // Create new user if not specified
  if (empty($MYSQL_USR)) $MYSQL_USR = $MYSQL_DB;
  if (empty($MYSQL_PWD)) {
    $MYSQL_PWD= sha1(uniqid(rand()).mt_rand().$MYSQL_USR); // returns hex string
    $MYSQL_PWD= alphanum_encode(substr($MYSQL_PWD, 0, 12));  // "compress" a bit
  }
  $host = ($MYSQL_HOST=='localhost')? "@'localhost'" : "";
  $tbls = array_keys($dbTables);
  foreach ($tbls as $tblName) {
    $qry = "GRANT SELECT, INSERT, UPDATE, DELETE ON $MYSQL_DB.{$MYSQL_PREFIX}{$tblName} TO ?{$host} IDENTIFIED BY ?";
    pdo_query($qry, array($MYSQL_USR,$MYSQL_PWD),'Cannot GRANT privileges: ',$rootDB);
  }
  $rootDB = null; // close the connection
}
else { // no admin password, try to use the user name/password if needed
  if ($newDB == 'newDB')
    exit('<h1>SQL Admin Details Needed to Create a New Database</h1>');

  if ($newDB == 'newTbls') {
    // Try to connect using the user's credentials
    $db = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8",
		  $MYSQL_USR, $MYSQL_PWD,
		  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    create_tabels($db,$MYSQL_PREFIX); // from database.php
    $db = null;                    // close the connection
  }
}

// If we got here, then database and all tables were created
$db = new PDO("mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8",
	      $MYSQL_USR, $MYSQL_PWD,
	      array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

// Create the submission sub-directories
if (!mkdir("$SUBMIT_DIR/scratch", 0775)) { 
  exit ("<h1>Cannot create scratch directory $SUBMIT_DIR/scratch</h1>\n");
}
if (!mkdir("$SUBMIT_DIR/backup", 0775)) { 
  exit ("<h1>Cannot create submission backup directory $SUBMIT_DIR/backup</h1>\n");
}
if (!mkdir("$SUBMIT_DIR/final", 0775)) { 
  exit ("<h1>Cannot create camera-ready submission directory $SUBMIT_DIR/final</h1>\n");
}
if (!mkdir("$SUBMIT_DIR/attachments", 0775)) { 
  exit ("<h1>Cannot create attachments directory $SUBMIT_DIR/attachments</h1>\n");
}
copy('../init/.htaccess', $SUBMIT_DIR.'/.htaccess');
copy('../init/index.html', $SUBMIT_DIR.'/index.html');
copy('../init/index.html', $SUBMIT_DIR.'/scratch/index.html');
copy('../init/index.html', $SUBMIT_DIR.'/backup/index.html');
copy('../init/index.html', $SUBMIT_DIR.'/final/index.html');
copy('../init/index.html', $SUBMIT_DIR.'/attachments/index.html');

// Insert the PC chair into the committee table. Also generates password
// for the chair and send it by email. Initially, the password is written
// to the database "in the clear" (to be consistent with the non-web-based
// method of initialization). After customization, the value that will
// be written to the database is SHA1(salt.email.password)
$chrPwd = sha1(uniqid(rand()).mt_rand());           // returns hex string
$chrPwd = alphanum_encode(substr($chrPwd, 0, 15));  // "compress" a bit
$chairEmail = strtolower($chairEmail);

$qry = "INSERT INTO {$MYSQL_PREFIX}committee SET revId=?, revPwd=?, name=?, email=?, canDiscuss=?, flags=?";
pdo_query($qry, array(1,$chrPwd,$chairName,$chairEmail,1,FLAG_IS_CHAIR),
	  "Cannot insert program chair to database: ");

// Store database parameters in file

if (!$lines) { // the parameter file is not there yet
  $prmsString = "<?php\n"
  . "/* Parameters for a new installation: this file is formatted as a PHP\n"
  . " * file to ensure that accessing it directly by mistake does not cause\n"
  . " * the server to send this information to a client.\n"
  . "MYSQL_HOST=$MYSQL_HOST\n"
  . "MYSQL_DB=$MYSQL_DB\n"
  . "MYSQL_PREFIX=$MYSQL_PREFIX\n"
  . "MYSQL_USR=$MYSQL_USR\n"
  . "MYSQL_PWD=$MYSQL_PWD\n"
  . "SUBMIT_DIR=$SUBMIT_DIR\n"
  . "LOG_FILE=$LOG_FILE\n"
  . "ADMIN_EMAIL=$ADMIN_EMAIL\n"
  . "CONF_SALT=$CONF_SALT\n"
  . "BASE_URL=$BASE_URL\n"
  . " ********************************************************************/\n"
  . "?>\n";

  // Check that you can create the parameter file

  if (!($fd = fopen($prmsFile, 'w')) || !fwrite($fd, $prmsString)) {
    print "<h1>Cannot write into parameters file $prmsFile</h1>\n";
    print "Parameter file is as follows:\n<pre>\n".htmlspecialchars($prmsString)."\n</pre>\n";
    print "After creating this file, you need to accees {$BASE_URL}chair/<br/>\n";
    print "using username $chairEmail and password $chrPwd";
    exit();
  }
  fclose($fd);
  chmod($prmsFile, 0440);
}

// Send email to chair and admin with the password for using this site
$hdr   = "From: $ADMIN_EMAIL";
$sndTo = "$ADMIN_EMAIL, $chairEmail";
$sbjct = "Submission/review site for $MYSQL_PREFIX is up";

$prot = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
$BASE_URL = $prot.'://'.$BASE_URL;
$msg =<<<EndMark
The new submission and review site was initialized, it now needs to be
customized for its conference. To complete the installation, you need
to access the web-page at

  {$BASE_URL}chair/customize.php

To access that page use username $chairEmail and password $chrPwd

On that page you need to provide various details such as the conference
name, various deadlines, etc. Everything that you enter there can later
be modified from the chair interface. Once you finished "customizing"
it, the site will be open to submissions from the URL

  {$BASE_URL}submit/

and you could administer it from

  {$BASE_URL}chair/

There is a demo site for this software where you could see how most of
the pages look like, so you can get a preview of "what would happen
when I press this button". The demo is available at

 http://people.csail.mit.edu/shaih/websubrev/demo/0.63/

That site is "interactive" in the sense that pressing any button or
link should take you to the page that you would see if this was a real
live system. (But of course the choices and data that you enter will
be ignored since it is not really a live system.)

Please direct any question to websubrev@iacr.org

Good luck with your conference.

EndMark;
mail($sndTo, $sbjct, $msg, $hdr);

if (file_exists($SUBMIT_DIR.'/copyright.html')) {
  rename($SUBMIT_DIR.'/copyright.html', $SUBMIT_DIR.'/final/copyright.html');
}

header("Location: customize.php?username=$chairEmail&password=$chrPwd");
?>
