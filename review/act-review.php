<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
require 'store-review.php';
$revId = (int) $pcMember[0];

if (defined('CAMERA_PERIOD')) {
   exit("<h1>Site closed: cannot post new reviews</h1>");
}
$ret = storeReview($_POST['subId'], $revId, $_POST['subRev'], $_POST['conf'],
		   $_POST['grade'], $_POST, $_POST['comments2authors'],
		   $_POST['comments2PC'], $_POST['comments2chair']);

if ($ret==-1) exit("<h1>No Submission specified</h1>");
if ($ret==-3) exit("<h1>Submission does not exist or reviewer has a conflict</h1>");

header("Location: receipt-report.php?subId={$subId}&revId={$revId}");
?>
