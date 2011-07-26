<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, Tal Moran
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
if (!($lines=file('../init/confParams.php'))) die("Cannot read parameters");
foreach ($lines as $line) {
  $i = strpos($line, '=');           // look for NAME=value
  if ($i===false || $i==0) continue; // no 'NAME=' found
  $nm = substr($line,0,$i);
  $vl = rtrim(substr($line,$i+1));
  if ($nm=='MYSQL_HOST'     || $nm=='MYSQL_DB'   || $nm=='MYSQL_USR'
      || $nm=='MYSQL_PWD'   || $nm=='SUBMIT_DIR' || $nm=='LOG_FILE'
      || $nm=='ADMIN_EMAIL' || $nm=='CONF_SALT'  || $nm=='BASE_URL') {
    if (empty($vl)) die("<h1>Parameter $nm cannot be empty</h1>");
    define($nm, $vl);
  }
}

require_once('../includes/confConstants.php');
require_once('../includes/confUtils.php');

// Check if we can use the PDF stamping based on Zend framework: 

// 1. Look for Zend in the standard include path on this server
//    Note that fopen use the include_path while file_exists does not
if (($fp = @fopen('Zend/Pdf.php', 'r', 1)) and fclose($fp)) {
  define("HAVE_ZEND_PDF", true);
} 
// 2. If not found, look for a local copy of Zend framework
elseif (file_exists('../zend-framework/Zend/Pdf.php')) {
  $zend_dir = realpath("../zend-framework");
  set_include_path(get_include_path() . PATH_SEPARATOR . $zend_dir);
  define("HAVE_ZEND_PDF", true);
} 
// 3. If all fails, conclude that we do not have Zend
else {
  define("HAVE_ZEND_PDF", false);
}

$cnnct = db_connect();
$qry = "SELECT name, email from committee WHERE revId=".CHAIR_ID;
$res = db_query($qry, $cnnct, "Cannot find chair in database: ");
$row = mysql_fetch_row($res) or die("Cannot find chair in database");
define('CHAIR_NAME', $row[0]);
define('CHAIR_EMAIL', $row[1]);

if (isset($notCustomized) && $notCustomized===true) $row=emptyPrms();
else {
  $qry = "SELECT * from parameters ORDER BY version DESC LIMIT 1";
  $res = db_query($qry, $cnnct, "Cannot load parameters: ");
  $row = mysql_fetch_assoc($res) or die("No parameters are specified");
}

define('PARAMS_VERSION', $row['version']);
define('CONF_NAME', $row['longName']);
define('CONF_SHORT', $row['shortName']);
define('CONF_YEAR', $row['confYear']);
define('CONF_HOME', $row['confURL']);
if (isset($row['regDeadline'])) {
  define('USE_PRE_REGISTRATION',true);
  define('REGISTER_DEADLINE',$row['regDeadline']);
} else  {
  define('USE_PRE_REGISTRATION',false);
  define('REGISTER_DEADLINE',NULL);
}
define('SUBMIT_DEADLINE', $row['subDeadline']);
define('CAMERA_DEADLINE', $row['cmrDeadline']);

$confFlags = (int) $row['flags'];
define('CONF_FLAGS', $confFlags);
define('REVPREFS', (($confFlags & FLAG_PCPREFS)?true:false));
define('ANONYMOUS', (($confFlags & FLAG_ANON_SUBS)?true:false));
define('USE_AFFILIATIONS', (($confFlags & FLAG_AFFILIATIONS)?true:false));
define('EML_CRLF', (($confFlags & FLAG_EML_HDR_CRLF)?"\n":"\r\n"));
define('EML_X_MAILER', (($confFlags & FLAG_EML_HDR_X_MAILER)?true:false));
define('EML_EXTRA_PRM', (($confFlags & FLAG_EML_EXTRA_PRM)?true:false));
if ($confFlags & FLAG_SSL) define('HTTPS_ON', true);

define('EML_SENDER', (isset($row['emlSender'])?$row['emlSender']:NULL));
define('TIME_SHIFT', intval($row['timeShift']));
define('MAX_GRADE', $row['maxGrade']);
define('MAX_CONFIDENCE', $row['maxConfidence']);
$x = empty($row['cmrInstrct']) ? NULL : $row['cmrInstrct'];
define('CAMERA_INSTRUCTIONS', $x);
$x = empty($row['acceptLtr']) ? NULL : $row['acceptLtr'];
define('ACCEPT_LTR', $x);
$x = empty($row['rejectLtr']) ? NULL : $row['rejectLtr'];
define('REJECT_LTR', $x);
$x = empty($row['acptSbjct']) ? NULL : $row['acptSbjct'];
define('ACCEPT_SBJCT', $x);
$x = empty($row['rjctSbjct']) ? NULL : $row['rjctSbjct'];
define('REJECT_SBJCT', $x);

$confFormats = formatTable($row['formats']);
$categories = categoryTable($row['categories']);
$criteria = criteriaTable($row['extraCriteria']);

