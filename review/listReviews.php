<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'showReviews.php';
require 'ascii-showReviews.php';
require 'header.php';  // defines $pcMember=array(id, name, email, ...)

$bigNumber = 1000000;  // some stupid upper bound on the number of posts


if (isset($_GET['format']) && $_GET['format']=='ascii') {
  $subHeader_fnc = 'ascii_subHeader';
  $showReviews_fnc = 'ascii_showReviews';
  $showPosts_fnc = 'ascii_showPosts';
} else {
  $subHeader_fnc = 'subDetailedHeader';
  $showReviews_fnc = isset($_GET['withReviews']) ?
    'show_reviews_with_comments' : 'show_reviews';
  $showPosts_fnc = 'show_posts';
}

$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];
$pcmFlags=  (int) $pcMember[5];

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1) exit("<h1>$revName cannot discuss submissions yet</h1>");

// Get a list of submissions for which this reviewer already saw all
// the discussions/reviews. Everything else is considered "new"

$cnnct = db_connect();
$seenSubs = array();
$qry = "SELECT s.subId FROM submissions s, lastPost lp WHERE lp.revId=$revId AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
$res = db_query($qry, $cnnct);
while ($row = mysql_fetch_row($res)) { $seenSubs[$row[0]] = true; }

// Prepare the ORDER BY clause
list($order, $heading,$flags) = order_clause();

// The default order is by number, and we also use the same to break
// ties in other ordering
if (empty($order)) { $order = 'subId, r.revId'; $heading='number';}
else               { $order .= ', subId, r.revId'; }

// prepare the query: first get the submission details
$qry = "SELECT s.subId subId, s.title title, 
       UNIX_TIMESTAMP(s.lastModified) lastModif, s.status status, 
       s.avg avg, s.wAvg wAvg, (s.maxGrade-s.minGrade) delta,
       a.assign assign, a.watch watch,\n";

// Next the reviwe details
$qry .="       r.revId revId, r.confidence conf, r.score grade, 
       UNIX_TIMESTAMP(r.lastModified) modified, c.name PCmember,
       r.subReviewer subReviewer";
for ($i=0; $i<count($criteria); $i++) {
  $qry .= ",\n       r.grade_{$i} grade_{$i}";
}

if (isset($_GET['withReviews'])) { // get also the comments
  $flags |= 64;
  $qry .= ",\n       r.comments2authors cmnts2athr,
       r.comments2committee cmnts2PC";
  if ($revId==CHAIR_ID) $qry .= ",\n       r.comments2chair cmnts2chr";
}

// Next comes the JOIN conditions (not for the faint of heart)
$qry .= "\n  FROM submissions s
       LEFT JOIN reports r ON r.subId=s.subId
       LEFT JOIN committee c ON c.revId=r.revId
       LEFT JOIN assignments a ON a.revId='$revId' AND a.subId=s.subId\n";

// Finally the WHERE and ORDER clauses
$qry .= "  WHERE status!='Withdrawn'\n";
if (isset($_GET['watchOnly'])) {
  $qry .= " AND a.watch=1\n";
  $flags |= 16;
}
if (isset($_GET['ignoreWatch'])) {
  $flags |= 32;
}
$qry .= "  ORDER BY $order";

$res = db_query($qry, $cnnct);

