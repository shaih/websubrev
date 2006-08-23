<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
chdir('..'); // This script is placed in a sub-directory

if (file_exists('./includes/confConstants.php')) { // Already customized
  exit("<h1>This installation is already cusomized</h1>");
}

// Some things in confUtils need the BASE_URL constant
$baseURL = $_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'];   // this file
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/'));   // the directory
$baseURL = substr($baseURL, 0, strrpos($baseURL, '/')+1); // parent directory
define('BASE_URL', $baseURL);

require './includes/confUtils.php'; 

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) 
  $_POST  = array_map('stripslashes_deep', $_POST);

// Read all the fields, stripping spurious white-spaces
$longName    = isset($_POST['longName'])   ? trim($_POST['longName'])   : NULL;
$shortName   = isset($_POST['shortName'])  ? trim($_POST['shortName'])  : NULL;
$confYear    = isset($_POST['confYear'])   ? trim($_POST['confYear'])   : NULL;
$confURL     = trim($_POST['confURL']);

$webServer   = isset($_POST['webServer'])  ? trim($_POST['webServer'])  : NULL;
$localSQL  = (trim($_POST['localMySQL'])=="yes");
$sqlHost   = empty($_POST['MySQLhost'])    ?
                               'localhost' : trim($_POST['MySQLhost']);
$sqlRoot   = isset($_POST['rootNm'])       ? trim($_POST['rootNm'])     : NULL;
$sqlRtPw   = isset($_POST['rootPwd'])      ? trim($_POST['rootPwd'])    : NULL;
$sqlDB     = isset($_POST['confDB'])       ? trim($_POST['confDB'])     : NULL;
$sqlUsr    = isset($_POST['user'])         ? trim($_POST['user'])       : NULL;
$sqlPwd    = isset($_POST['pwd'])          ? trim($_POST['pwd'])        : NULL;

// parse_email returns either an array (name, email) or NULL
$admin       = isset($_POST['admin']) ? parse_email($_POST['admin'])    : NULL;
$chair       = isset($_POST['chair']) ? parse_email($_POST['chair'])    : NULL;
$committee   = isset($_POST['committee'])  ?
                                    explode(';', $_POST['committee'])   : NULL;

$emlCrlf   = isset($_POST['emlCrlf'])      ? trim($_POST['emlCrlf']): "\\r\\n";
$emlExtraPrm = isset($_POST['emlExtraPrm'])? trim($_POST['emlExtraPrm']): NULL;
if (empty($emlExtraPrm)) $emlExtraPrm = '-f ' . $chair[1];
else if (strtolower($emlExtraPrm)=="none") $emlExtraPrm = '';

$subDeadline = isset($_POST['cameraDeadline']) ? trim($_POST['subDeadline']) : NULL;
$cameraDeadline= isset($_POST['cameraDeadline'])? trim($_POST['cameraDeadline']) : NULL;

$categories  = isset($_POST['categories']) ?
                    explode(';', $_POST['categories']) : NULL;

$f1dsc     = isset($_POST['format1desc'])  ? trim($_POST['format1desc']): NULL;
$f1ext     = isset($_POST['format1ext'])   ? trim($_POST['format1ext']) : NULL;
$f1mime    = isset($_POST['format1mime'])  ? trim($_POST['format1mime']): NULL;

$f2dsc     = isset($_POST['format2desc'])  ? trim($_POST['format2desc']): NULL;
$f2ext     = isset($_POST['format2ext'])   ? trim($_POST['format2ext']) : NULL;
$f2mime    = isset($_POST['format2mime'])  ? trim($_POST['format2mime']): NULL;

$f3dsc     = isset($_POST['format3desc'])  ? trim($_POST['format3desc']): NULL;
$f3ext     = isset($_POST['format3ext'])   ? trim($_POST['format3ext']) : NULL;
$f3mime    = isset($_POST['format3mime'])  ? trim($_POST['format3mime']): NULL;


$anonymous = isset($_POST['anonymous']) ? 'Anonymous submissions' 
                                    : 'Non-anonymous (=nonymous?) submissions';
$revPrefs  = isset($_POST['revPrefs']) ?
  'PC members can specify reviewing preferences' :
  'Reviewing preferences and automatic assignments are disabled';

$maxGrade  = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 6;
if (($maxGrade < 2) || ($maxGrade > 9)) { $maxGrade =6; }

