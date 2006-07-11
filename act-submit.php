<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the contacts file and utils file

if (defined('CAMERA_PERIOD')) exit("<h1>Submission Deadline Expired</h1>");

// Read all the fields, stripping spurious white-spaces 

$title   = isset($_POST['title']) ? trim($_POST['title']) : NULL;
$author  = isset($_POST['authors']) ? trim($_POST['authors']) : NULL;
$affiliations  = isset($_POST['affiliations']) ? trim($_POST['affiliations']) : NULL;
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : NULL;
$abstract= isset($_POST['abstract']) ? trim($_POST['abstract']) : NULL;
$category= isset($_POST['category']) ? trim($_POST['category']) : NULL;
$keywords= isset($_POST['keywords']) ? trim($_POST['keywords']) : NULL;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : NULL;

if (isset($_FILES['sub_file'])) {
  $sbFileName = trim($_FILES['sub_file']['name']);
  $tmpFile = $_FILES['sub_file']['tmp_name'];
}
else $sbFileName = NULL;

// Assign random (?) password to the new submission
$subPwd = md5(uniqid(rand()) . mt_rand().$title.$author); // returns hex string
$subPwd = alphanum_encode(substr($subPwd, 0, 15));          // "compress" a bit

// Test that the mandatory fields are not empty
if (empty($title) || empty($author)
    || empty($contact) || empty($abstract) || empty($sbFileName)) 
{ exit ("<h1>Submission Failed</h1>Some required fields are missing."); }

// Test that the contact has a valid format user@domain
if ((strlen($contact) > 255)
    || !preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i', $contact))
{ exit("<h1>Submission Failed</h1>
        Contact must be an email address in the format user@domain.");
}

// Test that a file was uploaded
if ($_FILES['sub_file']['size'] == 0)
  exit("<h1>Submission Failed</h1>Empty submission file uploaded.");
if (!is_uploaded_file($tmpFile))
  exit("<h1>Submission Failed</h1>No file was uploaded.");

/***** User input vaildated. Next prepare the MySQL query *****/

/* Try to determine the format of the submission file. The function
 * returns an extension (e.g., 'ps', 'pdf', 'doc', etc.) If it cannot
 * find a matching supported formar, it returns "{$ext}.unsupported"
 */
$fileFormat = determine_format($_FILES['sub_file']['type'], $sbFileName, $tmpFile);

// Sanitize user input while preparing the query
$cnnct = db_connect();
$qry = "INSERT INTO submissions SET title='"
  . my_addslashes(substr($title, 0, 255), $cnnct)
  . "', authors='". my_addslashes($author, $cnnct)
  . "', contact='". my_addslashes(substr($contact, 0, 255), $cnnct)
  . "', abstract='". my_addslashes($abstract, $cnnct)
  . "', ";

if (!empty($affiliations)) {
  $qry .= "affiliations='" . my_addslashes($affiliations, $cnnct) . "', ";
}
if (!empty($category)) {
  $qry .= "category='" . my_addslashes(substr($category, 0, 80), $cnnct) . "', ";
}
if (!empty($keywords)) {
  $qry .= "keyWords='" . my_addslashes(substr($keywords, 0, 255), $cnnct). "', ";
}
if (!empty($comment)) {
  $qry .=  "comments2chair='" . my_addslashes($comment, $cnnct) . "', ";
}
$qry .= "format='" . my_addslashes($fileFormat, $cnnct)
     . "', subPwd='{$subPwd}', whenSubmitted=NOW()";

/* Now we need to record the new submission. Below we try to minimize the
 * odds of an "inconsistent state": we first move the file to a temporary
 * name, then insert the record to the database, and then rename the temp
 * file to its permanent name. The submission is considered received once
 * it is in the database. 
 */

// Store the submission file under a temporary name
$fileName = SUBMIT_DIR."/tmp.{$subPwd}" . date('is');
if (!empty($fileFormat)) $fileName .= ".{$fileFormat}";

if (!move_uploaded_file($tmpFile, $fileName)) {
  error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fileName) failed\n", 3, './log/'.LOG_FILE);
  exit("<h1>Submission Failed</h1>
        Cannot move submission file " . $tmpFile . " to " . $fileName);
}

// Insert the new submission to the database 
db_query($qry, $cnnct, "Cannot insert submission details to database: ");

/**** Submission inserted, all that is left it to rename the submission
 * file to its permannet name. If anything goes wrong below, we will not
 * reject the submission but rather use the temp file in lieu of the 
 * permanent one until an administrator fixes it.
 ****/

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

if (empty($subId)) { // can't find this submission (transient database problem?)
  // Send a confirmation email without the submission-id
  error_log(date('Ymd-His: ')."Can't find new submission: ".mysql_error()."\n", 3, './log/'.LOG_FILE);  
  email_submission_details($contact, -1, 0, $subPwd, $title, 
        $author, $contact, $abstract, $category, $keywords, $comment);

  // Try to use the password as a key for the submission
  header("Location: receipt.php?subPwd=$subPwd&warning=1");
  exit();
}

// Rename the submission file to $subId.$fileFormat

$sbFileName = SUBMIT_DIR."/{$subId}";
if (!empty($fileFormat)) $sbFileName .= ".{$fileFormat}";

if (file_exists($sbFileName)) unlink($sbFileName); // just in case
if (!rename($fileName, $sbFileName)) {  // problems with the file system?
  error_log(date('Ymd-His: ')."rename($fileName, $sbFileName) failed\n", 3, './log/'.LOG_FILE);
  email_submission_details($contact, -1, $subId, $subPwd, $title, 
        $author, $contact, $abstract, $category, $keywords, $comment);
  header("Location: receipt.php?subId=$subId&subPwd=$subPwd&warning=1");
  exit();
}

// All went well, tell the client that we got the new submission.

email_submission_details($contact, 1, $subId, $subPwd, $title, $author, 
      $contact, $abstract, $category, $keywords, $comment, $fileFormat);
header("Location: receipt.php?subId=$subId&subPwd=$subPwd");
?>
