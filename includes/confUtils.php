<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

$prot = (defined('HTTPS_ON') || isset($_SERVER['HTTPS']))? 'https' : 'http';
$logo = $prot.'://'.BASE_URL."ibm-research-logo.jpg";
$footer = <<<EndMark
<br />
This is a version 0.53 (beta) of the
<a href="http://alum.mit.edu/www/shaih/websubrev">Web-Submission-and-Review
software</a>, written by Shai Halevi from
<a href="http://www.research.ibm.com"><img src="$logo" alt="IBM Research"></a>
<br/>
Shai would love to hear your comments and suggestions regarding this software.
EndMark;

$reviewIcon = '<img alt="[Review]" title="Write a report about this submission" src="'.$prot.'://'.BASE_URL.'review/Review.gif" border=1>';
$reviseIcon = '<img alt="[Revise]" title="Revise your report on this submission" src="'.$prot.'://'.BASE_URL.'review/Revise.gif" border=1>';
$discussIcon1 = '<img alt="[Discuss ]" title="See reports and discussion board" src="'.$prot.'://'.BASE_URL.'review/Discuss1.gif" border=1>';
$discussIcon2 = '<img alt="[Discuss*]" title="See reports and discussion board (some new items)" src="'.$prot.'://'.BASE_URL.'review/Discuss2.gif" border=1>';
$ACicon = '<img alt="[AC]" title="Status: accept" src="'.$prot.'://'.BASE_URL.'review/AC.gif" border=0>';
$MAicon = '<img alt="[MA]" title="Status: maybe accept" src="'.$prot.'://'.BASE_URL.'review/MA.gif" border=0>';
$DIicon = '<img alt="[DI]" title="Status: needs discussion" src="'.$prot.'://'.BASE_URL.'review/DI.gif" border=0>';
$MRicon = '<img alt="[MR]" title="Status: maybe reject" src="'.$prot.'://'.BASE_URL.'review/MR.gif" border=0>';
$REicon = '<img alt="[RE]" title="Status: reject" src="'.$prot.'://'.BASE_URL.'review/RE.gif" border=0>';
$NOicon = '<img alt="[NO]" title="Status: none" src="'.$prot.'://'.BASE_URL.'review/NO.gif" border=0>';
$WDicon = '<img alt="[WD]" title="Status: Withdrawn" src="'.$prot.'://'.BASE_URL.'review/WD.gif" border=0>';

define('VOTE_ON_SUBS', 1);
define('VOTE_ON_ALL',  2);
define('VOTE_ON_RE',   4);
define('VOTE_ON_MR',   8);
define('VOTE_ON_NO',  16);
define('VOTE_ON_DI',  32);
define('VOTE_ON_MA',  64);
define('VOTE_ON_AC', 128);

function show_legend()
{
  global $WDicon, $NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon;
  global $reviseIcon, $reviewIcon, $discussIcon1, $discussIcon2;

  $legend = <<<EndMark
<hr/>
<table><tbody>
<tr><td>Legend:</td><td>$NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon: Status marks (None, [Maybe-]Reject, Discuss, [Maybe-]Accept)</td>
<tr><td></td><td>$reviewIcon, $reviseIcon: Submit a new/revised report about a submissoin</td></tr>
<tr><td></td><td>$discussIcon1, $discussIcon2: See reports and discussion boards (all read / some unread)</td></tr>
</tbody></table>
EndMark;

  return $legend;
}


