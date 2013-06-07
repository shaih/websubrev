<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';

if (PERIOD!=PERIOD_REVIEW) exit("<h1>Review Site Is Not Active</h1>");

// Get assignments and reviews
if (isset($_POST["setDiscussFlags"])) {
  $qry = "SELECT revId, canDiscuss FROM {$SQLprefix}committee ORDER BY revId";
  $res = pdo_query($qry);

  $stmt = $db->prepare("UPDATE {$SQLprefix}committee SET canDiscuss=? WHERE revId=?");
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $revId = $row[0];
    if (isset($_POST["dscs"][$revId]) && $_POST["dscs"][$revId] != $row[1]) {
      $stmt->execute(array($_POST["dscs"][$revId], $revId));
    }
  }
}
header("Location: overview.php");
?>