<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$cnnct = db_connect();
$qry = "SELECT flags, emlSender from parameters WHERE version=".PARAMS_VERSION;
$res = db_query($qry, $cnnct, "Cannot load parameters: ");
$row = mysql_fetch_row($res) or die("No parameters are specified");
$oldFlags = $newFlags = (int) $row[0];
$oldSender = $row[1];

if (isset($_POST['tweakSettings'])) {
  if (isset($_POST['emlCrlf']) && $_POST['emlCrlf']==1) // use LF
    $newFlags |= FLAG_EML_HDR_CRLF;
  else                                                  // use CRLF
    $newFlags &= ~((int)FLAG_EML_HDR_CRLF);

  if (isset($_POST['xMailer']))
    $newFlags |= FLAG_EML_HDR_X_MAILER;
  else
    $newFlags &= ~((int)FLAG_EML_HDR_X_MAILER);

  $newSender = trim($_POST['sender']);
  
  if (isset($_POST['emlExtraPrm']))
    $newFlags |= FLAG_EML_EXTRA_PRM;
  else
    $newFlags &= ~((int)FLAG_EML_EXTRA_PRM);

  if ($newFlags!=$oldFlags || $newSender!=$oldSender) {
    $qry = "UPDATE parameters SET flags=$newFlags, emlSender='$newSender'\n"
      . "  WHERE version=".PARAMS_VERSION;
    $res = db_query($qry, $cnnct, "Cannot update settings: ");
  }
}
header("Location: tweakSite.php?tweaked=yes");
?>