<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

require 'header.php'; // brings in the contacts file and utils file
$confName = CONF_SHORT . ' ' . CONF_YEAR;

// Check that mandatory subId and subPwd are specified
$subId = (int) trim($_POST['subId']);
$subPwd = my_addslashes(trim($_POST['subPwd']));
if (empty($subId) || empty($subPwd))
  exit("<h1>Revision Failed</h1>Missing submission-ID or password.");

// If user pressed "Load submission details", redirect back to revision form
if (isset($_POST['loadDetails'])) {
  $ref = trim($_POST['referer']);
  if (empty($ref)) $ref = trim($_SERVER['HTTP_REFERER']);
  if (empty($ref)) $ref = (PERIOD<PERIOD_CAMERA) ? 'revise.php' : 'cameraready.php';
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
$eprint  = isset($_POST['eprint'])   ? trim($_POST['eprint'])   : '';
$optin   = isset($_POST['optin'])    ? ((int) trim($_POST['optin'])) : 0;

if (isset($_FILES['sub_file'])) {
  $sbFileName = trim($_FILES['sub_file']['name']);
  $tmpFile = $_FILES['sub_file']['tmp_name'];
  $fileSize = $_FILES['sub_file']['size'];
}
else $fileSize = $tmpFile = $sbFileName = NULL;

// Check that contact has valid format user@domain (if specified)
if (!empty($contact)) {
  $addresses = explode(",", $contact);
  foreach($addresses as $addr) {
    $addr = trim($addr);
    if (!preg_match('/^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$/i', $addr))
      exit("<h1>Revision Failed</h1>
Contact(s) must be a list of email addresses in the format user@domain.");
  }
}

// Test that file was uploaded (if specified)
if (!empty($sbFileName)) {
  if ($_FILES['sub_file']['size'] == 0)
     exit("<h1>Revision Failed</h1>Empty submission file uploaded.");
  if (!is_uploaded_file($tmpFile))
     exit("<h1>Revision Failed</h1>No file was uploaded.");
}

// Test that there exists a submission with this subId/subPwd

$qry = "SELECT title, authors, affiliations, contact, abstract, category, keyWords, comments2chair, format, status FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=?";
$res=pdo_query($qry,array($subId, $subPwd));
$row= $res->fetch(PDO::FETCH_ASSOC)
  or exit("<h1>Revision Failed</h1>
           No submission with ID $subId and password $subPwd was found.");
if ((PERIOD>=PERIOD_CAMERA) && $row['status']!='Accept') {
  exit("<h1>Submission with ID $subId was not accepted to the conference</h1>");
}

$oldStatus= $row['status'];
$oldCntct = $row['contact'];
$oldFrmt = $row['format'];

/***** User input vaildated. Next prepare the MySQL query *****/

$updts = '';
$prms = array();
if (!empty($title)) {
  $updts .= "title=?,";
  $prms[] = substr($title, 0, 255);
}
else $title = $row['title'];

if (!empty($author)) {
  $updts .= "authors=?,";
  $prms[] = $author;
}
else $author = $row['authors'];

if (!empty($affiliations)) {
  $updts .= "affiliations=?,";
  $prms[] = $affiliations;
}
else $affiliations = $row['affiliations'];

if (!empty($contact) && strcasecmp($contact, $oldCntct)!=0) {
  $oldCntct .= ", $contact";  // send email to both old and new contacts
  $updts .= "contact=?,";
  $prms[] = $contact;
}
else $contact = $row['contact'];

if (!empty($abstract)) {
  $updts .= "abstract=?,";
  $prms[] = $abstract;
}
else $abstract = $row['abstract'];

if (!empty($category)) {
  $updts .= "category=?,";
  $prms[] = substr($category, 0, 80);
}
else $category = $row['category'];

if (!empty($keywords)) {
  $updts .= "keyWords=?,";
  $prms[] = substr($keywords, 0, 255);
}
else $keywords = $row['keyWords'];

if (!empty($comment)) {
  $updts .= "comments2chair=?,";
  $prms[] = $comment;
}
else $comment = $row['comments2chair'];


if (!empty($optin)) {
  $updts .= "flags = flags | ".FLAG_IS_CHECKED.", \n";
} else {
  $updts .= "flags = flags & (~".FLAG_IS_CHECKED."), \n";
}

// If a new file is specified, try to determine its format and then
// store the uploaded file under a temporary name
$fileFormat = NULL;
if (!empty($sbFileName)) {
  $fileFormat = determine_format($_FILES['sub_file']['type'], $sbFileName, $tmpFile);
  $updts .= "format=?,";
  $prms[] = $fileFormat;

  $fileName = SUBMIT_DIR."/tmp.{$subId}." . date('YmdHis');
  if (!empty($fileFormat)) $fileName .= ".{$fileFormat}";
  if (!move_uploaded_file($tmpFile, $fileName)) {
    error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fileName) failed\n", 3, LOG_FILE);
    exit("<h1>Revision Failed</h1>
          Cannot move submission file " . $tmpFile . " to " . $fileName);
  }
}

// If anything changed, insert changes into the database
if (!empty($updts) || isset($_POST['reinstate'])) {
  if ((PERIOD<=PERIOD_SUBMIT) || isset($_POST['reinstate']) || $oldStatus=='Withdrawn')
    $updts .= "status='None',"; 
  $qry = "UPDATE {$SQLprefix}submissions SET $updts lastModified=NOW() WHERE subId=? AND subPwd=?";
  $prms[] = $subId;
  $prms[] = $subPwd;

  pdo_query($qry, $prms, 'Cannot insert submission details to database: ');
}
else { // Hmm.. nothing has changed, why are we here?
  if (PERIOD<PERIOD_CAMERA || !isset($_FILES['pdf_file']))
    exit("<h1>Revision Failed</h1>Revision form indicated no changes.");
}

// If a new file is specified, copy old submission file for backup
// and rename new submission file to $subId.$fileFormat
if (!empty($sbFileName)) {
  $oldName = $subId . (empty($oldFrmt) ? "" : ".$oldFrmt");
  $newName = $subId . (empty($fileFormat) ? "" : ".$fileFormat");

  // Save a backup copy of the old file and store the new one

  if (file_exists(SUBMIT_DIR."/backup/$oldName"))
    unlink(SUBMIT_DIR."/backup/$oldName");  // just in case

  $directory = (PERIOD>=PERIOD_CAMERA) ? SUBMIT_DIR."/final" : SUBMIT_DIR;
  if (file_exists("$directory/$oldName"))
    rename("$directory/$oldName", SUBMIT_DIR."/backup/$oldName");

  if ($newName!=$oldName && file_exists("$directory/$newName"))
    unlink("$directory/$newName");
  if (!rename($fileName, "$directory/$newName")) {
    error_log(date('Ymd-His: ')."rename($fileName, $directory/$newName) failed\n", 3, LOG_FILE);
    email_submission_details($oldCntct, -2, $subId, $subPwd, $title, 
	  $author, $contact, $abstract, $category, $keywords, $comment);
    header("Location: receipt.php?subId={$subId}&subPwd={$subPwd}&warning=1");
    exit();
  }
  if (PERIOD<PERIOD_CAMERA) { // mark new file as needing a stamp
    $qry = "UPDATE {$SQLprefix}submissions SET flags=(flags|".SUBMISSION_NEEDS_STAMP.") WHERE subId=?";
    pdo_query($qry, array($subId),"Cannot mark file as needing a stamp: ");
  }
}

// If submitting camera-ready file: record extra information
if (PERIOD>=PERIOD_CAMERA) {
  if (isset($_FILES['pdf_file'])) { // store also the PDF separately, if given
    $pdfFileName = trim($_FILES['pdf_file']['name']);
    $tmpFile = $_FILES['pdf_file']['tmp_name'];
    $pdfFileSize = $_FILES['pdf_file']['size'];
  }
  else $pdfFileName = $tmpFile = $pdfFileSize = NULL;
  $file2Format = (is_uploaded_file($tmpFile) && $pdfFileSize>0)?
    determine_format($_FILES['pdf_file']['type'], $pdfFileName, $tmpFile):
    '';
  // The determine_format will return 'pdf.unsupported', since for camera-ready
  // we only allow archive files. We just ignore the 'unsupported' part
  if (strtolower($file2Format)=='pdf.unsupported') { // if it exists, store it
    if (!empty($IACRdir))
      $pdfFileName = "$IACRdir/$confName.$subId.pdf";
    else 
      $pdfFileName = SUBMIT_DIR."/$subId.pdf";
    if (file_exists($pdfFileName)) unlink($pdfFileName);
    move_uploaded_file($tmpFile, $pdfFileName);
  }

  if (!empty($eprint)) {
    $qry = "UPDATE {$SQLprefix}acceptedPapers SET nPages=?, eprint=? WHERE subId=?";
    pdo_query($qry, array($nPages,$eprint,$subId),
	      "Cannot update ePrint information for submission $subId: ");
  }
}

     
// All went well, tell the client that we got the revised submission.

email_submission_details($oldCntct, 2, $subId, $subPwd, $title, $author,
      $contact, $abstract, $category, $keywords, $comment, $fileFormat,
      $fileSize);
header("Location: receipt.php?subId={$subId}&subPwd={$subPwd}");
?>
