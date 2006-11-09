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

if (isset($_GET['username']) && isset($_GET['password'])) {
  $urlParams = '?username='.$_GET['username'].'&password='.$_GET['password'];
}
else { $urlParams = ''; }

// Read all the fields, stripping spurious white-spaces
$longName    = isset($_POST['longName'])  ? trim($_POST['longName'])  : NULL;
$shortName   = isset($_POST['shortName']) ? trim($_POST['shortName']) : NULL;
$confYear    = isset($_POST['confYear'])  ? trim($_POST['confYear'])  : NULL;
$confURL     = trim($_POST['confURL']);

$subDeadline = isset($_POST['cameraDeadline']) ? trim($_POST['subDeadline']) : NULL;
$cameraDeadline= isset($_POST['cameraDeadline']) ? trim($_POST['cameraDeadline']) : NULL;
$categories  = isset($_POST['categories']) ? explode(';', $_POST['categories']) : NULL;

$f1dsc     = isset($_POST['format1desc']) ? trim($_POST['format1desc']): NULL;
$f1ext     = isset($_POST['format1ext'])  ? trim($_POST['format1ext']) : NULL;
$f1mime    = isset($_POST['format1mime']) ? trim($_POST['format1mime']): NULL;

$f2dsc     = isset($_POST['format2desc']) ? trim($_POST['format2desc']): NULL;
$f2ext     = isset($_POST['format2ext'])  ? trim($_POST['format2ext']) : NULL;
$f2mime    = isset($_POST['format2mime']) ? trim($_POST['format2mime']): NULL;

$f3dsc     = isset($_POST['format3desc']) ? trim($_POST['format3desc']): NULL;
$f3ext     = isset($_POST['format3ext'])  ? trim($_POST['format3ext']) : NULL;
$f3mime    = isset($_POST['format3mime']) ? trim($_POST['format3mime']): NULL;

$chair       = isset($_POST['chair']) ? parse_email($_POST['chair'])  : false;
$committee   = isset($_POST['committee'])  ?
                                    explode(';', $_POST['committee'])   : NULL;

$anonymous = isset($_POST['anonymous']) ?
  'Anonymous submissions' :
  'Onymous (=non-anonymous) submissions';
$revPrefs  = isset($_POST['revPrefs']) ?
  'PC members can specify reviewing preferences' :
  'Reviewing preferences and automatic assignments are disabled';

$maxGrade  = isset($_POST['maxGrade']) ? (int) trim($_POST['maxGrade']) : 6;
if (($maxGrade < 2) || ($maxGrade > 9)) { $maxGrade =6; }

$crList = isset($_POST['criteria']) ? explode(';', $_POST['criteria']) : NULL;

// Check that the required fileds are specified

if (empty($longName)|| empty($shortName)|| empty($confYear)|| !$chair) {
  print "<h1>Mandatory fields are missing</h1>\n";
  exit("You must specify the conference short and long names and year, and the program chair email address\n");
}

if ($confYear < 1970 || $confYear > 2099) {
  print "<h1>Wrong format for the conference year</h1>\n";
  exit("Year must be an integer between 1970 and 2099");
}

// Try to parse the deadlines as some recognized date format
// The error code from strtotime is -1 for PHP 4 and FALSE for PHP 5
$tsb = empty($subDeadline) ? false : strtotime($subDeadline);
if ($tsb!==false && $tsb!=-1) {
  $subDeadline = $tsb; // store as a number (unix time)
  $subDeadlineHtml = utcDate('r (T)', $tsb);
}
else die("<h1>Unrecognized time format for submission deadline</h1>");

$tcr = empty($cameraDeadline) ? false : strtotime($cameraDeadline) ;
if ($tcr!==false && $tcr!=-1) {
  $cameraDeadline = $tcr; // store as a number (unix time)
  $cameraDeadlineHtml = utcDate('r (T)', $tcr);
}
else die("<h1>Unrecognized time format for camera-ready deadline</h1>");

// Create an array of committee members
if (isset($committee)) {
  $list = $committee;
  $committee = array();
  foreach ($list as $m) { $committee[] = parse_email($m); }
}

