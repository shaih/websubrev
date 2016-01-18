<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
//exit("<pre>".print_r($_POST,true)."</pre>");

require 'header.php'; // brings in the contacts file and utils file

if (defined('CAMERA_PERIOD')) exit("<h1>Submission Deadline Expired</h1>");

if (USE_PRE_REGISTRATION && PERIOD>PERIOD_PREREG) {
  if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
    $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			    $_SERVER['PHP_AUTH_PW'], chair_ids());
  if ($chair === false) {
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Pre-registration Deadline Expired</h1>Please contact the chair.");
  }
}

// Read all the fields, stripping spurious white-spaces 

$title   = isset($_POST['title'])?    trim($_POST['title'])    : NULL;
$author  = isset($_POST['authors'])?  $_POST['authors']        : NULL;
$affiliations  = isset($_POST['affiliations'])? $_POST['affiliations'] : NULL;
$authIDs = isset($_POST['authID'])?   $_POST['authID']        : NULL;
$contact = isset($_POST['contact'])?  trim($_POST['contact'])  : NULL;
$abstract= isset($_POST['abstract'])? trim($_POST['abstract']) : NULL;
$category= isset($_POST['category'])? trim($_POST['category']) : NULL;
$keywords= isset($_POST['keywords'])? trim($_POST['keywords']) : NULL;
$comment = isset($_POST['comment'])?  trim($_POST['comment'])  : NULL;
$optin = isset($_POST['optin'])?      trim($_POST['optin'])    : NULL;

if (isset($_FILES['sub_file'])) {
  $sbFileName = trim($_FILES['sub_file']['name']);
  $tmpFile = $_FILES['sub_file']['tmp_name'];
}
else $sbFileName = NULL;

if (isset($_FILES['auxMaterial'])) {
  $auxFileType = file_extension($_FILES['auxMaterial']['name']);
  $auxTmpFile = $_FILES['auxMaterial']['tmp_name'];
}
else $auxFileType = NULL;

// convert arrays to semi-colon separated lists
list($author,$affiliations,$authIDs) = arraysToStrings($author,$affiliations,$authIDs);

// Assign random (?) password to the new submission
$subPwd = sha1(uniqid(rand()) . mt_rand().$title.$author); // returns hex string
$subPwd = alphanum_encode(substr($subPwd, 0, 15));          // "compress" a bit

// Test that the mandatory fields are not empty. Submissions without an
// actual submission file are allowed if the conference uses pre-registration
if (empty($title) || empty($author) || empty($contact) || empty($abstract)
    || (!USE_PRE_REGISTRATION && empty($sbFileName))
    ) 
{ exit ("<h1>Submission Failed</h1>Some required fields are missing."); }