$crList = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;

// Check that the required fileds are specified

if (empty($longName)      || empty($shortName)     || empty($confYear)
    || empty($webServer)  || empty($admin)         || empty($chair)) {
  print "<h1>Mandatory fields are missing</h1>\n";
  print "You must specify the conference short and long names and year, web-server name, administrator email and program chair email\n";
  exit();
}

if (!preg_match('/^[0-9]{2}$/', $confYear)) {
  print "<h1>Wrong format for the conference year</h1>\n";
  print "Year must consists of exatly two digits.\n";
  exit();
}
else $confYear += 2000;

if ((empty($sqlRoot) || empty($sqlRtPw))
    && (empty($sqlDB) || empty($sqlUsr) || empty($sqlPwd))) {
  print "<h1>Cannot create/access MySQL database</h1>\n";
  print "To automatically generate MySQL database, you must specify the
         MySQL root username and password.<br />\n";
  print "Otherwise, you must manually create the database and specify the
         database name, and also specify MySQL usename and password of a
         user that can write into that database.\n";
  exit();
}

// Try to parse the deadlines as some recognized date format
// The error code from strtotime is -1 for PHP 4 and FALSE for PHP 5
$tsb = empty($subDeadline) ? false : strtotime($subDeadline);
if ($tsb!==false && $tsb!=-1) {
  $subDeadline = $tsb; // store as a number (unix time)
  $subDeadlineHtml = utcDate('r (T)', $tsb);
}
else die("<h1>Unregocnized time format for submission deadline</h1>");

$tcr = empty($cameraDeadline) ? false : strtotime($cameraDeadline) ;
if ($tcr!==false && $tcr!=-1) {
  $cameraDeadline = $tcr; // store as a number (unix time)
  $cameraDeadlineHtml = utcDate('r (T)', $tcr);
}
else die("<h1>Unregocnized time format for camera-ready deadline</h1>");

// Create an array of committee members
if (isset($committee)) {
  $list = $committee;
  $committee = array();
  foreach ($list as $m) { $committee[] = parse_email($m); }
}

// Create an array of criteria
if (isset($crList)) {
  $criteria = array();
  foreach ($crList as $cr) {
    if ($c = parse_criterion($cr)) { $criteria[] = $c; }
  }
}

// Create an array of all the supported formats
$cFrmts = array();
if (isset($_POST['formatPDF']))
   $cFrmts[] = array('PDF', 'pdf', 'application/pdf');
if (isset($_POST['formatPS']))
  $cFrmts[] = array('Postscript', 'ps', 'application/postscript');
if (isset($_POST['formatTEX'])) {
  $cFrmts[] = array('TeX/LaTeX', 'tex', 'application/x-tex');
  $cFrmts[] = array('LaTeX', 'latex', 'application/x-latex');
}
if (isset($_POST['formatHTML'])) {
  $cFrmts[] = array('HTML', 'html', 'text/html');
  $cFrmts[] = array('HTML', 'htm', 'text/html');
}
if (isset($_POST['formatZIP'])) 
  $cFrmts[] = array('Zip Archive', 'zip', 'application/zip');
if (isset($_POST['formatMSword']))
  $cFrmts[] = array('MS-Word', 'doc', 'application/msword');
if (isset($_POST['formatPPT']))
  $cFrmts[] = array('PowerPoint', 'ppt', 'application/powerpoint');
if (isset($_POST['formatODT'])) 
  $cFrmts[] = array('OpenOffice Document', 'odt', 
		       'application/vnd.oasis.opendocument.text');
if (isset($_POST['formatODP'])) 
  $cFrmts[] = array('OpenOffice Presentation', 'odp', 
		       'application/vnd.oasis.opendocument.presentation');

if (!empty($f1ext) || !empty($f1mime)) {
  if ($f1ext[0] == '.') $f1ext = subsrt($f1ext, 1); // Remove leading '.'
  $cFrmts[] = array($f1dsc, $f1ext, $f1mime);
}
if (!empty($f2ext) || !empty($f2mime)) {
  if ($f2ext[0] == '.') $f2ext = subsrt($f2ext, 1); // Remove leading '.'
  $cFrmts[] = array($f2dsc, $f2ext, $f2mime);
}
if (!empty($f3ext) || !empty($f3mime)) {
  if ($f3ext[0] == '.') $f3ext = subsrt($f3ext, 1); // Remove leading '.'
  $cFrmts[] = array($f3dsc, $f3ext, $f3mime);
}