// If the user is found in the database, returns the user details
// as array(id, name, email). Otherwise returns false.
function auth_PC_member($eml, $pwd, $id=NULL)
{
  // Test the username and password parameters
  if (!isset($eml) || !isset($pwd))
    return false;

  $cnnct = db_connect();

  // Create a digest of the password and sanitize the email address
  $eml = strtolower(trim($eml));

  $pwd = md5(CONF_SALT . $eml . $pwd);
  $eml = my_addslashes($eml, $cnnct);

  // Formulate the SQL find the user
  $qry = "SELECT revId, name, email, canDiscuss, threaded FROM committee WHERE";
  if (isset($id)) {
    $id = (int) trim($id);
    $qry .= " revId='{$id}' AND email = '{$eml}' AND revPwd = '{$pwd}'";
  }
  else $qry .= " email = '{$eml}' AND revPwd = '{$pwd}'";

  // Go to the database to look for the user
  $res = db_query($qry, $cnnct, "Cannot authenticate against database: ");

  // exactly one row? if not then we failed
  if (mysql_num_rows($res) != 1) 
    return false;

  // return the user details
  return mysql_fetch_row($res);
}

function my_addslashes($str, $cnnct=NULL)
{
  if (!isset($cnnct))
      $cnnct=@mysql_connect(MYSQL_HOST, MYSQL_USR, MYSQL_PWD);
  return mysql_real_escape_string($str, $cnnct);
}

function db_connect($host=MYSQL_HOST,
		    $usr=MYSQL_USR, $pwd=MYSQL_PWD, $db=MYSQL_DB)
{
  if (!($cnnct=@mysql_connect($host, $usr, $pwd))) {
    error_log(date('Y.m.d-H:i:s ').mysql_error()."\n", 3, 'log/'.LOG_FILE);
    exit("<h1>Cannot connect to MySQL server</h1>\n" .
	 "mysql_connect($host, $usr, $pwd)<br />". mysql_error());
  }
  if (isset($db) && !@mysql_select_db($db, $cnnct)) {
    error_log(date('Y.m.d-H:i:s ').mysql_error()."\n", 3, './log/'.LOG_FILE);
    exit("<h1>Cannot select database $db</h1>\n" . mysql_error());
  }
  return $cnnct;
}

function db_query($qry, $cnnct, $desc='')
{
  $res=mysql_query($qry, $cnnct);
  if ($res===false) {
    error_log(date('Y.m.d-H:i:s ')
      .mysql_errno().'-'.mysql_error()." $php_errormsg\n", 3, './log/'.LOG_FILE);
    exit("<h1>Query Failed</h1>\n{$desc}"
	 . "Query: <pre>". htmlspecialchars($qry) . "</pre>\nError: "
	 . htmlspecialchars(mysql_error())
	 . " " . htmlspecialchars($php_errormsg));
  }
  return $res;
}

function make_link($linkto, $text, $notLink=false)
{
  if ($notLink) { return "[<span style=\"color: gray;\">$text</span>] \n"; }
  else          { return "[<a href=\"$linkto\">$text</a>] \n"; }
}

//$filesErrs = array('No error', 
//	   'Uploaded file size > upload_max_filesize directive in php.ini',
//	   'Uploaded file > MAX_FILE_SIZE in the html form', 
//	   'File was only partially uploaded', 
//	   'No file was uploaded'
//);

/* Status codes are: +-1: new submission
 *                   +-2: revision
 *                   +-3: withdrawal
 * Negative values means that there was some problem in processing
 * the submission. In this case the administrator is CC'ed on the email.
 */
