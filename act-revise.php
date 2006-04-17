<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

require 'header.php'; // brings in the contacts file and utils file

// Check that mandatory subId and subPwd are specified
$subId = (int) trim($_POST['subId']);
$subPwd = my_addslashes(trim($_POST['subPwd']));
if (empty($subId) || empty($subPwd))
  exit("<h1>Revision Failed</h1>Missing submission-ID or password.");

// If user pressed "Load submission details", redirect back to revision form
if (isset($_POST['loadDetails'])) {
  $ref = trim($_POST['referer']);
  if (empty($ref)) $ref = trim($_SERVER['HTTP_REFERER']);
  if (empty($ref)) $ref = 'revise.php';
  header("Location: $ref?subId={$subId}&subPwd={$subPwd}");
  exit();
}

// Read all the fields, stripping spurious white-spaces

$title   = isset($_POST['title'])    ? trim($_POST['title'])    : NULL;
$author  = isset($_POST['authors'])  ? trim($_POST['authors'])  : NULL;
$affiliations  = isset($_POST['affiliations']) ? trim($_POST['affiliations']) : NULL;
$contact = isset($_POST['contact'])  ? trim($_POST['contact'])  : NULL;
$abstract= isset($_POST['abstract']) ? trim($_POST['abstract']) : NULL;
$category= isset($_POST['category']) ? trim($_POST['category']) : NULL;
$keywords= isset($_POST['keywords']) ? trim($_POST['keywords']) : NULL;
$comment = isset($_POST['comment'])  ? trim($_POST['comment'])  : NULL;
$nPages  = isset($_POST['nPages'])   ? ((int) trim($_POST['nPages'])) : 0;

if (isset($_FILES['sub_file'])) {
  $sbFileName = trim($_FILES['sub_file']['name']);
  $tmpFile = $_FILES['sub_file']['tmp_name'];
}
else $tmpFile = $sbFileName = NULL;

