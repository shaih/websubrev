<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true;

// If password is given in the URL, don't need to ask the user for it 
$pwd = isset($_GET['pwd']) ? trim($_GET['pwd']) : NULL;
if (!empty($pwd)) {
  require_once('../includes/confConstants.php'); 
  $_SERVER['PHP_AUTH_PW'] = $pwd;
  $_SERVER['PHP_AUTH_USER'] = CHAIR_EMAIL;
}
require 'header.php';// authenticate the chair

if (defined('REVIEW_PERIOD')) exit("<h1>Submission Site is Closed</h1>");

$eml = CHAIR_EMAIL;
$eml2 = ADMIN_EMAIL;

$testOnly = isset($_GET['testOnly']) ? ' (including dummy test data)' : '';

print <<<EndMark
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Installation Customized</title>
</head>

<body>
<h1>Installation Customized{$testOnly}</h1>
<table>
<tbody>
EndMark;

$cName = CONF_NAME." (".CONF_SHORT." ".CONF_YEAR.")";
print "
  <tr><td>Conference name:</td>
      <td><b>$cName</b></td>
  </tr>
  <tr><td>Conference home-page:</td><td><tt>".CONF_HOME."</tt></td>
  </tr>
  <tr><td>Chair email:</td>
      <td><tt>".CHAIR_EMAIL."</tt></td>
  </tr>
  <tr><td>Admin email:</td>
      <td><tt>".ADMIN_EMAIL."</tt></td>
  </tr>
  <tr><td>Submission deadline:</td>
      <td><b>".SUBMIT_DEADLINE."</b></td>
  </tr>
  <tr><td>Camera-ready deadline:</td>
      <td><b>".CAMERA_DEADLINE."</b></td>
  </tr>
  <tr><td>Affiliations:</td>\n";

if (USE_AFFILIATIONS) print "     <td>Required on submission</td>\n  </tr>\n";
else print "     <td>Not required</td>\n  </tr>\n";

print "  <tr><td>Anonymous submissions:</td>\n";
if (ANONYMOUS) print "     <td>Submissions are anonymous</td>\n  </tr>\n";
else print "     <td>Submissions are <b>not</b> anonymous</td>\n  </tr>\n";

if (is_array($categories) && (count($categories)>0)) {
  print "  <tr><td>&nbsp;</td><td></td></tr>\n";
  print "  <tr><td>Categories:</td>";
  foreach ($categories as $c)  // one row for each format
    print "<td>$c</td></tr>\n  <tr><td></td>";
  print "<td>&nbsp;</td></tr>\n";
}

if (is_array($confFormats) && (count($confFormats)>0)) {
  print "  <tr><td>Supported Formats:</td>";
  foreach ($confFormats as $ext => $f)  // one row for each format
    print "<td>$f[0] (<tt>.$ext, $f[1]</tt>)</td></tr>\n  <tr><td></td>";
  print "<td>&nbsp;</td></tr>\n";
}

print "  <tr><td>Review grades:</td><td>1 through ".MAX_GRADE."</td></tr>\n";
print "  <tr><td>Confidence levels:</td>\n";
print "     <td>1 through ".MAX_CONFIDENCE."</td>\n  </tr>\n";

if (is_array($criteria) && (count($criteria)>0)) {
  print "  <tr><td>&nbsp;</td><td></td></tr>\n";
  print "  <tr><td colspan=\"2\">Additional evaluation criteria:</td></tr>\n";
  foreach ($criteria as $cr)  
    print "  <tr><td>$cr[0]:</td><td>1 through $cr[1]</td></tr>\n";
}
print "  <tr><td>&nbsp;</td><td></td></tr>\n";

$cnnct = db_connect();
$qry = "SELECT revId, name, email FROM committee";
$res = db_query($qry, $cnnct);
$hdr = "Program Committee:";
while ($row = mysql_fetch_row($res)) {
  print "  <tr><td>$hdr &nbsp;</td>\n";
  print "      <td>$row[1] &lt;$row[2]&gt;</td>\n  </tr>\n";
  $hdr = "";
}
print "</tbody>\n</table>\n<br />\n";

if (!defined('REVIEW_PERIOD')) {
  print "The submission start page is now <a href=\"../index.php\">";
} else if (!defined('CAMERA_PERIOD')) {
  print "The review start page is <a href=\"../review/index.php\">";
} else print "The final-version submission page is <a href=\"../index.php\">";

print <<<EndMark
available here</a>,
and the administration page is <a href="index.php">available here</a>.

EndMark;

if (!empty($pwd)) {
  print <<<EndMark
<b>To access the administration page you must login with username "$eml" and
password "$pwd".</b> An email message containing the username/password
was sent to $eml and also to $eml2.

EndMark;
}
?>
</body>
</html>