// Store the reviews in
$subs = array();
$watch = array();
$others = array();
$currentId = -1; // make sure that it does not equal $row['subId'] below
while ($row = mysql_fetch_assoc($res)) {
  if ($row['assign']==-1) continue; // don't show conflict-of-interest subs

  //  print "<pre>".print_r($row, true)."</pre>";

  if ($row['subId'] != $currentId) { // A new submission record
    $currentId = $row['subId'];
    $nohtingNew = isset($seenSubs[$currentId]);

    // Record the details of this new submission (including statistics)
    $subs[$currentId] = array('reviews'   => array(),
			'subId'     => $row['subId'],
			'title'     => $row['title'],
			'lastModif' => $row['lastModif'], 
			'status'    => $row['status'],
			'avg'       => $row['avg'],
			'wAvg'      => $row['wAvg'],
			'delta'     => $row['delta'],
			'hasNew'    => (!$nohtingNew) );

    // Store the newly found submission in one of the lists
    if (!isset($_GET['ignoreWatch']) && $row['watch']==1)
      $watch[] =& $subs[$currentId];
    else
      $others[] =& $subs[$currentId]; 
  } // end new submission

  // Record the details of the current review in the submission's review list
  if (isset($row['PCmember'])) {
    $review = array('subId'       => $row['subId'],
		    'revId'       => $row['revId'],
		    'PCmember'    => $row['PCmember'],
		    'subReviewer' => $row['subReviewer'],
		    'modified'    => $row['modified'],
		    'conf'        => $row['conf'],
		    'grade'       => $row['grade']);
    for ($i=0; $i<count($criteria); $i++)
      $review["grade_{$i}"] = $row["grade_{$i}"];

    if (isset($_GET['withReviews'])) { // get also the comments
      $review["cmnts2athr"] = $row["cmnts2athr"];
      $review["cmnts2PC"] = $row["cmnts2PC"];
      if ($revId==CHAIR_ID) $review["cmnts2chr"] = $row["cmnts2chr"];
    }
    array_push($subs[$currentId]['reviews'], $review);
  }
}

if (isset($_GET['withDiscussion'])) { // get also the discussions
  $flags |= 128;
  $qry = "SELECT 0 AS depth, postId, parentId, subject, comments, 
     UNIX_TIMESTAMP(whenEntered) whenEntered, pc.name name, subId
  FROM posts pst, committee pc
  WHERE pc.revId=pst.revId
  ORDER BY subId, whenEntered";
  $res = db_query($qry, $cnnct);
  while ($row = mysql_fetch_array($res)) {
    $subId = (int) $row['subId'];
    if (!isset($subs[$subId])) continue; // make sure there is such submission

    if (!isset($subs[$subId]['posts']))  // an array for the discussion
      $subs[$subId]['posts'] = array();
    array_push($subs[$subId]['posts'], $row);

    //    print '<pre>$subs['.$subId."]=".print_r($subs[$subId])."</pre>"; //debug
  }
  
}

// Display results to the user

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head>
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<style type="text/css">
h1 {text-align: center; }
tr { vertical-align: top; }
.lightbg { background-color: rgb(245, 245, 245); } 
.darkbg { background-color: lightgrey; } 
.hidden {display:none;}
.shown {display:inline;}
td.ctr { text-align: center;} 
div.fixed { font: 14px monospace; width: 90%;}
</style>

<title>Review List (by $heading)</title>
</head>
<body>
$links
<hr />
<h1>Review List (by $heading)</h1>
<h2 style="text-align: center;">$revName</h2>

EndMark;

if (count($watch)>0) {
  print "<h2>Submissions on {$revName}'s Watch List:</h2>\n";
  foreach ($watch as $sub) {
    $subHeader_fnc($sub, $revId);
    $showReviews_fnc($sub['reviews'], $revId);
    if (isset($_GET['withDiscussion']) && is_array($sub['posts'])) { 
      $showPosts_fnc($sub['posts'], $sub['subId'], false, $bigNumber);
    }
    $otherTtl = true;
  }
}
else { $otherTtl = false; }

if (count($others)>0) {
  if($otherTtl) print "<br /><br /><h2>Other Submissions:</h2>\n";
  foreach ($others as $sub) {
    $subHeader_fnc($sub, $revId);
    $showReviews_fnc($sub['reviews'], $revId);
    if (isset($_GET['withDiscussion']) 
	&& isset($sub['posts']) && is_array($sub['posts'])) {
      $showPosts_fnc($sub['posts'], $sub['subId'], false, $bigNumber);
    }
  }
}
print <<<EndMark
<hr />
$links
</body>
</html>

EndMark;

if (isset($_GET['showRevBox'])) { // remember setting for next time
  $pcmFlags &= 0xffff00ff;
  $pcmFlags |= ($flags << 8);
  db_query("UPDATE committee SET flags=$pcmFlags WHERE revId=$revId", $cnnct);
}
/*********************************************************************/
?>