// Check that contact has valid format user@domain (if specified)
if (!empty($contact) &&
    ((strlen($contact) > 255) ||
     !preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i', $contact))) {
     exit("<h1>Revision Failed</h1>
           Contact must be an email address in the format user@domain.");
}
// Test that file was uploaded (if specified)
if (!empty($sbFileName)) {
  if ($_FILES['sub_file']['size'] == 0)
     exit("<h1>Revision Failed</h1>Empty submission file uploaded.");
  if (!is_uploaded_file($tmpFile))
     exit("<h1>Revision Failed</h1>No file was uploaded.");
}

// Test that there exists a submission with this subId/subPwd

$cnnct = db_connect();
$qry = 'SELECT title, authors, affiliations, contact, abstract, category, keyWords, comments2chair, format, status FROM submissions WHERE'
       . " subId='{$subId}' AND subPwd='{$subPwd}'";
$res=db_query($qry, $cnnct);
$row=@mysql_fetch_assoc($res)
  or exit("<h1>Revision Failed</h1>
           No submission with ID $subId and password $subPwd was found.");
if (defined('CAMERA_PERIOD') && $row['status']!='Accept') {
  exit("<h1>Submission with ID $subId was not accepted to the conference</h1>");
}

$oldCntct = $row['contact'];
$oldFrmt = $row['format'];

/***** User input vaildated. Next prepare the MySQL query *****/

$updts = '';
if (!empty($title)) {
  $updts = "title='" . my_addslashes(substr($title, 0, 255), $cnnct)."',\n";
}
else $title = $row['title'];

if (!empty($author)) {
  $updts .= "  authors='" . my_addslashes($author, $cnnct). "',\n";
}
else $author = $row['authors'];

if (!empty($affiliations)) {
  $updts .= "affiliations='" . my_addslashes($affiliations, $cnnct) . "',\n";
}
else $affiliations = $row['affiliations'];

if (!empty($contact) && strcasecmp($contact, $oldCntct)!=0) {
  $contact = substr($contact, 0, 255);
  $oldCntct .= ", $contact";  // send email to both old and new contacts
  $updts .= "  contact='" . my_addslashes($contact, $cnnct)."',\n";
}
else $contact = $row['contact'];

if (!empty($abstract)) {
  $updts .= "  abstract='" . my_addslashes($abstract, $cnnct)."',\n";
}
else $abstract = $row['abstract'];

if (!empty($category)) {
  $updts .= "  category='" . my_addslashes(substr($category, 0, 80), $cnnct)."',\n";
}
else $category = $row['category'];

if (!empty($keywords)) {
  $updts .= "  keyWords='" . my_addslashes(substr($keywords, 0, 255), $cnnct)."',\n";
}
else $keywords = $row['keyWords'];

if (!empty($comment)) {
  $updts .= "  comments2chair='" . my_addslashes($comment, $cnnct)."',\n";
}
else $comment = $row['comments2chair'];

// If a new file is specified, try to determine its format and then
// store the uploaded file under a temporary name
$fileFormat = NULL;
if (!empty($sbFileName)) {
  $fileFormat = determine_format($_FILES['sub_file']['type'], $sbFileName, $tmpFile);
  $updts .= "  format='" . my_addslashes($fileFormat, $cnnct)."',\n";

  $fileName = SUBMIT_DIR."/tmp.{$subId}." . date('YmdHis');
  if (!empty($fileFormat)) $fileName .= ".{$fileFormat}";
  if (!move_uploaded_file($tmpFile, $fileName)) {
    error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fileName) failed\n", 3, './log/'.LOG_FILE);
    exit("<h1>Revision Failed</h1>
          Cannot move submission file " . $tmpFile . " to " . $fileName);
  }
}
     
// If anything changed, insert changes into the database
if (!empty($updts) || isset($_POST['reinstate'])) {
  if (!defined('REVIEW_PERIOD') || isset($_POST['reinstate']))
    $updts .= "status='None', "; 
  $qry = "UPDATE submissions SET $updts lastModified=NOW()\n"
    . "WHERE subId='{$subId}' AND subPwd='{$subPwd}'";
  db_query($qry, $cnnct, 'Cannot insert submission details to database: ');
}
else { // Hmm.. nothing has changed, why are we here?
  exit("<h1>Revision Failed</h1>Revision form indicated no changes.");
  //  header("Location: revise.php?subId={$subId}&subPwd={$subPwd}");
}

// If a new file is specified, copy old submission file for backup
// and rename new submission file to $subId.$fileFormat
if (!empty($sbFileName)) {
  $oldName = $subId . (empty($oldFrmt) ? "" : ".$oldFrmt");
  $newName = $subId . (empty($fileFormat) ? "" : ".$fileFormat");

  // Save a backup copy of the old file and store the new one

  if (file_exists(SUBMIT_DIR."/backup/$oldName"))
    unlink(SUBMIT_DIR."/backup/$oldName");  // just in case

  $directory = defined('CAMERA_PERIOD') ? SUBMIT_DIR."/final" : SUBMIT_DIR;
  if (file_exists("$directory/$oldName"))
    rename("$directory/$oldName", SUBMIT_DIR."/backup/$oldName");

  if (file_exists("$directory/$newName")) unlink("$directory/$newName");
  if (!rename($fileName, "$directory/$newName")) {
    error_log(date('Ymd-His: ')."rename($fileName, $directory/$newName) failed\n", 3, './log/'.LOG_FILE);
    email_submission_details($oldCntct, -2, $subId, $subPwd, $title, 
	  $author, $contact, $abstract, $category, $keywords, $comment);
    header("Location: receipt.php?subId={$subId}&subPwd={$subPwd}&warning=1");
    exit();
  }
}

// If submitting camera-ready file: record the number of pages
if (defined('CAMERA_PERIOD') && $nPages>0) {
  $qry = "UPDATE acceptedPapers SET nPages=$nPages WHERE subId={$subId}";
  db_query($qry, $cnnct,
	   "Cannot update number of pages for submission $subId: ");
}
     
// All went well, tell the client that we got the revised submission.

email_submission_details($oldCntct, 2, $subId, $subPwd, $title, $author,
      $contact, $abstract, $category, $keywords, $comment, $fileFormat);
header("Location: receipt.php?subId={$subId}&subPwd={$subPwd}");
?>