function email_submission_details($sndto, $status, $sid, $pwd, $ttl = NULL, 
	                          $athr = NULL, $cntct = NULL, $abs = NULL,
				  $cat = NULL, $kwrd = NULL, $cmnt = NULL,
				  $fileFormat = NULL)
{
  $emlCrlf = (EML_CRLF == "\n") ? "\n" : "\r\n";
  $hdr = 'From: '.CONF_SHORT.' '.CONF_YEAR.' Chair <'.CHAIR_EMAIL.">$emlCrlf";
  $hdr .= 'Cc: ' . CHAIR_EMAIL . $emlCrlf;
  if ($status < 0) { $hdr .= 'Bcc: '.ADMIN_EMAIL.$emlCrlf; }
  $hdr .= 'X-Mailer: PHP/' . phpversion();

  $dots = (strlen($ttl) > 50) ? '... ' : ' ';

  switch (abs($status)) {
  case 1:
    $sbjct = "Submission " . substr($ttl, 0, 50) . $dots . "received";
    break;
  case 2:
    $sbjct = "Revised submission " . substr($ttl, 0, 50) . $dots . "received";
    break;
  default:
    $sbjct = "Submission $sid withdrawn";
    break;
  }

  $msg = "The submissions details are as follows:\n";
  if (!empty($fileFormat) && substr($fileFormat, -12)=='.unsupported') {
    $msg .= "UNSUPPORTED FORMAT: \t{$fileFormat}\n";
  }
  if ($sid != 0)     { $msg .= "Submission number:  \t{$sid}\n"; }
  if (!empty($pwd))  { $msg .= "Submission password:\t{$pwd}\n\n"; }

  if (!empty($ttl))  { $msg .= "Title:    \t{$ttl}\n"; }
  if (!empty($athr)) { $msg .= "Authors:  \t{$athr}\n"; }
  if (!empty($cntct)){ $msg .= "Contact:  \t{$cntct}\n"; }
  if (!empty($cat))  { $msg .= "Category: \t{$cat}\n"; }
  if (!empty($kwrd)) { $msg .= "Key words:\t{$kwrd}\n"; }
  if (!empty($cmnt)) { $msg .= "Comments: \t{$cmnt}\n"; }
  if (!empty($abs))  { $msg .= "\nAbstract:\n" . wordwrap($abs, 78) . "\n"; }

  if (ini_get('safe_mode') || !defined('EML_EXTRA_PRM'))
    //      || empty(EML_EXTRA_PRM))
    $success = mail($sndto, $sbjct, $msg, $hdr);
  else
    $success = mail($sndto, $sbjct, $msg, $hdr, EML_EXTRA_PRM);

  if (!$success)
    error_log(date('Y.m.d-H:i:s ')
	      ."Cannot send receipt to {$sndto}. {$php_errormsg}\n", 
	      3, './log/'.LOG_FILE);
}


// This function could be expanded
function determine_format($fType, $fName, $fLocation)
{
  global $confFormats;

  // See if the extension or MIME type match the current format
  $fType = strtolower($fType);
  if (is_array($confFormats)) {
    foreach ($confFormats as $e=>$f) {
      $ee = str_replace(".", "\\.", $e);    // escape . and $ in regexp
      $ee = str_replace("\$", "\\\$", $ee);
      $pattern = '/'.$ee.'$/i';
      if (preg_match($pattern, $fName)) return $e;
    }
    foreach ($confFormats as $e=>$f) 
      if ($fType==$f[1]) return $e;
  }

  // In principle, we can also try to look in the file itself,
  // e.g. as in the Unix syscall file. But we don't do it yet.

  // Unsupported format
  $ext = strtolower(substr(strrchr($fName, '.'), 1));
  return substr($ext, 0, 20) . '.unsupported';
}

/* "Compress" a hexa-decimal string by encoding using the 64 letters
 * [a-z], [A-Z], [0-9], '_', and '~'. This function is "binary safe",
 * in that it always returns a string made of these 64 cheracters, but
 * if the input string is not hexadecimal then some of the entropy in it
 * will be lost.
 */