/* Before actually doing anything, ask the user to confirm again */
$longNameHtml  = htmlspecialchars($longName); 
$shortNameHtml = htmlspecialchars($shortName); 
$confYearHtml  = htmlspecialchars($confYear);  
$confURLHtml   = htmlspecialchars($confURL);   
$webServerHtml = htmlspecialchars($webServer);
$sqlHostHtml   = htmlspecialchars($sqlHost);   
$sqlDBHtml     = htmlspecialchars($sqlDB); 	  
$sqlUsrHtml    = htmlspecialchars($sqlUsr); 	  
$sqlPwdHtml    = htmlspecialchars($sqlPwd); 	  
$sqlRootHtml   = htmlspecialchars($sqlRoot); 
$sqlRtPwHtml   = htmlspecialchars($sqlRtPw);  
$adminEmlHtml  = htmlspecialchars($admin[1]); 
$chairNmHtml   = htmlspecialchars($chair[0]); 
$chairEmlHtml  = htmlspecialchars($chair[1]); 
$emlCrlfHtml   = htmlspecialchars($emlCrlf);
$emlExtraPrmHtml= empty($emlExtraPrm) ? "none" :
                                        htmlspecialchars($emlExtraPrm);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
  <title>Confirm Customization</title>
</head>
<body>
<h1 style="text-align: center;">Confirm Customization</h1>

Please go carefully over these details and make sure that they are all
correct. If you find mistakes, use your browsers Back button to return
to the customization form and correct them. If all the detais are correct,
hit the Confirm button below to customize this installation. 

<h2>The Conference:</h2>     
<table cellspacing=6>
<tbody>
  <tr><td style="text-align: right;">Conference name:</td>
    <td><b>$longNameHtml</b></td>
  </tr>
  <tr><td style="text-align: right;">Conference&nbsp;short&nbsp;name:</td>
    <td><b>$shortNameHtml</b> &nbsp;$confYearHtml</td>
  </tr>
  <tr><td style="text-align: right;">Conference URL:</td>
    <td><tt>$confURLHtml</tt></td>
  </tr>
</tbody></table>

<h2>The Site:</h2>     
<table cellspacing=6>
<tbody>
<tr><td style="text-align: right;">Web server:</td>
    <td><tt>$webServerHtml</tt></td>
</tr>
<tr><td style="text-align: right;">MySQL server:</td>
    <td>
EndMark;
if ($localSQL) print "Runs on the same host as the web-server\n";
else           print "Runs on the host <tt> $sqlHostHtml</tt>\n";
print "    <td>\n  </tr>\n";

if (!empty($sqlDB) && !empty($sqlUsr) && !empty($sqlPwd)) {
  print <<<EndMark

<tr><td style="text-align: right;">MySQL Database:</td>
    <td><i>$sqlDBHtml</i></td>
  </tr>
  <tr><td style="text-align: right;">MySQL&nbsp;User:</td>
     <td>Name: <i>$sqlUsrHtml</i>, &nbsp; password: <i>$sqlPwdHtml</i></td>
  </tr>
EndMark;
}
else if (!empty($sqlRoot) && !empty($sqlRtPw)) {
  print <<<EndMark

  <tr><td style="text-align: right;">MySQL&nbsp;Administrator:</td>
     <td>Name: <tt>$sqlRootHtml</tt>, &nbsp; password: <tt>$sqlRtPwHtml</tt></td>
  </tr>
EndMark;
}

print <<<EndMark

  <tr><td style="text-align: right;">Administrator Email:</td>
    <td><tt>$adminEmlHtml</tt></td>
  </tr>
  <tr><td style="text-align: right;">Email settings:</td>
    <td>Separate lines with <tt>"$emlCrlfHtml"</tt></td>
  </tr>
  <tr><td></td>
    <td>Extra sendmail parameters: <tt>$emlExtraPrmHtml</tt></td>
  </tr>
</tbody></table>

<h2>Program Committee:</h2>     
<table cellspacing=6>
<tbody>
  <tr><th></th> <th>Name</th> <th>Email</th></tr>
  <tr><td style="text-align: right;">Program Chair:</td>
    <td><tt>$chairNmHtml</tt></td> <td><tt>$chairEmlHtml</tt></td>
  </tr>

