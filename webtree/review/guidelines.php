<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=false;
$preReview=true;
require 'header.php';

// If the guidelines file exists, send it to the client
header("Content-Type: text/html");
$gdFile = SUBMIT_DIR."/guidelines.html";
if (!file_exists($gdFile) || !readfile($gdFile)) {
  exit("<h1>No Guidelines Available</h1>");
}
?>
