<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$bypassAuth = true; // allow access to this script even after the deadline
if(isset($_GET['attachment']))
  $allow_rebuttal = true;

require 'header.php'; // brings in the constants and utils files
$subId = isset($_GET['subId']) ? ((int)trim($_GET['subId'])) : 0;
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';

if ($subId<=0 || empty($subPwd)) {
  die('<h1>Missing Submission-ID or password</h1>');
}

$view_reviews = false;

$qry= "SELECT format, nPages, auxMaterial FROM {$SQLprefix}submissions s
  LEFT JOIN {$SQLprefix}acceptedPapers a ON a.subId=s.subId
  WHERE s.subId=? AND s.subPwd=?";
$res = pdo_query($qry, array($subId,$subPwd));
$row = $res->fetch(PDO::FETCH_NUM)
  or die("<h1>Wrong Submission-ID or password</h1>");
$finalVersion = isset($row[1]);
$fmt = isset($_GET['aux'])? $row[2] : $row[0];
$fmt = strtolower($fmt);


// Find the MIME type of this format
$mimeType = NULL;
if (is_array($confFormats) && isset($confFormats[$fmt]))
  $mimeType = $confFormats[$fmt][1];
else if ($fmt=='pdf') $mimeType = 'application/pdf';
else if ($fmt=='ps') $mimeType = 'application/postscript';
else if ($fmt=='tar') $mimeType = 'application/x-tar';
else if ($fmt=='tar.gz') $mimeType = 'application/x-tar-gz';
else if ($fmt=='tgz') $mimeType = 'application/x-compressed-tar';
else if ($fmt=='zip') $mimeType = 'application/x-zip';
else if ($fmt=='jpeg' || $fmt=='jpg') $mimeType = 'image/jpeg';
else if ($fmt=='gif') $mimeType = 'image/gif';
else if ($fmt=='ppt') $mimeType = 'application/powerpoint';
else if ($fmt=='doc') $mimeType = 'application/msword';
else if ($fmt=='html' || $fmt=='htm') $mimeType = 'text/html';
else if ($fmt=='tex' || $fmt=='latex') $mimeType = 'application/x-tex';
else if ($fmt=='odt') $mimeType = 'application/vnd.oasis.opendocument.text';
else if ($fmt=='odp') $mimeType = 'application/vnd.oasis.opendocument.presentation';

if ((PERIOD>=PERIOD_CAMERA) && $finalVersion) {
  $fileName = SUBMIT_DIR."/final/$subId.$fmt";
} else if (isset($_GET['aux'])) {
  $fileName = SUBMIT_DIR."/$subId.aux.$fmt";
} else {
  $fileName = SUBMIT_DIR."/$subId.$fmt";
}

if (isset($_GET['attachment']) && $view_reviews) {
  $qry = "SELECT attachment FROM {$SQLprefix}reports WHERE subId=? AND attachment=?";
  $res = pdo_query($qry, array($subId,$_GET['attachment']));
  if ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $filename = SUBMIT_DIR."/attachments/".$row['attachment'];
  }
}

if (!file_exists($fileName)) {
  exit("<h1>Submission file not found</h1>");
}

if (isset($mimeType))  header("Content-Type: $mimeType");
$name = isset($_GET['aux'])? "$subId.aux.$fmt" : "$subId.$fmt";
header("Content-Disposition: inline; filename=\"$name\"");

/* If you cannot download large files, try replacing the call to
 * readfile by readfile_chunked. Namely, use the line:
 *
 *   if (!readfile_chunked($fileName)) {
 */
if (!readfile($fileName)) {
  exit("<h1>Error reading file</h1>");
}
?>
