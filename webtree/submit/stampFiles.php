<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
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
        (Submission number $subId to $confName: DO NOT DISTRIBUTE!) show
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

  // Backup the "unstamped" file
  if (file_exists("backup/{$subId}.unstamped.{$format}")) 
    unlink("backup/{$subId}.unstamped.{$format}"); // just in case
  rename($subFile, "backup/{$subId}.unstamped.{$format}");

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