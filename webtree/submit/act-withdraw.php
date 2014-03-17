<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

require 'header.php'; // brings in the contacts file and utils file

if (defined('CAMERA_PERIOD')) {
  $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			  $_SERVER['PHP_AUTH_PW'], chair_ids());
  if ($chair === false) {
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Contact the chair to withdraw the submission</h1>");
  }
}

// Read fields, stripping spurious white-spaces
$subId = (int)$_POST['subId'];
$subPwd = trim($_POST['subPwd']);
$htmlPwd = htmlspecialchars($subPwd);

// Check that mandatory subID and subPwd are specified
if (empty($subId) || empty($subPwd))
  exit("<h1>Withdrawal Failed</h1>Missing submission-ID or password.");

// Test that there exists a submission with this subId/subPwd

$qry = "SELECT title, authors, contact FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=?";
$res=pdo_query($qry, array($subId,$subPwd));
$row=$res->fetch(PDO::FETCH_NUM)
  or exit("<h1>Withdrawal Failed</h1>
           No submission with ID $subId and password $htmlPwd was found.");

$ttl = $row[0];
$athr = $row[1];
$cntct = $row[2];

/***** User input vaildated. Next modify the status *****/

$qry = "UPDATE {$SQLprefix}submissions SET status='Withdrawn' WHERE subId=? AND subPwd=?";
pdo_query($qry, array($subId,$subPwd));

// Tell the client that the submission is withdrawn

if (defined('REVIEW_PERIOD') && REVIEW_PERIOD===true) { // send email only to chair
  email_submission_details(chair_emails(), 3, $subId, $subPwd, $ttl, $athr);
} else {                    // send email to contact author
  email_submission_details($cntct, 3, $subId, $subPwd, $ttl, $athr);
}
header("Location: withdrawn.php?subId=$subId&subPwd=$subPwd");
?>