// Create an array of criteria
$nCrits = 0;
if (isset($crList)) {
  $criteria = array();
  foreach ($crList as $cr) if ($c = parse_criterion($cr)) {
    $criteria[] = $c;
    $nCrits++;
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
  if ($f1ext[0] == '.') $f1ext = substr($f1ext, 1); // Remove leading '.'
  $cFrmts[] = array($f1dsc, $f1ext, $f1mime);
}
if (!empty($f2ext) || !empty($f2mime)) {
  if ($f2ext[0] == '.') $f2ext = substr($f2ext, 1); // Remove leading '.'
  $cFrmts[] = array($f2dsc, $f2ext, $f2mime);
}
if (!empty($f3ext) || !empty($f3mime)) {
  if ($f3ext[0] == '.') $f3ext = substr($f3ext, 1); // Remove leading '.'
  $cFrmts[] = array($f3dsc, $f3ext, $f3mime);
}

/* Before actually doing anything, ask the user to confirm again */
$longNameHtml  = htmlspecialchars($longName); 
$shortNameHtml = htmlspecialchars($shortName); 
$confYearHtml  = htmlspecialchars($confYear);  
$confURLHtml   = htmlspecialchars($confURL);   

$chairNmHtml   = htmlspecialchars($chair[0]);
$chairEmlHtml  = htmlspecialchars($chair[1]);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Confirm Customization</title>
<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<style type="text/css">
h1 {text-align: center;}
</style>
</head>
<body>
<h1>Confirm Customization</h1>

Please go carefully over these details and make sure that they are all
correct. If you find mistakes, use the Back button of your browser to return
to the customization form and correct them. If all the details are correct,
hit the Confirm button below to customize this installation. 

<h2>The Conference:</h2>     
<table cellspacing=6><tbody>
<tr><td class=rjust>Conference name:</td><td><b>$longNameHtml</b></td>
</tr>
<tr><td class=rjust>Conference&nbsp;short&nbsp;name:</td>
  <td><b>$shortNameHtml</b> &nbsp;$confYearHtml</td>
</tr>
<tr><td class=rjust>Conference URL:</td>
  <td><tt>$confURLHtml</tt></td>
</tr>
</tbody></table>

<h2>Submissions:</h2>     
<table cellspacing=6>
<tbody>
<tr><td class=rjust>Submission Deadline:</td>
  <td colspan="3"><b>$subDeadlineHtml</b></td>
</tr>
<tr><td class=rjust>Camera-ready Deadline:</td>
  <td colspan="3"><b>$cameraDeadlineHtml</b></td>
</tr>

EndMark;

if (is_array($categories) && count($categories)>0) {
  $i = 0;
  foreach ($categories as $c) {
    $c = htmlspecialchars(trim($c));
    if (!empty($c)) {
      if ($i>0) print "  <tr><td></td>\n";
      else      print "  <tr><td class=rjust>Categories:</td>\n";
      print "    <td colspan=\"3\">$c</td>\n</tr>\n";
      $i++;
    }
  }
  $nCats = $i;  // How many non-empty categories
}
else $nCats = 0;
if ($nCats == 0) {
  print '<tr><td class=rjust>Categories:</td>
  <td colspan="3">' . "<b>Not using categories</b></td></tr>\n";
}

print '<tr><td class=rjust>Require affiliations:</td>'
     . '<td colspan="3"><b>' . (isset($_POST['affiliations']) ? 'Yes' : 'No')
     . "</b></td>\n</tr>\n";

if (is_array($cFrmts) && count($cFrmts)>0) {
  print "<tr><th style=\"text-align:right;\">Supported Formats:</th>\n";
  print "  <th>Name</th> <th>Extension</th> <th>MIME-type</th>\n";
  print "</tr>\n";
  foreach($cFrmts as $f) {
    print "<tr><td></td><td>".htmlspecialchars($f[0])."</td> ";
    print "  <td>.$f[1]</td> <td>".htmlspecialchars($f[2])."</td>\n</tr>\n";
  }
}
else {
  print "<tr><td style=\"text-align:right;\">Supported Formats:</td>\n";
  print "  <td colspan=\"3\"><b>No Formats Recorded</b></td></tr>\n";
}
print <<<EndMark
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

<h2>Reviews:</h2>
<table cellspacing=6>
<tbody>
<tr><td colspan="2">$anonymous</td></tr>
<tr><td colspan="2">$revPrefs</td></tr>
<tr><td class=rjust>Overall Grades:</td><td><b>From 1 to $maxGrade</b></td>
</tr>
<tr><td class=rjust>Confidence&nbsp;Level:</td><td><b>From 1 to 3</b></td>
</tr>

EndMark;

if ($nCrits > 0) foreach ($criteria as $m) {
  $nm = trim($m[0]);
  $maxval = $m[1];
  if (!empty($nm)) {
    print "<tr><td class=rjust>$nm:</td>\n";
    print "  <td><b>From 1 to $maxval</b></td>\n</tr>\n";
  }
}

print <<<EndMark

</tbody></table>
<br/>
<form action="doCustomize.php{$urlParams}" enctype="multipart/form-data" method="post">

<input name="longName"  type="hidden" value="$longName">
<input name="shortName" type="hidden" value="$shortName">
<input name="confYear"  type="hidden" value="$confYear">
<input name="confURL"    type="hidden" value="$confURL">
<input name="subDeadline" type="hidden" value="$subDeadline">
<input name="cameraDeadline" type="hidden" value="$cameraDeadline">
<input name="chairName"  type="hidden" value="$chair[0]">
<input name="chairEmail" type="hidden" value="$chair[1]">

EndMark;

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