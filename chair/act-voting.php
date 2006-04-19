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

/* If chair specified parameters - write them to vote-parameter file
 *******************************************************************/
if (isset($_POST["voteParams"])) {
  $votePrms = "<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
\n";

  $voteDeadline = str_replace("\\", "\\\\", trim($_POST["voteDeadline"]));
  $voteDeadline = str_replace("'", "\\'", $voteDeadline);
  $votePrms .= "\$voteDeadline='{$voteDeadline}';\n"; 

  $voteInstructions = trim($_POST["voteInstructions"]);
  $voteInstructions = str_replace("\\", "\\\\", $voteInstructions);
  $voteInstructions = str_replace("'", "\\'", $voteInstructions);
  $votePrms .= "\$voteInstructions='{$voteInstructions}';\n";

  $voteType = ($_POST["voteType"]=='Grade') ? 'Grade' : 'Choose';
  $votePrms .= "\$voteType='{$voteType}';\n";

  if ($voteType=='Grade') {
    $voteMaxGrade = (int) trim($_POST["voteMaxGrade"]);
    if ($voteMaxGrade < 1 || $voteMaxGrade > 9) {
      $voteMaxGrade = 1;
    }
  }
  else $voteMaxGrade = 1;
  $votePrms .= "\$voteMaxGrade={$voteMaxGrade};\n";

  if ($_POST["voteBudget"]>0)
  $votePrms .= "\$voteBudget=".((int)$_POST["voteBudget"]).";\n";

  // select the submissions (or other things) that participate in the votes
  $voteOnSubmissions = (int)$_POST["voteOnSubmissions"];
  $votePrms .= "\$voteOnSubmissions = $voteOnSubmissions;\n";

  if ($voteOnSubmissions==0) {
    $voteItems = explode(';', $_POST["voteItems"]);
    $votePrms .= "\$voteTitles = array(\n";
    $i = 1; $comma = "";
    foreach ($voteItems as $vItem) {
      $vItem = trim($vItem);
      if (empty($vItem)) continue;
      $vItem = str_replace('\\', '\\\\', $vItem);
      $vItem = str_replace('\'', '\\\'', $vItem);
      $votePrms .= "$comma $i => '$vItem'";
      $i++; $comma=",";
    }
    $votePrms .= "\n);\n";
  } else if ($voteOnSubmissions==2) {
    $voteOnThese=trim($_POST["voteOnThese"]);
    if (!empty($voteOnThese)) {
      $voteOnThese = explode(',', $voteOnThese);
      for ($i=0; $i<count($voteOnThese); $i++)
	$voteOnThese[$i] = (int) trim($voteOnThese[$i]);
      $votePrms .= "\$voteOnThese='".implode(", ", $voteOnThese) ."';\n";
    }
    if (isset($_POST["voteOnAC"])) {
      $votePrms .= "\$voteOnAC = true;\n";
    }
    if (isset($_POST["voteOnMA"])) {
      $votePrms .= "\$voteOnMA = true;\n";
    }
    if (isset($_POST["voteOnDI"])) {
      $votePrms .= "\$voteOnDI = true;\n";
    }
    if (isset($_POST["voteOnNO"])) {
      $votePrms .= "\$voteOnNO = true;\n";
    }
    if (isset($_POST["voteOnMR"])) {
      $votePrms .= "\$voteOnMR = true;\n";
    }
    if (isset($_POST["voteOnRE"])) {
      $votePrms .= "\$voteOnRE = true;\n";
    }
  }
  
  // write the parameters into the vote-parameter file

  // First write to a temporary file
  $tFile = './review/voteParams.php.tmp';
  if (!($fd = fopen($tFile, 'w'))) {
    exit("<h2>Cannot create the vote-parameter file at $tFile</h2>\n");
  }
  if (!fwrite($fd, $votePrms)) {
    exit ("<h2>Cannot write into vote parameters file $tFile</h2>\n");
  }
  fclose($fd); // Close the temporary file and rename it to it's final name

  if (file_exists('./review/voteParams.bak.php'))  // just in case
    unlink('./review/voteParams.bak.php');
  if (file_exists('./review/voteParams.php'))      // store a backup copy
    rename('./review/voteParams.php', './review/voteParams.bak.php');
  if (!rename($tFile, './review/voteParams.php'))
    exit("<h2>Cannot rename the vote-parameter file</h2>\n");
}  // if (isset($_POST["voteParams"]))


/* Reset the votes table in the database
 *******************************************************************/
if (isset($_POST["voteReset"])) { db_query("DELETE FROM votes", $cnnct); }

/* Close the current vote and remove the vote-parameter file
 *******************************************************************/
if (isset($_POST["voteClose"])) {
  if (file_exists('./review/voteParams.bak.php'))  // just in case
    unlink('./review/voteParams.bak.php');
  if (file_exists('./review/voteParams.php'))      // store a backup copy
    rename('./review/voteParams.php', './review/voteParams.bak.php');
}

header("Location: voting.php");
?>
