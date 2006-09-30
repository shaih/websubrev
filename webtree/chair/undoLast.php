<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

//exit("PARAMS_VERSION=".PARAMS_VERSION);
$cnnct = db_connect();
$qry = "SELECT MAX(version) FROM parameters";
$res = db_query($qry, $cnnct);
$row = mysql_fetch_row($res);
$maxVersion = $row[0];
$prev=PARAMS_VERSION-1;
$cur=PARAMS_VERSION;
$next=PARAMS_VERSION+1;

if (isset($_GET['undoLast']) && $cur>1) { // undo last change
  $qry = "UPDATE parameters SET isCurrent=1 WHERE version=$prev";
  $res=db_query($qry, $cnnct);
  if (mysql_affected_rows()>0) { // success
    $qry = "UPDATE parameters SET isCurrent=0 WHERE version>=$cur";
    db_query($qry, $cnnct);
  }
  header("Location: index.php");
  exit();
}

if (isset($_GET['redoLast']) && $cur<$maxVersion) { // redo last change
  $qry = "UPDATE parameters SET isCurrent=1 WHERE version=$next";
  $res=db_query($qry, $cnnct);
  if (mysql_affected_rows()>0) { // success
    $qry = "UPDATE parameters SET isCurrent=0 WHERE version<=$cur";
    db_query($qry, $cnnct);
  }
  header("Location: index.php");
  exit();
}

if ($cur==1 & $maxVersion==1) {
  exit("<h1>No Undo/Redo Information Available</h1>");
}

$links= show_chr_links(4);

if ($cur>1)
     $bkButton = '<input type=submit name=undoLast value="Undo Last Change">';
else $bkButton = '';

if ($cur<$maxVersion)
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
page (or redo the last change that you un-did from this page).
Note that the only modifications that can be un-done from this page are
to "system parameters" (such as deadlines, closing the submissions, etc.).
In particular you cannot undo things like setting status of individual
submissions or setting the discuss flags of individual PC members.<br/>
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
