<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';
$chrEml = $chair[2];

if (PERIOD!=PERIOD_REVIEW) {
  exit("<h1>Can only purge submissions during the review period</h1>");
}

if (!isset($_POST['purged']) || !is_array($_POST['purged']) || empty($_POST['purged'])) {
  exit("<h1>Nothing to do</h1><a href=\".\">Back to main page</a>");
}

$toPurge = numberlist(array_keys($_POST['purged']));

// Make sure that all these submissions are not already withdrawn
$qry = "SELECT subId,subPwd,title,authors,contact FROM {$SQLprefix}submissions WHERE subId IN ($toPurge) AND status!='Withdrawn'";
$subs = pdo_query($qry)->fetchAll(PDO::FETCH_ASSOC);

// make a list of only the submission-IDs
$subIds = array();
foreach ($subs as $sb) {
  $subIds[] = $sb['subId'];
}

// Now set them all to 'Withdrawn'
if (!empty($subs)) {
  $qry = "UPDATE {$SQLprefix}submissions SET status='Withdrawn' WHERE subId IN ("
    . implode(',', $subIds) . ")";
  pdo_query($qry);

  // Send notification emails
  foreach ($subs as $sb) {
    if (isset($_POST['notifyByEmail']))
      email_submission_details($sb['contact'], 3, $sb['subId'],
			       $sb['subPwd'], $sb['title'], $sb['authors']);
    else
      email_submission_details($chrEml, 3, $sb['subId'],
			       $sb['subPwd'], $sb['title'], $sb['authors']);
  }
}
$nPurged = count($subs);
$cName = CONF_SHORT.' '.CONF_YEAR;
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<title>Pre-registrations to $cName purged</title>
</head>
<body>
Withdrawn $nPurged submissions without a submission file.
<a href=".">Back to main page</a>.
</body>
</html>
EndMark;
?>
