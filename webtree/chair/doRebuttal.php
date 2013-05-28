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

$rebDeadline = defined('REBUTTAL_DEADLINE')? 
  date('Y-n-j G:i e', REBUTTAL_DEADLINE) : "";
$newrebDeadline = isset($_POST['rebDeadline'])? 
  strtotime($_POST['rebDeadline']): false;
if ($newrebDeadline>0) $rebDeadline = $newrebDeadline;

$maxRebuttal = defined('MAX_REBUTTAL') ? MAX_REBUTTAL : "";
$newMaxRebuttal = isset($_POST['maxRebuttal'])? (int) $_POST['maxRebuttal']: 0;
if ($newMaxRebuttal>0) $maxRebuttal=$newMaxRebuttal;

if (!empty($_POST['rebuttalOn']))
  $flags = "flags=(flags | ".FLAG_REBUTTAL_ON.")";
else
  $flags = "flags=(flags & ~ ".FLAG_REBUTTAL_ON.")";

$qry = "UPDATE parameters SET rebDeadline=".((int)$rebDeadline)
  .", maxRebuttal=".((int)$maxRebuttal).", $flags";
db_query($qry, $cnnct);

header("Location: rebuttal.php?success=true");
?>