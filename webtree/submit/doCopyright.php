<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file

$confName = CONF_SHORT . ' ' . CONF_YEAR;
if (PERIOD<PERIOD_CAMERA)
     die("<h1>Final-version submission site for $confName is not open</h1>");

if (empty($_POST["signedBy1"]))
  exit("Signature on consent form (Part I) is required");

if (!empty($_POST["nonGovt"]) && empty($_POST["signedBy2"]))
  exit("Non-government authors must sign name in Part II");

if (!empty($_POST["govt"]) && empty($_POST["signedBy3"]))
  exit("Government authors must sign name in Part III");

if (empty($_POST["signedBy2"]) && 
    (empty($_POST["agency"]) || empty($_POST["country"])))
  exit("Agency and Country must be specified when signing copyright Part III");

$contact = trim($_POST["contact"]);
if (empty($contact))
  exit("Name and address of corresponding author are required");

$title = trim($_POST["title"]);
if (empty($title)) exit("Title of work is required");

$authors = trim($_POST["authors"]);
if (empty($authors)) exit("Authors must be specified");

$subId = isset($_POST['subId']) ? ((int)trim($_POST['subId'])) : '';
$subPwd = isset($_POST['subPwd']) ? trim($_POST['subPwd']) : '';

if ($subId<=0 || empty($subPwd))
  exit("submission-ID and password are required");

$qry = "SELECT contact, flags FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=? AND status='Accept'";
$res=pdo_query($qry, array($subId,$subPwd));
$row=$res->fetch(PDO::FETCH_NUM)
  or exit("No accepted submission with ID $subId and password $subPwd was found.");
$email = $row[0];
$flags = (int) $row[1];
$urlPrms = "?subId=$subId&subPwd=$subPwd";

$file = SUBMIT_DIR."/final/copyright.html";
if (!file_exists($file)) die("Cannot find copyright form");
$copyright = file_get_contents($file);

$copyright = str_replace('<input type="text" name="subId" value="[$subId]">, password: <input type="text" name="subPwd" value="[$subPwd]">', $subId, $copyright);

$copyright = str_replace('<input type="text" name="title" size="80" value="[$title]">', "<u>".htmlspecialchars($title)."</u>", $copyright);

$copyright = str_replace('<input type="text" name="authors" size="80" value="[$authors]">', "<u>".htmlspecialchars($authors)."</u>", $copyright);

$copyright = str_replace('<input type="text" name="contact" size="80">', "<u>".htmlspecialchars($contact)."</u>", $copyright);

$copyright = str_replace('<input type="checkbox" name="slides">',
			 (isset($_POST["slides"])? "(YES)" : "(NO)"),
			 $copyright);
$copyright = str_replace('<input type="checkbox" name="presentation">',
			 (isset($_POST["presentation"])? "(YES)" : "(NO)"),
			 $copyright);
$copyright = str_replace('<input type="checkbox" name="auxiliary">',
			 (isset($_POST["auxiliary"])? "(YES)" : "(NO)"),
			 $copyright);

$copyright = str_replace('<input type="text" name="signedBy1" size="40">',
			 "<u>".htmlspecialchars($_POST['signedBy1'])."</u>",
			 $copyright);

$signedBy2 = empty($_POST['signedBy2'])? '' :
  ("<p>Signed by: <u>".htmlspecialchars($_POST['signedBy2'])."</u> on ".date('F j, Y')."</p>");

$copyright = str_replace('<p>Signed by: <input type="text" name="signedBy2" size="40">. Today\'s date: <u>[$date]</u><br/><input class="submitButton" type="submit" name="nonGovt" value="I agree to the terms of this agreement"> (to be used by everyone except government employees)</p>', $signedBy2, $copyright);

if (empty($_POST['signedBy3'])) {
  $country = $signedBy3 = '';
} else {
  $country = '<p>Government agency: <u>'.htmlspecialchars($_POST['agency'])
    .'</u>, Country: <u>'.htmlspecialchars($_POST['country']).'</u></p>';
  $signedBy3 = "<p>Authorized Signature: <u>".htmlspecialchars($_POST['signedBy3'])
    ."</u> on ".date('F j, Y')."</p>";
}

$copyright = str_replace('<p>Government agency: <input type="text" size="60" name="agency">, Country: <input type="text" name="country"></p>', $country, $copyright);

$copyright = str_replace('<p>Signed by: <input type="text" name="signedBy3" size="40">. Today\'s date: <u>[$date]</u><br/><input class="submitButton" type="submit" name="govt" value="I am a government employee, this work is not subject to national or U.S. copyright protection"></p>', $signedBy3, $copyright);

$copyright = str_replace('[$confName]', $confName, $copyright);
$copyright = str_replace('[$date]', date('F j, Y'), $copyright);
$copyright = str_replace('[$year]', date('Y'), $copyright);

// Add some tracking information
$copyright = str_replace('</body>', '<hr/>Copyright submitted from '.$_SERVER['REMOTE_ADDR'].' at '.date('c',$_SERVER['REQUEST_TIME']).'</body>', $copyright);

// Record the copyright and consent in the database
if (isset($_POST["slides"])) 
     $flags |= FLAG_CONSENT_SLIDES;
else $flags &= ~(FLAG_CONSENT_SLIDES);
if (isset($_POST["presentation"])) 
     $flags |= FLAG_CONSENT_VIDEO;
else $flags &= ~(FLAG_CONSENT_VIDEO);
if (isset($_POST["auxiliary"]))
     $flags |= FLAG_CONSENT_OTHER;
else $flags &= ~(FLAG_CONSENT_OTHER);

$qry = "UPDATE {$SQLprefix}submissions sb, {$SQLprefix}acceptedPapers ac SET title=?, authors=?, flags=?, copyright=?, copyrightTime=NOW() WHERE ac.subId=sb.subId AND sb.subId=?";
pdo_query($qry, array($title,$authors,$flags,$copyright,$subId));

// Send by email
$subject = "[$confName] Copyright signed for '$title'";
$cc = chair_emails();               // cc the chairs
if (defined('IACR'))
  $cc[] = 'copyrightform@iacr.org'; // also cc the copyright address
my_send_mail($email, $subject, $copyright, $cc);

// Goto the camera-ready submission from
header("Location: cameraready.php{$urlPrms}");
?>
