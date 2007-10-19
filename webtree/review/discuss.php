<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
  $needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)
require 'showReviews.php';
$revId = (int) $pcMember[0];
$revName = htmlspecialchars($pcMember[1]);
$revEmail= htmlspecialchars($pcMember[2]);
$disFlag = (int) $pcMember[3];
$threaded= (int) $pcMember[4];

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != '1') exit("<h1>$revName cannot discuss submissions yet</h1>");

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. Also get the last time that this reviewer viewed
// the discussion on this submission.

$cnnct = db_connect();
$qry = "SELECT s.subId subId, s.title title, 
      UNIX_TIMESTAMP(s.lastModified) lastModif, s.status status,
      s.avg avg, s.wAvg wAvg, (s.maxGrade-s.minGrade) delta,
      lp.lastSaw lastSaw, a.assign assign, lp.lastVisited lastVisited
    FROM submissions s 
      LEFT JOIN assignments a ON a.revId='$revId' AND a.subId='$subId'
      LEFT JOIN lastPost lp ON lp.revId='$revId' AND lp.subId='$subId'
    WHERE s.subId='$subId' AND status!='Withdrawn'";

$res = db_query($qry, $cnnct);
if (!($submission = mysql_fetch_assoc($res))
    || $submission['assign']==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}
$lastSaw = isset($submission['lastSaw']) ? (int)$submission['lastSaw'] : 0;
$lastVisited = $submission['lastVisited'];
$title = htmlspecialchars($submission['title']);

// Get the reviews for this subsmission

$grades = "r.confidence conf, r.score score";
$chrCmnts = ($revId == CHAIR_ID) ? "r.comments2chair cmnts2chr, " : "";
$qry = "SELECT r.subId subId, r.revId revId, c.name PCmember,
       r.subReviewer subReviewer, r.confidence conf, r.score score,
       r.comments2authors cmnts2athr, r.comments2committee cmnts2PC, $chrCmnts
       UNIX_TIMESTAMP(r.lastModified) modified, r.attachment
    FROM reports r, committee c WHERE  r.revId=c.revId AND r.subId=$subId
    ORDER BY modified";

$qry2 = "SELECT revId, gradeId, grade from auxGrades WHERE subId=$subId";
$res = db_query($qry, $cnnct);
$auxRes = db_query($qry2, $cnnct);

// store the auxiliary grades in a more convenient array
$auxGrades = array();
while ($row = mysql_fetch_row($auxRes)) {
  $rId = (int) $row[0];
  $gId = (int) $row[1];
  $auxGrades[$rId][$gId] = isset($row[2]) ? ((int) $row[2]) : NULL;
}

// store reports for this submission in an array
$reports = array();
while ($row = mysql_fetch_assoc($res)) {
  $rId = (int) $row['revId'];
  for ($i=0; $i<count($criteria); $i++)
    $row["grade_{$i}"] = $auxGrades[$rId][$i];
  $reports[] = $row;
}


// Get the posts for this subsmission. (The depth field is initialized
// to zero and is later set by the logic in make_post_array().)

$qry = "SELECT 0 AS depth, postId, parentId, subject, comments, 
        UNIX_TIMESTAMP(whenEntered) whenEntered, pc.name name
    FROM posts pst, committee pc
    WHERE pst.subId='$subId' AND pc.revId=pst.revId
    ORDER BY whenEntered";
$res = db_query($qry, $cnnct);
$posts = array();
if ($threaded) { make_post_array($res, $posts); }
else while ($row = mysql_fetch_array($res)) { $posts[] = $row; }


// update the lastPost entry for this reviewer and submission
$lastPost = $lastSaw;
foreach ($posts as $p) { 
  if ($p['postId']>$lastPost) $lastPost=(int)$p['postId']; 
}

if (isset($submission['lastSaw'])) {
  $qry = "UPDATE lastPost SET lastSaw=$lastPost, lastVisited=NOW()
  WHERE revId=$revId AND subId=$subId";
} else {
  $qry = "INSERT INTO lastPost SET revId='$revId',
  subId='$subId', lastSaw='$lastPost'";
}
db_query($qry, $cnnct);

// If this is not the first time we visit this page, get a list
// of everything that changed since we last visited

$changeLog = array();
if (isset($submission['lastSaw'])) {
  $qry = "SELECT UNIX_TIMESTAMP(entered), description FROM changeLog
  WHERE subId=$subId AND ((entered > (NOW() - INTERVAL 10 DAY))
                          OR (entered > '$lastVisited'))
  ORDER BY entered DESC";
  $res = db_query($qry, $cnnct);
  while ($row = mysql_fetch_row($res)) $changeLog[] = $row;
}

// Now we can display the results to the user
$pageWidth = 725;
$links = show_rev_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Discussion of Submission $subId</title>

<link rel="stylesheet" type="text/css" href="../common/review.css" />
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<style type="text/css">
body { width : {$pageWidth}px; }
h1, h2 {text-align: center;}
table { width: 100%; }
a.tooltips:hover span { width: 400px;}
</style>