function alphanum_encode($hexstr)
{
  $len = strlen($hexstr);
  $hexstr = strtoupper($hexstr);

  $out = '';
  for ($i=0; $i<$len; $i+=3) {
    $c1 = ord($hexstr[$i]);
    if ($c1 >= ord('A')) $c1 -= (ord('A') - 10);
    else if ($c1 >= ord('0')) $c1 -= ord('0');

    if ($len > $i+1) {
      $c2 = ord($hexstr[$i+1]);
      if ($c2 >= ord('A')) $c2 -= (ord('A') - 10);
      else if ($c2 >= ord('0')) $c2 -= ord('0');
      $c1 = ($c1 * 16) + $c2;

      if ($len > $i+2) {
	$c2 = ord($hexstr[$i+2]);
	if ($c2 >= ord('A')) $c2 -= (ord('A') - 10);
	else if ($c2 >= ord('0')) $c2 -= ord('0');
	$c1 = ($c1 * 16) + $c2;
      }
    }

    $c2 = $c1 % 64;
    $c1 = (($c1 - $c2) / 64) % 64; // making sure that also $c1 < 64

    if ($c1 < 10) $out .= chr(ord('0')+$c1);
    else if ($c1 < 36) $out .= chr(ord('A')-10+$c1);
    else if ($c1 < 62) $out .= chr(ord('a')-36+$c1);
    else if ($c1 == 62) $out .= '_';
    else $out .= '~';

    if ($c2 < 10) $out .= chr(ord('0')+$c2);
    else if ($c2 < 36) $out .= chr(ord('A')-10+$c2);
    else if ($c2 < 62) $out .= chr(ord('a')-36+$c2);
    else if ($c2 == 62) $out .= '_';
    else $out .= '~';
  }
  return $out;
}

// Thanks to aderyn@gmail.com for this little marvel
function stripslashes_deep($value)
{
  return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
}


function implode_assoc($glue, $pieces, $inner_glue='=', $skip_empty=false)
{
 $output=array();
 foreach($pieces as $key=>$item) {
  if(!$skip_empty || isset($item))
    $output[] = $key . $inner_glue . $item;
 }
 return implode($glue, $output);
}

function return_to_caller($url, $extraPrms='', $anchor='')
{
  $whoseCalling = empty($_SERVER['HTTP_REFERER']) ?
    $url : $_SERVER['HTTP_REFERER'];

  if (!empty($extraPrms)) {
    $sep = (strpos($whoseCalling, '?')===false) ? '?' : '&';
    $whoseCalling .= $sep . $extraPrms;
  }

  if (!empty($anchor)) {
    if (($pos=strpos($whoseCalling, '#'))===false)
      $whoseCalling .= $anchor;
    else 
      $whoseCalling = subsrt($whoseCalling, 0, $pos) . $anchor;
  }
  header("Location: $whoseCalling");
  exit();
}

/* Make a version of $someString that is suitable for names: make sure
 * that the first char is alpha and the others are alphanumeric or '_'
 */
function makeName($someString) 
{
  if (!preg_match('/^[a-z][0-9a-z_]*$/i', $someString)) {
    // replace characters not in pattern with a '_'
    $name = preg_replace('/[^0-9A-Za-z_]+/', '_', $someString);

    // Make sure the first letter is alpha
    if (!ctype_alpha($name[0])) $name = 'X' . $name;
    return $name;
  }
  return $someString;
}

// The functions below  currently don't try to validate their input 

// expects either email or "Name <email>" formats
function parse_email($str)
{
  // $pat1 = '[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}'; // jusr email address
  // $pat2 = '[A-Z0-9. ]+ <' . $pat1 . '>';             // Name <address>

  $tok = explode('<', $str); // Split the string around '<'

  if (count($tok)>1) {       // At least one '<' found
    $tok[0] = trim($tok[0]);
    $i = strpos($tok[1], '>');
    if ($i !== false) $tok[1] = trim(substr($tok[1], 0, $i)); 
  }
  else { // Perhaps its just an email address with no name?
    $tok[1] = trim($tok[0]);
    $tok[0] = '';
  }

  if (empty($tok[1])) return false;  // email address field is empty

  return $tok;
}

// expects "Name (maxval)" format, returns an array { Name, maxval }
function parse_criterion($str)
{
  $tok = explode('(', $str); // Split the string around '('
  $cr = array('', 3);

  if (count($tok)>1) {       // At least one '(' found
    $cr[0] = trim($tok[0]);
    if (empty($cr[0])) return false;

    $i = strpos($tok[1], ')');
    if ($i !== false) $cr[1] = (int) trim(substr($tok[1], 0, $i));

    if (($cr[1] < 2) || ($cr[1] > 9)) {
      $cr[1] = 3; // default max value is 3
    } 
    return $cr;
  }

  return false;
}