EndMark;

if (is_array($committee) && count($committee)>0) {
  $i = 0;
  foreach ($committee as $m) {
    $nm = htmlspecialchars(trim($m[0])); $eml = htmlspecialchars(trim($m[1]));
    if (!empty($eml)) {
      if ($i>0) print "  <tr><td></td>\n";
      else      print "  <tr><td style=\"text-align: right;\">Program Committee:</td>\n";

      print "    <td><tt>$nm</tt></td> <td><tt>$eml</tt></td>\n  </tr>\n";
      $i++;
    }
  }
  $nCmmtee = $i; // How many non-empty email addresses
}
else $nCmmtee = 0;

print <<<EndMark

</tbody></table>

<h2>Submissions:</h2>     
<table cellspacing=6>
<tbody>
  <tr><td style="text-align: right;">Submission Deadline:</td>
    <td colspan="3"><b>$subDeadlineHtml</b>
    </td>
  </tr>
  <tr><td style="text-align: right;">Camera-ready Deadline:</td>
    <td colspan="3"><b>$cameraDeadlineHtml</b>
    </td>
  </tr>

EndMark;

if (is_array($categories) && count($categories)>0) {
  $i = 0;
  foreach ($categories as $c) {
    $c = htmlspecialchars(trim($c));
    if (!empty($c)) {
      if ($i>0) print "  <tr><td></td>\n";
      else      print "  <tr><td style=\"text-align: right;\">Categories:</td>\n";
      print "    <td colspan=\"3\">$c</td>\n  </tr>\n";
      $i++;
    }
  }
  $nCats = $i;  // How many non-empty categories
}
else $nCats = 0;
if ($nCats == 0) {
  print '  <tr><td style="text-align: right;">Categories:</td>
    <td colspan="3">' . "<b>Not using categories</b></td></tr>\n";
}

print '  <tr><td style="text-align: right;">Require affiliations:</td>'
     . ' <td colspan="3"><b>' . (isset($_POST['affiliations']) ? 'Yes' : 'No')
     . "</b></td>\n  </tr>\n";

if (is_array($cFrmts) && count($cFrmts)>0) {
  print "  <tr><th style=\"text-align:right;\">Supported Formats:</th>\n";
  print "      <th>Name</th> <th>Extension</th> <th>MIME-type</th>\n";
  print "  </tr>\n";
  foreach($cFrmts as $f) {
    print "  <tr><td></td><td>".htmlspecialchars($f[0])."</td> ";
    print " <td>.$f[1]</td> <td>".htmlspecialchars($f[2])."</td></tr>\n";
  }
}
else {
  print "  <tr><td style=\"text-align:right;\">Supported Formats:</td>\n";
  print "      <td colspan=\"3\"><b>No Formats Recorded</b></td></tr>\n";
}

print <<<EndMark

</tbody></table>

<h2>Reviews:</h2>
<table cellspacing=6>
<tbody>
  <tr><td colspan="2">$anonymous</td></tr>
  <tr><td colspan="2">$revPrefs</td></tr>
  <tr><td style="text-align: right;">Overall Grades:</td>
      <td><b>From 1 to $maxGrade</b></td>
  </tr>
  <tr><td style="text-align: right;">Confidence&nbsp;Level:</td>
      <td><b>From 1 to 3</b></td>
  </tr>

EndMark;

if (is_array($criteria) && count($criteria)>0) {
  $i = 0;
  foreach ($criteria as $m) {
    $nm = trim($m[0]); $maxval = $m[1];
    if (!empty($nm)) {
      print "  <tr><td style=\"text-align: right;\">$nm:</td>\n";
      print "    <td><b>From 1 to $maxval</b></td>\n  </tr>\n";
      $i++;
    }
  }
  $nCrits = $i;  // How many additional evaluation criteria
}
else $nCrits = 0;

print <<<EndMark

</tbody></table>
<br />
<form action="act-customize.php" enctype="multipart/form-data" method="post">

