<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (PERIOD>PERIOD_REVIEW) exit("<h1>The Review Site is Already Closed</h1>");
if (PERIOD<PERIOD_REVIEW) exit("<h1>The Review Site is Not Yet Open</h1>");

$updates = "version=version+1";

$cmrDdln=isset($_POST['cameraDeadline'])? trim($_POST['cameraDeadline']): NULL;
if (!empty($cmrDdln)) {
  $cmrDdln = strtotime($cmrDdln);
  if ($cmrDdln===false || $cmrDdln==-1)
    die ("<h1>Unrecognized time format for camera-ready deadline</h1>");
  if ($cmrDdln != CAMERA_DEADLINE) $updates .= ", cmrDeadline=$cmrDdln";
}

$updates .= ", period=".PERIOD_CAMERA;

$updates .= ",\n formats='tar(tar, application/x-tar);Compressed tar(tar.gz, application/x-tar-gz);Compressed tar(tgz, application/x-compressed-tar);zip(zip, application/x-zip)'";

$cameraAnnouncement = trim($_POST['finalVersionInstructions']);
$cameraAnnouncement = str_replace("\r\n", "\n", $cameraAnnouncement);
$subject = trim($_POST['subject']);

send_camera_instructions($cnnct, $cameraAnnouncement, $subject);

$cnnct = db_connect();
backup_conf_params($cnnct, PARAMS_VERSION);
$qry = "UPDATE parameters SET $updates";
db_query($qry, $cnnct, "Cannot reset parameters: ");

// All went well, go back to administration page
header("Location: index.php");
exit();

function send_camera_instructions($cnnct, $text, $sbjct)
{
  $qry = "SELECT subId, title, authors, contact FROM submissions WHERE status='Accept'";
  $res = db_query($qry, $cnnct);
  $count=0;
  while ($row = mysql_fetch_row($res)) {
    $subId = (int) $row[0];
    $contact = $row[3];

    my_send_mail($contact, $sbjct, $text, CHAIR_EMAIL,
	       "camera-ready notification for subID $subId, contact $contact");

    $count++;
    if (($count % 25)==0) { // rate-limiting, avoids cutoff
      sleep(1);
    }
  }
}
?>
