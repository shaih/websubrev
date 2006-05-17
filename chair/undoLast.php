<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

// Check if bak or fwd files exist
$bkFile = CONST_FILE . '.bak.php';
if (!file_exists($bkFile)) $bkFile = NULL;

$fwFile = CONST_FILE . '.fwd.php';
if (!file_exists($fwFile)) $fwFile = NULL;

if (isset($_GET['undoLast']) && isset($bkFile)) { // undo last change
  if (isset($fwFile)) unlink($fwFile);
  rename(CONST_FILE, $fwFile);
  rename($bkFile, CONST_FILE);
  header("Location: index.php");
  exit();
}

if (isset($_GET['redoLast']) && isset($fwFile)) { // redo last change
  if (isset($bkFile)) unlink($bkFile);
  rename(CONST_FILE, $bkFile);
  rename($fwFile, CONST_FILE);
  header("Location: index.php");
  exit();
}

if (!isset($bkFile) && !isset($fwFile)) {
  exit("<h1>No Undo/Redo Information Available</h1>");
}

$links= show_chr_links(4);

if (isset($bkFile))
     $bkButton = '<input type=submit name=undoLast value="Undo Last Change">';
else $bkButton = '';

if (isset($fwFile))
     $fwButton = '<input type=submit name=redoLast value="Redo Last Change">';
else $fwButton = '';

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
</style>
<title>Undo/Redo Last Change</title>
</head>
<body>
$links
<hr/>
<h1>Undo/Redo Last Change</h1>
<form action="undoLast.php" method=get>
$bkButton
$fwButton
</form>
<hr/>
$links
</body>
</html>
EndMark;
?>