define('PERIOD', $row['period']);
switch($row['period']) {
  case PERIOD_FINAL:
    define('CAMERA_PERIOD', false);
  case PERIOD_CAMERA:
    define('REVIEW_PERIOD', false);
  case PERIOD_REVIEW:
    define('SUBMIT_PERIOD', false);
  case PERIOD_SUBMIT:
    define('PREREG_PERIOD', false);
  case PERIOD_PREREG:
    define('SETUP_PERIOD', false);
  default:
    break;
}
switch($row['period']) {
  case PERIOD_FINAL:
    define('SHUTDOWN', true);
    break;
  case PERIOD_CAMERA:
    define('CAMERA_PERIOD', true);
    break;
  case PERIOD_REVIEW:
    define('REVIEW_PERIOD', true);
    break;
  case PERIOD_SUBMIT:
    define('SUBMIT_PERIOD', true);
    break;
  case PERIOD_PREREG:
    define('PREREG_PERIOD', true);
    break;
  default:
    define('SETUP_PERIOD', true);
}

$footer = <<<EndMark
<br />
This is a version 0.63 (beta) of the <a href="http://alum.mit.edu/www/shaih/websubrev">Web-Submission-and-Review software</a>, written by Shai Halevi from <a href="http://www.research.ibm.com"><img src="../common/ibm-research-logo.jpg" alt="IBM Research"></a>
<br/>
Shai would love to hear your comments and suggestions regarding this software.
EndMark;

$reviewIcon = '<img alt="[Review]" title="Write a report about this submission" src="../common/Review.gif" border=1>';
$revise2Icon = '<img alt="[Revise]" title="Continue your work-in-progress report" src="../common/Revise2.gif" border=1>';
$reviseIcon = '<img alt="[Revise]" title="Revise your report on this submission" src="../common/Revise.gif" border=1>';
$discussIcon1 = '<img alt="[Discuss ]" title="See reports and discussion board" src="../common/Discuss1.gif" border=1>';
$discussIcon2 = '<img alt="[Discuss*]" title="See reports and discussion board (some new items)" src="../common/Discuss2.gif" border=1>';
$ACicon = '<img alt="[AC]" title="Status: accept" src="../common/AC.gif" border=0>';
$MAicon = '<img alt="[MA]" title="Status: maybe accept" src="../common/MA.gif" border=0>';
$DIicon = '<img alt="[DI]" title="Status: needs discussion" src="../common/DI.gif" border=0>';
$MRicon = '<img alt="[MR]" title="Status: maybe reject" src="../common/MR.gif" border=0>';
$REicon = '<img alt="[RE]" title="Status: reject" src="../common/RE.gif" border=0>';
$NOicon = '<img alt="[NO]" title="Status: none" src="../common/NO.gif" border=0>';
$WDicon = '<img alt="[WD]" title="Status: Withdrawn" src="../common/WD.gif" border=0>';

function formatTable($fmtString)
{
  if (!isset($fmtString)) return NULL;
  $fmtString = trim($fmtString);
  if (empty($fmtString))  return NULL;

  $x = explode(';', $fmtString);
  if (is_array($x) && (count($x)>0)) {
    $confFormats = array();
    foreach ($x as $f) if (($y = parse_format($f))!==false) {
      list($desc, $ext, $mime) = $y;
      $confFormats[$ext] = array($desc, $mime);
    }
  }
  else $confFormats = NULL;

  return $confFormats;
}

function categoryTable($catString)
{
  if (!isset($catString)) return NULL;
  $catString = trim($catString);
  if (empty($catString))  return NULL;

  $x = explode(';', $catString);
  if (is_array($x) && count($x)>0) {
    $categories = array();
    foreach ($x as $cat) { $categories[] = trim($cat); }
  }
  else $categories = NULL;

  return $categories;
}

function criteriaTable($criteriaString)
{
  if (!isset($criteriaString)) return NULL;
  $criteriaString = trim($criteriaString);
  if (empty($criteriaString))  return NULL;

  $x = explode(';', $criteriaString);
  if (is_array($x) && count($x)>0) {
    $criteria = array();
    foreach ($x as $c) {
      if ($cr = parse_criterion($c)){ $criteria[]= array($cr[0], $cr[1]);}
    }
  }
  else $criteria = NULL;

  return $criteria;
}

function emptyPrms()
{
  return array(
    'version'       => 0,
    'longName'      => '',
    'shortName'     => '',
    'confYear'      => 0,
    'confURL'       => NULL,
    'regDeadline'   => NULL,
    'subDeadline'   => 0,
    'cmrDeadline'   => 0,
    'maxGrade'      => 6,
    'maxConfidence' => 3,
    'flags'         => FLAG_PCPREFS| FLAG_ANON_SUBS| FLAG_AFFILIATIONS| FLAG_EML_HDR_X_MAILER,
    'timeShift'     => 0,
    'emlSender'     => NULL,
    'period'        => 0,
    'formats'       => '',
    'categories'    => NULL,
    'extraCriteria' => NULL,
    'cmrInstrct'    => NULL,
    'acceptLtr'     => NULL,
    'rejectLtr'     => NULL
    );
}
?>
