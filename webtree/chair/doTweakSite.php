<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

if (isset($_POST['tweakSettings'])) {
  $timeShift = isset($_POST['timeShift'])? intval($_POST['timeShift']): 0;
  $newFlags = CONF_FLAGS;

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

  if ($newFlags!=CONF_FLAGS || $newSender!=EML_SENDER || $timeShift!=TIME_SHIFT) {
    $qry = "UPDATE {$SQLprefix}parameters SET flags=?, emlSender=?, timeShift=? WHERE version=?";
    $res = pdo_query($qry, array($newFlags,$newSender,$timeShift,PARAMS_VERSION));
  }
}
header("Location: tweakSite.php?tweaked=yes");
?>