// Test that the contact has a valid format user@domain
$addresses = explode(",", $contact);
foreach($addresses as $addr) {
  $addr = trim($addr);
  if (!preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i', $addr))
    exit("<h1>Revision Failed</h1>
Contact(s) must be a list of email addresses in the format user@domain.");
}

// Test that a file was uploaded
if (!empty($sbFileName)) {
  if ($_FILES['sub_file']['size'] == 0)
    exit("<h1>Submission Failed</h1>Empty submission file uploaded.");
  if (!is_uploaded_file($tmpFile))
    exit("<h1>Submission Failed</h1>No file was uploaded.");

  /* Try to determine the format of the submission file. The function
   * returns an extension (e.g., 'ps', 'pdf', 'doc', etc.) If it cannot
   * find a matching supported formar, it returns "{$ext}.unsupported"
   */
  $fileFormat = determine_format($_FILES['sub_file']['type'], $sbFileName, $tmpFile);
}
else $fileFormat = NULL;

/***** User input vaildated. Next prepare the MySQL query *****/

$qry = "INSERT INTO {$SQLprefix}submissions SET subId=?,subPwd=?,title=?,authors=?,contact=?,abstract=?,";
$prms = array(0,$subPwd,$title,$author,$contact,$abstract);

if (!empty($affiliations)) {
  $qry .= "affiliations=?,";
  $prms[] = $affiliations;
}
if (!empty($category)) {
  $qry .= "category=?,";
  $prms[] = substr($category, 0, 80);
}
if (!empty($keywords)) {
  $qry .= "keyWords=?,";
  $prms[] = substr($keywords, 0, 255);
}
if (!empty($comment)) {
  $qry .= "comments2chair=?,";
  $prms[] = $comment;
}
if (isset($fileFormat)) {
  $qry .= "format=?,";
  $prms[] = $fileFormat;
}
if (isset($auxFileType)) {
  $qry .= "auxMaterial=?,";
  $prms[] = $auxFileType;
}
if (isset($optin)) {
  $qry .= "flags=?,";
  $prms[] = FLAG_IS_CHECKED;
}
if (!empty($authIDs)) {
  $qry .= "authorIDs=?,";
  $prms[] = $authIDs;
}
$qry .= "whenSubmitted=NOW()";

/* Now we need to record the new submission. Below we try to minimize the
 * odds of an "inconsistent state": we first move the file to a temporary
 * name, then insert the record to the database, and then rename the temp
 * file to its permanent name. The submission is considered received once
 * it is in the database. 
 */

// Store the submission file under a temporary name
if (!empty($sbFileName)) {
  $fileName = SUBMIT_DIR."/tmp.{$subPwd}" . date('is');
  if (!empty($fileFormat)) $fileName .= ".{$fileFormat}";

  if (!move_uploaded_file($tmpFile, $fileName)) {
    error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fileName) failed\n", 3, LOG_FILE);
    exit("<h1>Submission Failed</h1>
        Cannot move submission file " . $tmpFile . " to " . $fileName);
  }
}

// Insert the new submission to the database 

/* Get next subId for insertion */
$qry2 = "SELECT MAX(subId) FROM {$SQLprefix}submissions WHERE subId<9000";

$prms[0] = 1+pdo_query($qry2)->fetchColumn();
if ($prms[0] <= 100) $prms[0] = 101;
pdo_query($qry, $prms, "Cannot insert submission details to database: ");
$subId = $prms[0];

/**** Submission inserted, all that is left it to rename the submission
 * file to its permanent name. If anything goes wrong below, we will not
 * reject the submission but rather use the temp file in lieu of the 
 * permanent one until an administrator fixes it.
 ****/

// Rename the submission file to $subId.$fileFormat

if (!empty($sbFileName)) {
  $sbFileName = SUBMIT_DIR."/{$subId}";
  if (!empty($fileFormat)) $sbFileName .= ".{$fileFormat}";

  if (file_exists($sbFileName)) unlink($sbFileName); // just in case
  if (!rename($fileName, $sbFileName)) {  // problems with the file system?
    error_log(date('Ymd-His: ')."rename($fileName, $sbFileName) failed\n", 3, LOG_FILE);
    email_submission_details($contact, -1, $subId, $subPwd, $title, $author, 
			 $contact, $abstract, $category, $keywords, $comment);
    header("Location: receipt.php?subId=$subId&subPwd=$subPwd&warning=1");
    exit();
  }
  elseif (PERIOD<PERIOD_CAMERA) { // mark new file as needing a stamp
    $qry = "UPDATE {$SQLprefix}submissions SET flags=(flags|?) WHERE subId=?";
    pdo_query($qry, array(SUBMISSION_NEEDS_STAMP,$subId),
	     "Cannot mark file as needing a stamp: ");
  }
}
// Store supported material as  $subId.aux.$fileFormat
if (isset($auxFileType)) {
  $auxFileName = SUBMIT_DIR."/{$subId}.aux.{$auxFileType}";
  if (!move_uploaded_file($auxTmpFile, $auxFileName)) {
    error_log(date('Ymd-His: ')."move_uploaded_file($auxTmpFile, $auxFileName) failed\n", 3, LOG_FILE);
  }
}

// All went well, tell the client that we got the new submission.

$filesize = isset($_FILES['sub_file']['size'])?
            $_FILES['sub_file']['size'] : NULL;
email_submission_details($contact, 1, $subId, $subPwd, $title, $author, 
      $contact, $abstract, $category, $keywords, $comment, $fileFormat,
      $filesize);
if (!empty($_POST['noConflicts'])) {
  header("Location: receipt.php?subId=$subId&subPwd=$subPwd");
} else {
  header("Location: specifyConflicts.php?subId=$subId&subPwd=$subPwd");
}
?>
