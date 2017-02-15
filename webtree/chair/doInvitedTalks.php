<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php'; // brings in the contacts file and utils file
$cName = CONF_SHORT.' '.CONF_YEAR;

// Read all the fields, stripping spurious white-spaces
$title   = isset($_POST['title']) ? trim($_POST['title']) : NULL;
$author  = isset($_POST['authors']) ? trim($_POST['authors']) : NULL;
$affiliations  = isset($_POST['affiliations']) ? trim($_POST['affiliations']) : NULL;
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : NULL;

// Assign random (?) password to the submission
$subPwd = sha1(uniqid(rand()).mt_rand().$title.$author); // returns hex string
$subPwd = alphanum_encode(substr($subPwd, 0, 15));       // "compress" a bit

// Test that the mandatory fields are not empty
if (empty($title) || empty($author) || empty($contact))
{ exit ("<h1>Submission Failed</h1>Some required fields are missing."); }

// Test that the contact has a valid format user@domain
$addresses = explode(",", $contact);
foreach($addresses as $addr) {
  $addr = trim($addr);
  if (!preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i', $addr))
    exit("<h1>Revision Failed</h1>
Contact(s) must be a list of email addresses in the format user@domain.");
}

/*** User input vaildated. Next insert the new submission to the database ***/

$subId = 1+ pdo_query("SELECT MAX(subId) FROM {$SQLprefix}submissions")->fetchColumn();

$qry = "INSERT INTO {$SQLprefix}submissions (subId,title,authors,contact,affiliations,format,subPwd,status,whenSubmitted) VALUES (?,?,?,?,?,'',?,'Accept',NOW())";

pdo_query($qry, array($subId,$title,$author,$contact,$affiliations,$subPwd));
pdo_query("INSERT INTO {$SQLprefix}acceptedPapers SET subId=?", array($subId));

// All went well, send email to the contact author
$prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
$subject = "$cName: added invited talk \"$title\"";
$msg = "You can upload a camera-ready writeup for this invited talk from\n\n";
$msg .="  $prot://".BASE_URL."submit/\n\n";
$msg .="On that page you can find instructions for preparing the writeup\n";
$msg .="and uploading it to the server. You should use submission-ID $subId\n";
$msg .="and password $subPwd to upload the file.\n";
my_send_mail($contact, $subject, $msg, chair_emails(),"Invited talk with ID $subId and password $subPwd");

header("Location: .");
?>
