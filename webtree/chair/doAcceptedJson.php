<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';
if (PERIOD<PERIOD_FINAL) die("<h1>Too early to produce accepted papers list</h1>");

header("Content-Type: application/json");
header("Content-Disposition: inline; filename=papers.json");

// Prepare an array of accepted submissions.
$qry = "SELECT title, authors, affiliations, category, keyWords
        FROM {$SQLprefix}submissions WHERE status='Accept'";
$res = pdo_query($qry);
$subArray = $res->fetchAll(PDO::FETCH_ASSOC);

$papers = array();
// Note that we want UTF-8 here, not HTML entities.
foreach($subArray as $sb) {
  $paper = array('title' => $sb['title'],
                 'authors' => $sb['authors']);
  $affiliations = $sb['affiliations'];
  if (!empty($affiliations)) {
    $paper['affiliations'] = $affiliations;
  }
  $category = $sb['category'];
  if (empty($category)) {
    $category='Uncategorized';
  }
  $paper['category'] = $category;
  $keyWords = $sb['keyWords'];
  if (!empty($keyWords)) {
     $paper['keyWords'] = $keyWords;
  }
  $papers[] = $paper;
}
$data = array('acceptedPapers' => $papers);
echo json_encode($data);
?>
