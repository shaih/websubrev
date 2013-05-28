<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
function show_legend()
{
  // icons are defined in includes/getParams.php
  global $WDicon, $NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon, $PCicon, $HVRicon;
  global $reviseIcon, $reviewIcon, $revise2Icon, $discussIcon1, $discussIcon2;
  
  $legend = <<<EndMark
<hr/>
<table><tbody>
<tr><td>Status:</td>
<td>$NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon:
    Status marks (None, [Maybe-]Reject, Discuss, [Maybe-]Accept)</td>
</tr>
<tr><td>Actions:</td>
<td>$reviewIcon,
    $revise2Icon,
    $reviseIcon:
    Submit a new/revised report about a submission</td>
</tr>
<tr><td></td>
<td>$discussIcon1,
    $discussIcon2:
    See reports and discussion boards (all read / some unread)</td>
</tr>
</tbody></table>
EndMark;

  return $legend;
}

// a debugging routine, prints the contents of an email instead of sending it
function my_display_mail($sendTo, $subject, $msg,
		      $cc=array(), $errMsg='', $attachments=NULL,
                      $from=NULL)
{
  $chrEml = defined('CHAIR_EMAIL')  ? CHAIR_EMAIL  : '';
  $sender = defined('EML_SENDER')   ? EML_SENDER   : '';
  $xMailer= defined('EML_X_MAILER') ? EML_X_MAILER : false;
  $xParam = defined('EML_EXTRA_PRM')? EML_EXTRA_PRM: false;

  echo "<pre>\n";
  if($from)
    echo "From: $from\n";
  else if (empty($chrEml))
   echo "From: ".ini_get('sendmail_from')."\n";
  else if (defined('CONF_SHORT') && defined ('CONF_YEAR'))
    echo "From: ".CONF_SHORT.CONF_YEAR." Chair <$chrEml>\n";
  else
    echo "From: $chrEml\n";
  echo "To: ".(is_array($sendTo)? implode(", ",$sendTo) : $sendTo)."\n";
  echo "Subject: {$subject}\n";
  if (!empty($cc))
    echo "Cc: ".(is_array($cc)? implode(", ",$cc) : $cc);
  echo "\n";
  echo $msg."\n";
  if (is_array($attachments) && count($attachments)>0)
    foreach($attachments as $a) {
      echo "Attachment: ".$a[0].$a[1],"\n";
    }
  echo "</pre>\n";
  return true;
}