// expects "Name (extension, mime-type)" format
function parse_format($str)
{
  $tok = explode('(', $str); // Split the string around '('

  if (count($tok)<=1) return false;

  // At least one '(' found
  $fmt = array(trim($tok[0]), '', '');

  $i = strpos($tok[1], ')');
  if ($i === false) return false;

  $tok = explode(',' , substr($tok[1], 0, $i));
  if (count($tok)<=1) return false;

  // at least one ',' found
  $fmt[1] = trim($tok[0]);
  if ($fmt[1][0] == '.') // remove leading dot if exists
    $fmt[1] = substr($fmt[1], 1);
  if (empty($fmt[1])) return false; // extension must be non-empty

  $fmt[2] = trim($tok[1]);

  return $fmt;
}

function show_status($status)
{
  global $WDicon, $NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon;

  if ($status == 'Withdrawn') {
    return "<span class=WD>$WDicon</span>";
  } else if ($status == 'Reject') {
    return "<span class=RE>$REicon</span>";
  } else if ($status == 'Perhaps Reject') {
    return "<span class=MR>$MRicon</span>";
  } else if ($status == 'Needs Discussion') {
    return "<span class=DI>$DIicon</span>";
  } else if ($status == 'Maybe Accept') {
    return "<span class=MA>$MAicon</span>";
  } else if ($status == 'Accept') {
    return "<span class=AC>$ACicon</span>";
  } else {
    return "<span class=NO>$NOicon</span>";
  }
}

// Each entry in the PCMs array is $revId => array(name, ...)
function match_PCM_by_name($name, &$PCMs)
{
  $name = trim(strtolower($name));
  $len = strlen($name);
  if ($len == 0) return -1;

  // See if the given name is a prefix of any of the PCM names
  foreach ($PCMs as $revId => $member) {
    $pcmName = strtolower($member[0]);
    if (strncmp($name, $pcmName, $len)==0)
      return (int) $revId;
  }

  if ($len < 2) return -1; // a single char must match exactly

  // If not, see if the given name is at edit distance one from a PCM name
  foreach ($PCMs as $revId => $member) {
    $pcmName = strtolower($member[0]);
    $pcmName = substr($pcmName, 0, $len);
    if (levenshtein($name, $pcmName)==1)
      return (int) $revId;
  }

  return -1;
}

function numberlist($lst)
{
  $a = explode(',', $lst);
  $s = $comma = '';
  foreach ($a as $n) {
    if (is_numeric($n)) {
      $s .= $comma.$n;
      $comma = ',';
    }
  }
  return $s;
}

// like date(), but returns time in UTC instead of server time
function utcDate($fmt, $when=NULL)
{
  if (!isset($when)) $when=time(); // use current time if none is specified

  if ($fmt=='Z') return date('Z',$when);// who would do such a contrived thing?

  $when -= date('Z',$when);        // add delta between server and UTC
  $fmt = str_replace("(T)", "(\U\T\C)", $fmt);
  $ret = date($fmt, $when);

  // A hack to remove -4000 (or similar) when $fmt has 'r' or 'O' or 'P'
  $diff2gmt = date('O',$when);
  $ret = str_replace($diff2gmt, "+0000", $ret);
  $diff2gmt = date('P',$when);
  $ret = str_replace($diff2gmt, "+00:00", $ret);

  return $ret;
}

function deltaTime($delta)
{
  if ($delta <= 0) {
    return 'Time is up';
  }

  $secs = $delta % 60;
  $delta -= $secs;
  $delta /= 60;

  $mins = $delta % 60;
  $delta -= $mins;
  $delta /= 60;

  $hours = $delta % 24;
  $delta -= $hours;

  $days = $delta / 24;
  if ($days <1) $days='';
  else $days = "$days days and";

  return sprintf("Time left: $days %02d:%02d:%02d", $hours, $mins, $secs);
}

function show_deadline($when)
{
  $delta = $when-time();
  return deltaTime($delta);
}
?>