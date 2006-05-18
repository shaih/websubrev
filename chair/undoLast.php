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
$fwFile = CONST_FILE . '.fwd.php';

if (isset($_GET['undoLast']) && file_exists($bkFile)) { // undo last change
  if (file_exists($fwFile)) unlink($fwFile);
  rename(CONST_FILE, $fwFile);
  rename($bkFile, CONST_FILE);
  header("Location: index.php");
  exit();
}

if (isset($_GET['redoLast']) && file_exists($fwFile)) { // redo last change
  if (file_exists($bkFile)) unlink($bkFile);
  rename(CONST_FILE, $bkFile);
  rename($fwFile, CONST_FILE);
  header("Location: index.php");
  exit();
}

if (!file_exists($bkFile) && !file_exists($fwFile)) {
  exit("<h1>No Undo/Redo Information Available</h1>");
}

$links= show_chr_links(4);

if (file_exists($bkFile))
     $bkButton = '<input type=submit name=undoLast value="Undo Last Change">';
else $bkButton = '';

if (file_exists($fwFile))
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
Use this form to Undo the last change that you did from the administration
page (or redo the last change that you un-did from this page). Currently,
only one version of Undo/Redo information is kept, so you cannot undo/redo
multiple changes.<br/>
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