// The $attachments parameter is an array of (path,filename) pairs,
// $msg is assumed to be a text-message (in utf-8 encoding)
function my_send_mail($sendTo, $subject, $msg,
		      $cc=array(), $errMsg='', $attachments=NULL,
                      $from=NULL)
{
  // return my_display_mail($sendTo,$subject,$msg,$cc,$errMsg,$attachments,$from);
  // return true;

  // Support sending to multiple e-mails, all but the first address
  // will be included in the CC field
  if(is_array($sendTo)) {
    $tmp = array_shift($sendTo); // remove first adderss and store it in $tmp
    if(!is_array($cc)) {         // make sure that $cc is an array
      $cc = array($cc);
    }
    $cc = array_merge($cc, $sendTo); // add remaining addresses to $cc
    $sendTo = $tmp;
  }

  $php_errormsg = ''; // avoid notices in case it isn't defined 

  // handle CRLF oddities
  if (defined('EML_CRLF') && EML_CRLF=="\n") $emlCRLF = "\n";
  else                                       $emlCRLF = "\r\n";

  $chrEml = defined('CHAIR_EMAIL')  ? CHAIR_EMAIL  : '';
  $sender = defined('EML_SENDER')   ? EML_SENDER   : '';
  $xMailer= defined('EML_X_MAILER') ? EML_X_MAILER : false;
  $xParam = defined('EML_EXTRA_PRM')? EML_EXTRA_PRM: false;
  
  if($from)
    $hdr = "From: ".$from;
  else if (empty($chrEml))
   $hdr = "From: ".ini_get('sendmail_from');
  else if (defined('CONF_SHORT') && defined ('CONF_YEAR'))
    $hdr = "From: ".CONF_SHORT.CONF_YEAR." Chair <$chrEml>";
  else
    $hdr = "From: $chrEml";
  
  if (!empty($cc)) {
    if(!is_array($cc)) {
      $cc = array($cc);
    }
    $hdr .= $emlCRLF."Cc: ".implode(", ", $cc);
  }
  
  if (!empty($sender)) $hdr .= $emlCRLF."Sender: ".EML_SENDER;
  if ($xMailer)        $hdr .= $emlCRLF."X-Mailer: PHP/".phpversion();

  // guess the type of message
  $sniplet = strtolower(substr($msg,0,14));
  if ($sniplet=='<!doctype html' || substr($sniplet,0,6)=='<html>')
    $type = "Content-type: text/html; charset=utf-8";
  else $type = "Content-type: text/plain; charset=utf-8";

  // If there are attachments, prepare a MIME email
  $mime='';
  if (is_array($attachments) && count($attachments)>0) {
    $boundary = '===WebSubRev_email_boundary_dKp9hcAr6===';
    foreach($attachments as $a) {
      $content = file_get_contents($a[0].$a[1]);
      if (!$content) continue;
      $mime.= '--'.$boundary.$emlCRLF;
      $mime.= "Content-Type: application/octet-stream; name=\"{$a[1]}\""
	.$emlCRLF;
      $mime.= "Content-Transfer-Encoding: base64".$emlCRLF;
      $mime.= "Content-Disposition: attachment; filename=\"{$a[1]}\"".$emlCRLF;
      $mime.= $emlCRLF. chunk_split(base64_encode($content)).$emlCRLF.$emlCRLF;
    }
  }
  if (!empty($mime)) {
    $msg = "This is a multi-part message in MIME format.".$emlCRLF
	. "--{$boundary}".$emlCRLF
	. $type.$emlCRLF
	. "Content-Transfer-Encoding: 7bit".$emlCRLF.$emlCRLF
	. $msg.$emlCRLF.$emlCRLF
	. $mime
	. "--{$boundary}--".$emlCRLF;
    $hdr .= $emlCRLF."MIME-Version: 1.0"
	. $emlCRLF."Content-Type: multipart/mixed; boundary=\"$boundary\"";
  }
  else // no attachments, just a single part
    $hdr .= $emlCRLF.$type;
 
  if ($xParam && !empty($chrEml) && !ini_get('safe_mode'))
    $success = mail($sendTo, $subject, $msg, $hdr, "-f $chrEml");
  else
    $success = mail($sendTo, $subject, $msg, $hdr);

  if (!$success) {
    if (empty($errMsg))
      $errMsg = "Cannot send email. {$php_errormsg}\n";
    else
      $errMsg = "Cannot send email, $errMsg. {$php_errormsg}\n";
    error_log(date('Y.m.d-H:i:s ').$errMsg, 3, LOG_FILE);
  }
  return $success;
}

// If the user is found in the database, returns the user details
// as array(id, name, email). Otherwise returns false.
function auth_PC_member($eml, $pwd, $id=NULL, $pwdInClear=false)
{
  // Test the username and password parameters
  if (!isset($eml) || !isset($pwd))
    return false;

  $cnnct = db_connect();

  // Create a digest of the password and sanitize the email address
  $eml = strtolower(trim($eml));

  // exit("$eml:$pwd => ".sha1(CONF_SALT . $eml . $pwd));
  if (!$pwdInClear) $pwd = sha1(CONF_SALT . $eml . $pwd);
  $eml = my_addslashes($eml, $cnnct);

  // Formulate the SQL find the user
  $qry = "SELECT revId, name, email, canDiscuss, threaded, flags FROM committee WHERE";
  if (isset($id)) {
    //Could be array or int
    if (is_numeric($id)) 
      $qry .= " revId=$id AND";
    elseif (is_array($id) && count($id)>=1) {
      $ids = $comma = '';
      foreach ($id as $n) if ($n>0) {
	$ids .= "{$comma}{$n}";
	$comma = ',';
      }
      $qry .= " revId IN ($ids) AND";
    }
  }
  $qry .= " email = '{$eml}' AND revPwd = '{$pwd}'";

  // Go to the database to look for the user
  $res = db_query($qry, $cnnct, "Cannot authenticate against database: ");

  // exactly one row? if not then we failed
  if (mysql_num_rows($res) != 1)
    return false;

  // return the user details
  return mysql_fetch_array($res);
}

function auth_author($subId, $password) {
  $cnnct = db_connect();
  $qry = "SELECT subId, subPwd, title, authors, abstract, rebuttal FROM submissions WHERE subPwd='".my_addslashes($password)."'";
  $res = db_query($qry, $cnnct);
  
  if(mysql_num_rows($res) < 1)
    return false;

  $row = mysql_fetch_assoc($res);
  return $row;
}

function active_rebuttal() {
  if (defined('REBUTTAL_FLAG') && REBUTTAL_FLAG) {
    return true;
  } 
  return false;
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
  if (!($cnnct=mysql_connect($host, $usr, $pwd))) {
    error_log(date('Y.m.d-H:i:s ').mysql_error()."\n", 3, LOG_FILE);
    exit("<h1>Cannot connect to MySQL server</h1>\n" .
	 "mysql_connect($host, $usr, $pwd)<br />". mysql_error());
  }
  if (isset($db) && !mysql_select_db($db, $cnnct)) {
    error_log(date('Y.m.d-H:i:s ').mysql_error()."\n", 3, LOG_FILE);
    exit("<h1>Cannot select database $db</h1>\n" . mysql_error());
  }
  mysql_query("SET NAMES utf8", $cnnct); // explicitly tell MySQL to speak utf8

  return $cnnct;
}

