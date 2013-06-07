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

backup_conf_params(PARAMS_VERSION);
pdo_query("UPDATE {$SQLprefix}parameters SET $updates");

// All went well, go back to caller
return_to_caller("activateCamera.php");
?>
