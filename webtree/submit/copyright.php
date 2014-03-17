<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file

$confName = CONF_SHORT . ' ' . CONF_YEAR;
if (PERIOD<PERIOD_CAMERA)
     die("<h1>Final-version submission site for $confName is not open</h1>");

$subId = isset($_GET['subId']) ? ((int)trim($_GET['subId'])) : '';
$subPwd = isset($_GET['subPwd']) ? trim($_GET['subPwd']) : '';
$title = $authors = $copyright = $urlPrms = '';

$file = SUBMIT_DIR."/final/copyright.html";
if (!file_exists($file)) die("Cannot find copyright form");
$copyright = file_get_contents($file);

if ($subId > 0 && !empty($subPwd)) {
  $qry = "SELECT title, authors FROM {$SQLprefix}submissions WHERE subId=? AND subPwd=? AND status='Accept'";
  $res=pdo_query($qry, array($subId,$subPwd));
  if ($row=$res->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $subId;
    $subPwd = htmlspecialchars($subPwd);
    $title = htmlspecialchars($row[0]);
    $authors  = htmlspecialchars($row[1]);
    $urlPrms = "?subId=$subId&subPwd=$subPwd";
  }
  else $subId = $subPwd = '';
}
else $subId = $subPwd = '';

$substitutions = array
  ('[$title]' => $title,
   '[$confName]' => $confName,
   '[$authors]' => $authors,
   '[$date]' => date('F j, Y'),
   '[$year]' => date('Y'),
   '[$subId]' => $subId,
   '[$subPwd]' => $subPwd,
   );
foreach ($substitutions as $tag => $val) {
  $copyright = str_replace($tag, $val, $copyright);
}

if (empty($subId) || empty($subPwd)) {
  $formHTML = '<body><form accept-charset="UTF-8" action="cameraready.php" method="get">I already signed the copyright notice for submission-ID <input type="text" name="subId"> with password <input type="text" name="subPwd">. <input type="submit" value="Proceed to camera-ready upload"></form><hr/>';
  $copyright = str_replace('<body>', $formHTML, $copyright);
}
echo $copyright;
?>