function db_query($qry, $cnnct, $desc='')
{
  $php_errormsg = ''; // avoid notices in case it isn't defined 
  $res=mysql_query($qry, $cnnct);
  if ($res===false) {
    error_log(date('Y.m.d-H:i:s ')
      .mysql_errno().'-'.mysql_error()." $php_errormsg\n", 3, LOG_FILE);
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
				  $fileFormat = NULL, $fileSize=NULL)
{
  // During review process, don't send email to authors, only to chair
  if (defined('REVIEW_PERIOD') && REVIEW_PERIOD==true) {
    $sndto = chair_emails();
    $cc = array();
  }
  else 
    $cc = chair_emails();
  
  if ($status < 0)  // if an error occured, send also to the administrator
    array_push($cc, ADMIN_EMAIL);
  
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
  if ($sid != 0)     { $msg .= "Submission-ID:  \t{$sid}\n"; }
  if (!empty($pwd))  { $msg .= "Submission password:\t{$pwd}\n\n"; }
  if ($sid != 0 && !empty($pwd)) {
    $prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
    $revise = (PERIOD>=PERIOD_CAMERA) ? 'cameraready.php' : 'revise.php';
    $msg .= "You can still revise this submission by going to\n\n  ";
    $msg .= "$prot://".BASE_URL."submit/{$revise}?subId=$sid&subPwd=$pwd\n\n";

    if (isset($fileSize) && $fileSize>0) {
      $msg .= "Make sure that you uploaded the right file to the server. ";
      $msg .= "\nThe file that we received contains $fileSize bytes. ";
      $msg.="You can download\nyour file back by going to\n\n  ";
      $msg.="$prot://".BASE_URL."submit/download.php?subId=$sid&subPwd=$pwd\n\n";
    }
  }
  if (!empty($ttl))  { $msg .= "Title:    \t{$ttl}\n"; }
  if (!empty($athr)) { $msg .= "Authors:  \t{$athr}\n"; }
  if (!empty($cntct)){ $msg .= "Contact:  \t{$cntct}\n"; }
  if (!empty($cat))  { $msg .= "Category: \t{$cat}\n"; }
  if (!empty($kwrd)) { $msg .= "Key words:\t{$kwrd}\n"; }
  if (!empty($cmnt)) { $msg .= "Comments: \t{$cmnt}\n"; }
  if (!empty($abs))  { $msg .= "\nAbstract:\n" .wordwrap($abs, 78) ."\n"; }


  $chairSbjct = $sbjct;
  if (!empty($cmnt))
    $chairSbjct .= " (comments to chair included)";

  //Send to author if 
  if (!(defined('REVIEW_PERIOD') && REVIEW_PERIOD==true)) {
    my_send_mail($sndto, $sbjct, $msg, array(), "receipt to $sndto");
    my_send_mail($cc, $chairSbjct, $msg, array(), "receipt to $sndto");
  }
  else {
    my_send_mail($sndto, $chairSbjct, $msg, array(), "receipt to $sndto");
  }
}

// This function could be expanded. It return the extension for this file
// type if the type is supported, and otherwise returns 'ext.unsupported'
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

// Find the extension part of a filename
function file_extension($filename)
{
  $exts = explode('.', $filename);
  $n = count($exts);
  if ($n <= 1) return ''; // no extension found

  $ext = $exts[$n-1];
  if ($n>2 && ($ext=='gz' || $ext=='Z')) // look for the preceeding extension
    $ext = $exts[$n-2] . ".$ext";

  return $ext;
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
      $whoseCalling = substr($whoseCalling, 0, $pos) . $anchor;
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
    if ($cr[0]=='Confidence') return false; // No 'Confidence' criterion

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

function show_status($status, $scratch = false)
{
  global $WDicon, $NOicon, $REicon, $MRicon, $DIicon, $MAicon, $ACicon;
  
  $scratch_class = "";
  
  if($scratch) {
    $scratch_class = " scratch";
  }
  
  if ($status == 'Withdrawn') {
    return "<span class='WD $scratch_class'>$WDicon</span>";
  } else if ($status == 'Reject') {
    return "<span class='RE $scratch_class'>$REicon</span>";
  } else if ($status == 'Perhaps Reject') {
    return "<span class='MR $scratch_class'>$MRicon</span>";
  } else if ($status == 'Needs Discussion') {
    return "<span class='DI $scratch_class'>$DIicon</span>";
  } else if ($status == 'Maybe Accept') {
    return "<span class='MA $scratch_class'>$MAicon</span>";
  } else if ($status == 'Accept') {
    return "<span class='AC $scratch_class'>$ACicon</span>";
  } else {
    return "<span class='NO $scratch_class'>$NOicon</span>";
  }
}

function has_pc_author($authors, $subId)
{
  $cnnct = db_connect();	
  $authors = my_addslashes($authors);
  $res = db_query("SELECT * FROM committee c JOIN submissions s on '$authors' LIKE CONCAT('%',c.name,'%') WHERE s.subId = '$subId';", $cnnct);	
  if (mysql_num_rows($res)> 0) {
  	return true;
  }
  return false;
}

function has_reviewed_paper($revId, $subId)
{
  $cnnct = db_connect();
  $res = db_query("SELECT score FROM reports WHERE subId=$subId AND revId=$revId", $cnnct);
  $reviewed = (mysql_num_rows($res) > 0);

  $res = db_query("SELECT assign FROM assignments WHERE subId=$subId AND revId=$revId", $cnnct);
  $row = mysql_fetch_row($res);
  $assigned = ($row[0] > 0);

  return (!$assigned || $reviewed);
  /*
  $res = db_query("SELECT flags from submissions s where s.subId = '$subId'", $cnnct);
  $sb = mysql_fetch_assoc($res);
  return ($sb['flags'] && FLAG_IS_GROUP);
  */
}

function has_reviewed_anything($revId)
{
  $cnnct = db_connect();
  $res = db_query("SELECT score FROM reports WHERE revId=$revId", $cnnct);
  return (mysql_num_rows($res) > 0);
}

function has_discussed($revId, $subId)
{
  $cnnct = db_connect();
  $res = db_query("SELECT postId FROM posts WHERE revId=$revId AND subId=$subId", $cnnct);
  return (mysql_num_rows($res) > 0);
}

function high_variance_reviews($subId, $thresh = 2.0)
{
  $cnnct = db_connect();
  $res = db_query("SELECT VAR_POP(score) avg FROM reports WHERE subId=$subId",$cnnct);
  return (mysql_result($res, 0) > $thresh);
}

function has_group_conflict($revId, $title) {
	$title = htmlspecialchars($title);
	$tArr = explode(',', $title);
	$cnnct = db_connect();
	foreach($tArr as $sId) {
		$sId = my_addslashes($sId, $cnnct);
		$qry1 = "SELECT a.assign assign FROM assignments a WHERE a.revId='$revId' ";
		$qry1 .= "AND a.subId='$sId'";
		$row = mysql_fetch_assoc(db_query($qry1, $cnnct));
		if($row['assign'] < 0) {
			return true;
		}
	}
	return false;
}

function get_sub_links($subs) {
	$subs = explode(',',$subs); 
	$ret = '';
	foreach($subs as $s) {
		$ret.="<a href='discuss.php?subId=".$s."'>".$s."</a>, ";
	}
	$ret = substr($ret, 0, -2);
	return $ret;
}

// Each entry in the PCMs array is $revId => array(name, ...)
function match_PCM_by_name($name, $PCMs)
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

  if ($len < 2) return -1; // a single char must be an exact prefix

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
  if (!isset($when))             // use current time if none is specified
    $when = time() + TIME_SHIFT;

  if ($fmt=='Z') return date('Z',$when);// who would do such a contrived thing?

  $when -= date('Z',$when);      // add delta between server and UTC
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
  $now = time() + TIME_SHIFT;
  $delta = $when - $now;
  return deltaTime($delta);
}

// Common solution to the problem of readfile reading entire file into memory
function readfile_chunked($filename)
{
  $chunksize = 1*(1024*1024); // how many bytes per chunk
  $buffer = '';
  $cnt =0;
  $handle = fopen($filename, 'rb');
  if ($handle === false) {
    return false;
  }
  while (!feof($handle)) {
    $buffer = fread($handle, $chunksize);
    echo $buffer;
    ob_flush();
    flush();
    $cnt += strlen($buffer);
  }
  $status = fclose($handle);
  if ($status) {
    return $cnt; // return num. bytes delivered like readfile() does.
  }
  return $status;
}

//Chair utilities
function is_chair($revId) {
  global $CHAIR_IDS;
  return in_array($revId, $CHAIR_IDS);
}

function chair_emails() {
  global $CHAIR_EMAILS;
  return $CHAIR_EMAILS;
}

function chair_ids() {
  global $CHAIR_IDS;
  return $CHAIR_IDS;
}