<input name="longName"  type="hidden" value="$longName">
<input name="shortName" type="hidden" value="$shortName">
<input name="confYear"  type="hidden" value="$confYear">
<input name="confURL"    type="hidden" value="$confURL">
<input name="subDeadline" type="hidden" value="$subDeadline">
<input name="cameraDeadline" type="hidden" value="$cameraDeadline">
<input name="adminEmail" type="hidden" value="$admin[1]">
<input name="chairName"  type="hidden" value="$chair[0]">
<input name="chairEmail" type="hidden" value="$chair[1]">
<input name="emlCrlf" type="hidden" value="$emlCrlf">
<input name="emlExtraPrm" type="hidden" value="$emlExtraPrm">
<input name="webServer" type="hidden" value="$webServer">
EndMark;

if ($localSQL)
     print '<input name="localMySQL" type="hidden" value="yes">'."\n";
else print '<input name="localMySQL" type="hidden" value="no">'."\n";
if (isset($sqlHost))
  print '<input name="MySQLhost" type="hidden" value="'. $sqlHost. "\">\n";
if (isset($sqlRoot))
  print '<input name="rootNm" type="hidden" value="'. $sqlRoot. "\">\n";
if (isset($sqlRtPw))
  print '<input name="rootPwd" type="hidden" value="'. $sqlRtPw . "\">\n";
if (isset($sqlDB))
  print '<input name="confDB" type="hidden" value="'. $sqlDB. "\">\n";
if (isset($sqlUsr))
  print '<input name="user" type="hidden" value="'. $sqlUsr. "\">\n";
if (isset($sqlPwd))
  print '<input name="pwd" type="hidden" value="'. $sqlPwd. "\">\n";

print "<input name=\"nCmmtee\" type=\"hidden\" value=\"$nCmmtee\">\n";
if ($nCmmtee > 0) {
  $i = 0;
  foreach ($committee as $m) {
    $nm = trim($m[0]); $eml = trim($m[1]);
    if (!empty($eml)) {
      print <<<EndMark
  <input name="member_{$i}_name" type="hidden" value="$nm">
  <input name="member_{$i}_email" type="hidden" value="$eml">

EndMark;
      $i++;
    }
  }
  print "\n";
}

print "<input name=\"nCats\" type=\"hidden\" value=\"$nCats\">\n";
if ($nCats > 0) {
  $i=0;
  foreach ($categories as $c) {
    $c = trim($c);
    if (!empty($c)) {
      print "<input name=\"category_$i\" type=\"hidden\" value=\"$c\">\n";
      $i++;
    }
  }
  print "\n";
}

if (isset($_POST['affiliations']))
  print '<input name="affiliations" type="hidden" value="on">' . "\n";

if (is_array($cFrmts) && count($cFrmts) > 0) {
  print '<input name="nFrmts" type="hidden" value="'. count($cFrmts). "\">\n";
  for ($i=0; $i<count($cFrmts); $i++) {
    print '<input name="frmt_' . $i . '_desc" '
      . 'type="hidden" value="' . $cFrmts[$i][0] . "\">\n";
    print '<input name="frmt_' . $i . '_ext" '
      . 'type="hidden" value="' . $cFrmts[$i][1] . "\">\n";
    print '<input name="frmt_' . $i . '_mime" '
      . 'type="hidden" value="' . $cFrmts[$i][2] . "\">\n";
  }
  print "\n";
}
else { print '<input name="nFrmts" type="hidden" value="0">' . "\n\n"; }

if (isset($_POST['anonymous'])) {
  print "<input name=\"anonymous\" type=\"hidden\" value=\"on\">\n";
}

if (isset($_POST['revPrefs'])) {
  print "<input name=\"revPrefs\" type=\"hidden\" value=\"on\">\n";
}

print "<input name=\"maxGrade\" type=\"hidden\" value=\"$maxGrade\">\n";
print "<input name=\"nCrits\" type=\"hidden\" value=\"$nCrits\">\n";
if ($nCrits > 0) {
  $i=0;
  foreach ($criteria as $c) {
    $nm = trim($c[0]); $maxval = $c[1];
    if (!empty($nm)) {
      print <<<EndMark
  <input name="criterion_{$i}_name" type="hidden" value="$nm">
  <input name="criterion_{$i}_max" type="hidden" value="$maxval">

EndMark;
      $i++;
    }
  }
  print "\n";
}

print <<<EndMark
<input value="Confirm: use these values to customize the installation"
 type="submit">
</form>
</body>
</html>

EndMark;
?>
