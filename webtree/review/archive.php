<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';

$cnnct = db_connect();

$revId = $pcMember[0];

if(!(isset($_POST['download'])
     && is_array($_POST['download'])))
  exit("<h1>No Files Given</h1>");

foreach($_POST['download'] as $id) {
  if(!ctype_digit($id))
    exit("<h1>Invalid SubId given</h1>");
}

$qry = "SELECT s.subId, s.format FROM submissions s
   LEFT JOIN assignments a ON s.subId = a.subId
   WHERE !(a.revId =".$revId." AND a.assign = -1) AND s.subId IN(".
  implode(", ",$_POST['download']).
  ") GROUP BY s.subId";

$res = db_query($qry, $cnnct);

$files = array();

$path = sha1(uniqid(rand()).mt_rand());
$path = "/tmp/".alphanum_encode(substr($path, 0, 15)).".tgz";  // "compress" a bit

require 'Archive/Tar.php';

$tar_object = new Archive_tar($path, true);
$tar_object->setErrorHandling(PEAR_ERROR_PRINT, "%s<br />\n");

chdir(SUBMIT_DIR);

while($row = mysql_fetch_assoc($res)) {
  $filename = $row['subId'].".".$row['format'];
  if(!file_exists($filename))
    exit("<h1>File not found</h1>");
  
  if (!($tar_object->addModify($filename, "submissions"))) {
    error_log(date('Y.m.d-H:i:s ')."Cannot add $subName to tar file",
              3, LOG_FILE);
    exit("<h1>Failed</h1>");
  }
}

header("Content-Type: application/x-tar");
header("Content-Disposition: inline; filename=\"download.tgz\"");

function my_readfile_chunked($filename,$retbytes=true) { 
   $chunksize = 1*(1024*1024); // how many bytes per chunk 
   $buffer = ''; 
   $cnt =0; 
   // $handle = fopen($filename, 'rb'); 
   $handle = fopen($filename, 'rb'); 
   if ($handle === false) { 
       return false; 
   } 
   while (!feof($handle)) { 
       $buffer = fread($handle, $chunksize); 
       echo $buffer; 
       ob_flush(); 
       flush();
       if ($retbytes) { 
           $cnt += strlen($buffer); 
       } 
   } 
       $status = fclose($handle); 
   if ($retbytes && $status) { 
       return $cnt; // return num. bytes delivered like readfile() does. 
   } 
   return $status; 
}

if(!my_readfile_chunked($path))
  exit("<h1>Error reading file.</h1>");

unlink($path);
