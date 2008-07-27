<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file

header("Content-Type: text/html");
$file = SUBMIT_DIR."/final/guidelines.html";
if (!file_exists($file) || !readfile($file)) {
  exit("<h1>No Instructions Available</h1>");
}
?>
