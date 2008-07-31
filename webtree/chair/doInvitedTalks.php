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
$cnnct = db_connect();

// Read all the fields, stripping spurious white-spaces 

$title   = isset($_POST['title']) ? trim($_POST['title']) : NULL;
$author  = isset($_POST['authors']) ? trim($_POST['authors']) : NULL;
$affiliations  = isset($_POST['affiliations']) ? trim($_POST['affiliations']) : NULL;
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : NULL;

// Assign random (?) password to the submission
$subPwd = md5(uniqid(rand()) . mt_rand().$title.$author); // returns hex string
$subPwd = alphanum_encode(substr($subPwd, 0, 15));          // "compress" a bit

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

/***** User input vaildated. Next prepare the MySQL query *****/

// Sanitize user input while preparing the query
$cnnct = db_connect();
$qry = "INSERT INTO submissions SET title='"
  . my_addslashes(substr($title, 0, 255), $cnnct)
  . "', authors='". my_addslashes($author, $cnnct)
  . "', contact='". my_addslashes($contact, $cnnct)
  . "', affiliations='" . my_addslashes($affiliations, $cnnct)
  . "', format='', subPwd='{$subPwd}', status='Accept', whenSubmitted=NOW()";

// Insert the new submission to the database 
db_query($qry, $cnnct, "Cannot insert invited talk to database: ");

/* We find out the submission number from the database record. (Note that
 * we use the auto_increment mechanism of MySQL to ensure that concurrent
 * submisssions don't get assigned the same numbers. Let's hope that MySQL
 * has a sensible concurrency control.)
 */

// Find this submission in the database
$qry = "SELECT subId FROM submissions WHERE subPwd='{$subPwd}'
  AND title='"   . my_addslashes(substr($title, 0, 255), $cnnct)."'
  AND authors='" . my_addslashes($author, $cnnct)."'
  AND contact='" . my_addslashes(substr($contact, 0, 255), $cnnct)."'";
$res = db_query($qry, $cnnct);
$row = mysql_fetch_row($res);
$subId = $row[0];   // What number was assigned to this submission

if (empty($subId)){ // can't find this submission (transient database problem?)
  error_log(date('Ymd-His: ')."Can't find new submission w/ pwd $subPwd: ".mysql_error()."\n", 3, LOG_FILE);  
  exit("<h1>Problems adding a submission with password $subPwd to database</h1>");
}

$qry = "INSERT INTO acceptedPapers SET subId=$subId";
db_query($qry, $cnnct);

// All went well, send email to the contact author
$prot = (defined('HTTPS_ON')||isset($_SERVER['HTTPS']))? 'https' : 'http';
$subject = "$cName: added invited talk \"$title\"";
$msg = "You can upload a camera-ready writeup for this invited talk from\n\n";
$msg .="  $prot://".BASE_URL."submit/\n\n";
$msg .="On that page you can find instructions for preparing the writeup\n";
$msg .="and uploading it to the server. You should use submission-ID $subId\n";
$msg .="and password $subPwd to upload the file.\n";
my_send_mail($contact, $subject, $msg, CHAIR_EMAIL,"Invited talk with ID $subId and password $subPwd");

header("Location: .");
?>
