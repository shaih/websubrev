<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, Tal Moran
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

// Check for Zend Framework
if (HAVE_ZEND_PDF) {
  require_once 'Zend/Pdf.php';
}

// Use Zend PDF Framework to stamp without losing ToC and hyperlinks
function stampPDF($pdfFile, $stampString) {
  // Use FONT_TIMES_BOLD for header
  $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_TIMES_BOLD);

  $pdf = Zend_Pdf::load($pdfFile);

  foreach ($pdf->pages as $pdfPage) {
    // Apply font
    $pdfPage->setFont($font, 12);

    $xAxis = 70; $yAxis = $pdfPage->getHeight() - 22; // Where to put the stamp

    $pdfPage->drawText($stampString, $xAxis, $yAxis, 'UTF-8');
  }
  #
  // Save document to same file (just appending new data)
  #
  $pdf->save($pdfFile, true);
}

// Backup a submitted file. If a backup file already exists,
// a new numbered backup file will be created.
// Returns TRUE if backup succeeded and FALSE otherwise.
function createBackup($subFile, $subId, $format, $backupDir) 
{
  if (!file_exists("$backupDir/{$subId}.unstamped.{$format}"))
    return copy($subFile, "$backupDir/{$subId}.unstamped.{$format}");

  $num = 1;
  $maxnum = 1000;

  while (($num < $maxnum) && file_exists("$backupDir/{$subId}.unstamped-{$num}.{$format}")) 
	++$num;

  if ($num >= $maxnum)
	return FALSE;

  return copy($subFile, "$backupDir/{$subId}.unstamped-{$num}.{$format}");
}


// Stamp a submission
function stampSubmission($subId, $format)
{
  $confName = CONF_SHORT.' '.CONF_YEAR;
  $saveDir = getcwd();
  chdir(SUBMIT_DIR);

  $subFile = "{$subId}.{$format}";
  $tmpFile = "scratch/{$subId}.ps";
  $tmpStmp = "scratch/{$subId}.stamped.ps";

  $return_var = 0;
  $output_lines = array();

  $stampString = "Submission number $subId to $confName: DO NOT DISTRIBUTE!";


  // Backup the "unstamped" file
  if (!createBackup($subFile, $subId, $format, "backup")) {
	// We won't risk stamping if backup failed!
	error_log("Backup failed for $subId; leaving unstamped!");
	return cleanExit(1,$saveDir);
  }

  if (HAVE_ZEND_PDF && (strtoupper($format)=='PDF')) {
    // Use cleaner PDF stamping mechanism

    try {
      // Stamp the PDF
      stampPDF($subFile, $stampString);

      return cleanExit(0,$saveDir);
    } catch(Exception $e) {
      // Stamping failed (probably due to unsupported PDF version)
      // Fall back to previous stamping method

      //error_log("Zend PDF stamp failed for $subId: ".$e);
    }
  }

  if (file_exists($tmpFile)) unlink($tmpFile);  // just in case
  // Conver PDF to PS if needed
  if (strtoupper($format)=='PDF') {
    //    echo "trying pdftops $subFile $tmpFile<br/>\n";
    $ret=exec("pdftops $subFile $tmpFile", $output_lines, $return_var);
    if ($ret===false || $return_var!=0) { // try again with pdf2ps
      //      echo "trying pdf2ps $subFile $tmpFile<br/>\n";
      $ret=exec("pdf2ps $subFile $tmpFile", $output_lines, $return_var);
    }
    if ($ret===false || $return_var!=0) { // try again with acroread -toPostScript
      $ret=exec("acroread -toPostScript $subFile scratch", $output_lines, $return_var);
    }
  }
  // Or just copy if it is already in PS format
  elseif (strtoupper($format)=='PS')
    $ret = copy($subFile,$tmpFile);
  else return cleanExit(-1,$saveDir);  // don't know how to stamp other things

  if ($ret===false || $return_var!=0) // cannot convert/copy
    return cleanExit((($return_var!=0)? $return_var : -2),$saveDir);

  /*** Manipulate the PS file, adding a stamp to each page ***/
  $fin = fopen($tmpFile, "rb"); // open for read

  if (file_exists($tmpStmp)) unlink($tmpStmp);  // just in case
  $fout = fopen($tmpStmp,"xb"); // create and open for write
  if (!$fin || !$fout) {        // failed to open a file
    return cleanExit(-3,$saveDir,$fin,$fout);
  }

  $xAxis = 70; $yAxis = 770;    // Where to put the stamp

  // First phase: read until you see '%!'
  while (!feof($fin)) {
    $buffer = fgets($fin, 4096);
    if (substr($buffer,0,1)=='%' && substr($buffer,1,1)=='!')
      break;
  }
  // Second phase: Read and copy until after all the lines that begin with %%
  while (!feof($fin)) {
    fwrite($fout,$buffer);
    $buffer = fgets($fin, 4096);
    // If A4 format, put the stamp a little higher:
    // We identify A4 by the line %%BoundingBox: 0 0 ... 842
    if (substr($buffer,0,19)=='%%BoundingBox: 0 0 ' && substr($buffer,23,3)=='842')
      $yAxis = 820;
    elseif (substr($buffer,0,1)!='%' || substr($buffer,1,1)!='%') {
      // The PS stamp to insert into the file
      $header =<<<EndMark

/\@show\@page {showpage} bind def

/showpage {
    save
        initmatrix
        /Times-Roman findfont 12 scalefont setfont
        $xAxis $yAxis moveto
        ($stampString) show
    restore
    \@show\@page
%    /showpage {\@show\@page} bind def
} def

EndMark;
      fwrite($fout,$header);
      break;
    }
  }
  // Third phase: copy everything else
  while (!feof($fin)) {
    fwrite($fout,$buffer);
    $buffer = fgets($fin, 4096);
  }
  fclose($fin);
  unlink($tmpFile);
  if (!empty($buffer)) fwrite($fout,$buffer);
  fclose($fout);

  
  // backup already exists, delete original to prevent problem with
  // rename.
  unlink($subFile); 

  // Convert back from PS to PDF if needed ...
  if (strtoupper($format)=='PDF') {
    $ret=exec("ps2pdf {$tmpStmp} {$subFile}", $output_lines, $return_var);
    if ($ret===false || $return_var!=0) { // try again with pstopdf
      $ret=exec("pstopdf {$tmpStmp} {$subFile}", $output_lines, $return_var);
    }
    unlink($tmpStmp);
    if ($ret===false || $return_var!=0){ // cannot convert: recover from backup
      rename(SUBMIT_DIR."/backup/{$subId}.unstamped.{$format}",$subFile);
      return cleanExit((($return_var!=0)? $return_var : -4),$saveDir);
    }
  }
  else // ... or just move back the PS file
    rename($tmpStmp,$subFile);

  return cleanExit(0,$saveDir);
}

function cleanExit($errCode, $saveDir=NULL, $fd1=NULL, $fd2=NULL)
{
  if (!empty($saveDir)) chdir($saveDir);
  if (isset($fd1)) fclose($fd1);
  if (isset($fd2)) fclose($fd2);
  return $errCode;
}

?>
