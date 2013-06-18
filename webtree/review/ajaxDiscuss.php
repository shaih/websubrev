<?php
/* Web Submission and Review Software
 * Written by Shai Halevi, William Blair, Adam Udi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';
require 'showReviews.php';

$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];
$threaded= (int) $pcMember[4];


if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != '1' && (!has_reviewed_paper($revId, $subId) && $disFlag == '2')) exit("<h1>$revName cannot discuss submissions yet</h1>");

$qry = "SELECT s.subId subId, s.title title, 
      UNIX_TIMESTAMP(s.lastModified) lastModif, s.status status,
      s.avg avg, s.wAvg wAvg, VAR_POP(r.score) delta,
      lp.lastSaw lastSaw, a.assign assign, lp.lastVisited lastVisited
    FROM {$SQLprefix}submissions s 
      LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=?
      LEFT JOIN {$SQLprefix}lastPost lp ON lp.revId=? AND lp.subId=?
      LEFT JOIN {$SQLprefix}reports r ON r.subId=? 
    WHERE s.subId=? AND (status!='Withdrawn' OR (s.flags & ?))
	GROUP BY subId";
$res = pdo_query($qry, array($revId,$subId,$revId,
			     $subId,$subId,$subId,FLAG_IS_GROUP));
if (!($submission = $res->fetch(PDO::FETCH_ASSOC))
    || $submission['assign']<0) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$lastSaw = isset($submission['lastSaw']) ? (int)$submission['lastSaw'] : 0;

// Get all the posts for this subsmission. The depth field is initialized
// to zero and is later set by the logic in make_post_array(). The mine
// field is set as 0/1 depending if the current reviewer made this post.
$qry = "SELECT 0 AS depth, postId, parentId, subject, comments, 
        UNIX_TIMESTAMP(whenEntered) AS whenEntered, pc.name AS name,
        (pst.revId=?) AS mine
    FROM {$SQLprefix}posts pst, {$SQLprefix}committee pc
    WHERE pst.subId=? AND pc.revId=pst.revId
    ORDER BY whenEntered";
$res = pdo_query($qry, array($revId,$subId));
$posts = array();
if ($threaded) { make_post_array($res, $posts); }
else while ($row = $res->fetch()) {
    $row['subject'] = htmlspecialchars($row['subject'], ENT_QUOTES|ENT_COMPAT);
    $row['comments'] = nl2br(htmlspecialchars($row['comments']));
    $posts[] = $row;
  }

// update the lastPost entry for this reviewer and submission
$lastPost = $lastSaw;
foreach ($posts as $p) { 
  if ($p['postId']>$lastPost) $lastPost=(int)$p['postId']; 
}

if (isset($submission['lastSaw'])) {
  pdo_query("UPDATE {$SQLprefix}lastPost SET lastSaw=?, lastVisited=NOW() WHERE revId=? AND subId=?", array($lastPost,$revId,$subId));
} else {
  pdo_query("INSERT INTO {$SQLprefix}lastPost SET revId=?, subId=?, lastSaw=?", array($revId,$subId,$lastPost));
}

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache");

echo json_encode(array('posts'=>$posts, 
                       'subId'=>$subId, 
                       'threaded'=>$threaded, 
                       'lastSaw'=>$lastSaw,
		       'is_chair'=>is_chair($revId)
                       )
                 );