<script type="text/javascript" language="javascript">
<!--
  function expandcollapse (postid) {
    post = document.getElementById(postid);
    if (post.className=="shown") { post.className="hidden"; }
    else { post.className="shown"; }
    return false;
}
// -->
</script>
</head>
<body>
$links
<hr />
<h1>Reviews/Discussion of Submission $subId</h1>

EndMark;

subDetailedHeader($submission, $revId, false);

if (count($changeLog)>0) {
  $chngeLogHtml = '<a class=tooltips href="#" onclick="return false;" style="border: 1px blue solid;">' . "Recent activity<span>\n";
  foreach ($changeLog as $line) {
    $when = utcDate('M-d H:i ', $line[0]);
    $chngeLogHtml .= $when . $line[1] . "<br/>\n";
  }
  $chngeLogHtml .= "</span></a>\n";
}
else $chngeLogHtml = '';

if (is_array($posts) && count($posts)>0) {
  $altview = ($threaded) ? 'UNthreaded' : 'threaded';
  print <<<EndMark
<div style="float: right;">
    <a href="toggleThreaded.php">Switch to $altview view</a>
</div>
$chngeLogHtml
<h2 style="text-align: left;"><a name="discuss">Discussion</a></h2>

EndMark;
  show_posts($posts, $subId, $threaded, $lastSaw, $pageWidth);
}
else print $chngeLogHtml . "<br/>\n";

if (!defined('CAMERA_PERIOD')) print <<<EndMark
<a name="endDiscuss"> </a>
<big><b>Start a new discussion thread:</b></big><br />
<form action="doPost.php" enctype="multipart/form-data" method="post">
<table><tbody>
<tr><td class="rjust">Subject:</td>
    <td style="text-align: left; width: 640px;">
        <input style="width: 100%;" type="text" name="subject"></td>
    <td style="width: auto;"></td>
</tr>
</tbody></table>
<textarea name="comments" style="width: 100%;" rows="9">
</textarea>
<br />
<input type="hidden" name="subId" value="$subId">
<input type="submit" value="Post Comments">
</form>

EndMark;

print "<hr size=4 noshade=noshade/>\n";

show_reviews_with_comments($reports, $revId);

exit("<hr />\n{$links}\n</body>\n</html>\n");
/********************************************************************/

// a basic "node" class to be able to do Depth-First-Search
class Node {
  var $parentIdx  =-1;
  var $childIdx   =-1;
  var $nxtSibIdx  =-1;
  var $curChldIdx =-1;
  var $level      =0;
}

// This function returns an array of posts, which is ordered by 
// thread (in a depth-first manner) and by date within each thread
function make_post_array(&$res, &$posts)
{
  // First get all the rows that the MySQL query returned, and prepare
  // a "reverse translation" table from postId to index in rows[]
  $rows = array();
  $rowIdx = array();
  $i = 1;           // index zero is reserved for the root
  while ($row = mysql_fetch_assoc($res)) {
    $pid = $row['postId'];
    $rows[$i] = $row;
    $rowIdx[$pid] = $i;
    $i++;
  }
  if (count($rows)==0) return 0; // no posts: the "depth" is zero

  // exit("<pre>".print_r($rows, true)."</pre>"); // debug

  // Now initialize a dependency graph, represented as an array of
  // nodes. (The code below depends on the parents to appear in the
  // list before their children.)
  $graph = array();
  $graph[0] = new Node;
  foreach($rows as $i => $row) {
    $p = $row['parentId'];
    if (isset($p) && isset($rowIdx[$p])) $p = $rowIdx[$p];
    else                                 $p = 0;    // child of the root

    $nd = new Node;
    $nd->parentIdx = $p;
    $prnt =& $graph[$p];

    if ($prnt->childIdx == -1) {// $i is the first child of $prnt
      $prnt->childIdx = $i;
    }
    else {                      // $prnt has "older" children
      $s = $prnt->curChldIdx;
      $graph[$s]->nxtSibIdx = $i;  // mark $i as the young sibling of $s
    }
    $prnt->curChldIdx = $i;        // make $i as the current child of $prnt

    $graph[$i] = $nd;
  }

  // run depth-first-search on the graph
  $depth = depth_first_search(0, -1, $rows, $graph, $posts);

  return $depth;
}

function depth_first_search($idx, $depth, &$rows, &$graph, &$posts)
{
  if ($idx > 0) {
    $rows[$idx]['depth'] = $depth;  // fill in the depth fields
    $posts[] = &$rows[$idx];
  }

  $node = $graph[$idx];
  $d = 0;
  if ($node->childIdx > 0) {
    $d = depth_first_search($node->childIdx, 
				$depth+1, $rows, $graph, $posts);
  }
  if ($node->nxtSibIdx > 0) {
    $depth=depth_first_search($node->nxtSibIdx, $depth, $rows, $graph, $posts);
    if ($d > $depth) $depth = $d;
  }
  return $depth;
}
?>
