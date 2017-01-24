<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
* This software is distributed under the terms of the open-source license
* Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
* in this package or at http://www.opensource.org/licenses/cpl1.0.php
*/
$needsAuthentication = true;
require 'header.php';

$rebDeadline = defined('REBUTTAL_DEADLINE')? 
  date('Y-n-j G:i e', REBUTTAL_DEADLINE) : "";
$newrebDeadline = isset($_POST['rebDeadline'])? 
  strtotime($_POST['rebDeadline']): false;
if ($newrebDeadline>0) $rebDeadline = $newrebDeadline;

$maxRebuttal = defined('MAX_REBUTTAL') ? MAX_REBUTTAL : "";
$newMaxRebuttal = isset($_POST['maxRebuttal'])? (int) $_POST['maxRebuttal']: 0;
if ($newMaxRebuttal>0) $maxRebuttal=$newMaxRebuttal;

if (!empty($_POST['rebuttalOn']))
  $flags = "flags=(flags | ?)";
else
  $flags = "flags=(flags & ~?)";

$qry = "UPDATE {$SQLprefix}parameters SET rebDeadline=?, maxRebuttal=?, $flags";
pdo_query($qry, array($rebDeadline,$maxRebuttal,FLAG_REBUTTAL_ON));

$qry = "UPDATE {$SQLprefix}submissions SET $flags";
exit($qry);
pdo_query($qry, array(FLAG_FINAL_REBUTTAL));

header("Location: rebuttal.php?success=true");
?>