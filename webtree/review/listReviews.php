<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'showReviews.php';
require 'showReviewsAscii.php';
require 'header.php';  // defines $pcMember=array(id, name, email, ...)

$bigNumber = 1000000;  // some stupid upper bound on the number of posts

function show_rebuttal($rebuttal) 
{
  $rebuttal = nl2br(htmlspecialchars($rebuttal));
  print <<<EndMark
<div style="width: 90%; margin-left: auto; margin-right: auto; border:1px solid;">
<b>Author&apos;s Rebuttal</b>
<p>$rebuttal</p>
</div>
EndMark;
}

function ascii_show_rebuttal($rebuttal) 
{
  $rebuttal = htmlspecialchars(wordwrap($rebuttal));
  print <<<EndMark
<pre>**Authorss rebuttal:**
$rebuttal
___________________________________________________________________________
</pre>
EndMark;
}

if (isset($_GET['format']) && $_GET['format']=='ascii') {
  $subHeader_fnc = 'ascii_subHeader';
  $showReviews_fnc = 'ascii_showReviews';
  $showPosts_fnc = 'ascii_showPosts';
  $showRebuttal_fnc = 'ascii_show_rebuttal';
} else {
  $subHeader_fnc = 'subDetailedHeader';
  $showReviews_fnc = isset($_GET['withReviews']) ?
    'show_reviews_with_comments' : 'show_reviews';
  $showPosts_fnc = 'show_posts';
  $showRebuttal_fnc = 'show_rebuttal';
}

$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];
$pcmFlags=  (int) $pcMember[5];
$isChair = is_chair($revId);

// Check that this reviewer is allowed to discuss submissions
if ($disFlag != 1 && !has_reviewed_anything($revId)) exit("<h1>$revName cannot discuss submissions yet</h1>");

// Get a list of tags that this reviewer can see
$tags = array();
$qry = "SELECT tagName,subId FROM {$SQLprefix}tags WHERE type IN ($revId,0) "
  . ($isChair? 'OR type<=0 ' : '') . 'ORDER BY subId,tagName';
$res = pdo_query($qry);
while ($row = $res->fetch(PDO::FETCH_NUM)) {
  $tag = $row[0];
  $subId = $row[1];
  if (!isset($tags[$subId])) $tags[$subId] = array($tag);
  else                       $tags[$subId][] = $tag;
}

// Get a list of submissions for which this reviewer already saw all
// the discussions/reviews. Everything else is considered "new"

$seenSubs = array();
$qry = "SELECT s.subId FROM {$SQLprefix}submissions s, {$SQLprefix}lastPost lp WHERE lp.revId=? AND s.subId=lp.subId AND s.lastModified<=lp.lastVisited";
$res = pdo_query($qry, array($revId));
while ($row = $res->fetch(PDO::FETCH_NUM)) { $seenSubs[$row[0]] = true; }

// Prepare the ORDER BY clause (order_clause() defined in revFunctions.php)
list($order, $heading,$flags) = order_clause();

// The default order is by number, and we also use the same to break
// ties in other ordering
if (empty($order)) { $order = 'subId'; $heading='number';}
else               { $order .= ', subId'; }

// prepare the query: first get the submission details
$qry = "SELECT s.subId subId, s.title title, 
       UNIX_TIMESTAMP(s.lastModified) lastModif, s.status status, 
       s.avg avg, s.wAvg wAvg, s.minGrade minGrade, s.maxGrade maxGrade,
       maxGrade-minGrade delta, s.flags flags,
       a.assign assign, a.watch watch,\n";

// Next the review details
$qry .="       r.revId revId, r.confidence conf, r.score score, 
       UNIX_TIMESTAMP(r.lastModified) modified, c.name PCmember,
       r.subReviewer subReviewer";
if (isset($_GET['withReviews'])) { // get also the comments
  $flags |= 64;
  $qry .= ",\n       r.comments2authors cmnts2athr,
       r.comments2committee cmnts2PC";
  if ($isChair) $qry .= ",\n       r.comments2chair cmnts2chr";
}
$qry .= ",s.contact contact, s.rebuttal rebuttal,SHA1(CONCAT('".CONF_SALT."',s.subId,r.revId)) alias";

// Next comes the JOIN conditions (not for the faint of heart)
//You said it! -AU
$qry .= "\n  FROM {$SQLprefix}submissions s
       LEFT JOIN {$SQLprefix}reports r ON r.subId=s.subId
       LEFT JOIN {$SQLprefix}committee c ON c.revId=r.revId
       LEFT JOIN {$SQLprefix}assignments a ON a.revId=? AND a.subId=s.subId ";

// Finally the WHERE and ORDER clauses
$qry .= "  WHERE (status!='Withdrawn' OR (s.flags & ".FLAG_IS_GROUP." ))\n";
if (isset($_GET['watchOnly'])) {
  $qry .= " AND a.watch=1\n";
  $flags |= 16;
}
if (isset($_GET['ignoreWatch'])) {
  $flags |= 32;
}
$qry .= "  GROUP BY subId, revId ORDER BY $order,alias";
$res = pdo_query($qry, array($revId));

// Get also the auxiliary grades
$qry2 = "SELECT z.subId, z.revId, z.gradeId, z.grade"
     . " FROM {$SQLprefix}auxGrades z, {$SQLprefix}submissions s"
     . " WHERE s.subId=z.subId AND s.status!='Withdrawn'"
     . " ORDER BY z.subId, z.revId, z.gradeId";
$auxRes = pdo_query($qry2);

$qryAvg = "SELECT r.revId revId, AVG(r.confidence) avgConf, AVG(r.score) avgScore FROM {$SQLprefix}reports r, {$SQLprefix}committee c WHERE r.revId = c.revId GROUP BY revId";
$avgRes = pdo_query($qryAvg);

$qry2Avg = "SELECT revId, gradeId, avg(grade) avgGrade FROM {$SQLprefix}auxGrades GROUP BY gradeId, revId";
$aux2Res = pdo_query($qry2Avg);

// store aux grades in a more convenient array
$auxGrades = array();
while ($row = $auxRes->fetch(PDO::FETCH_NUM)) {
  $sId = (int) $row[0];
  $rId = (int) $row[1];
  $gId = (int) $row[2];
  $auxGrades[$sId][$rId][$gId] = isset($row[3]) ? ((int) $row[3]) : NULL;
}
while ($row = $avgRes->fetch(PDO::FETCH_ASSOC)) {
  $rId = (int) $row['revId'];
  $avgGrades[$rId]['avgScore'] = $row['avgScore'];
  $avgGrades[$rId]['avgConf'] = $row['avgConf'];
}
$avgAuxGrades = array();
while ($row = $aux2Res->fetch(PDO::FETCH_ASSOC)) {
  $rId = (int) $row['revId'];
  $gId = (int) $row['gradeId'];
  $avgAuxGrades[$rId][$gId] = $row['avgGrade'];
}

// Store the reviews in a tables
$subs = array();
$watch = array();
$others = array();
$currentId = -1; // make sure that it does not equal $row['subId'] below
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
  if ($row['assign']<0 || has_group_conflict($revId, $row['title']))
    continue;                  // don't show conflict-of-interest subs
  //  print "<pre>".print_r($row, true)."</pre>";

  if ($row['subId'] != $currentId) { // A new submission record
    // kludge: if the reviewer cannot discuss this submission,
    // don't show it at all
    if ($disFlag==2 && $row['assign']==1 && 
	!has_reviewed_paper($revId, $row['subId']))
	continue;

    $currentId = $row['subId'];
    $nohtingNew = isset($seenSubs[$currentId]);
    $subs[$currentId] = array('reviews'   => array(),
			'subId'     => $row['subId'],
			'title'     => $row['title'],
			'lastModif' => $row['lastModif'], 
			'status'    => $row['status'],
			'avg'       => $row['avg'],
			'wAvg'      => $row['wAvg'],
			'delta'     => $row['delta'],
			'minGrade'  => $row['minGrade'],
			'maxGrade'  => $row['maxGrade'],
			'flags'     => $row['flags'],
			'contact'   => $row['contact'],
			'hasNew'    => (!$nohtingNew) );

    if (isset($tags[$currentId]))
      $subs[$currentId]['tags'] = $tags[$currentId];
    else $subs[$currentId]['tags'] = array();

    if (isset($_GET['withReviews']) && ($row['flags']&FLAG_FINAL_REBUTTAL))
      $subs[$currentId]['rebuttal'] = $row['rebuttal'];

    if (isset($_GET['ignoreWatch']) && $row['watch']==1)
      $subs[$currentId]['watch'] = 1;

    // Store the newly found submission in one of the lists
    if (!isset($_GET['ignoreWatch']) && $row['watch']==1)
      $watch[] =& $subs[$currentId];
    else 
      $others[] =& $subs[$currentId];
  } // end new submission

  // Record the details of the current review in the submission's review list
  if (isset($row['PCmember'])) {
    $sId = $row['subId'];
    $rId = $row['revId'];
    $review = array('subId'       => $sId, 
		    'revId'       => $rId, 
		    'PCmember'    => $row['PCmember'],
		    'subReviewer' => $row['subReviewer'],
		    'modified'    => $row['modified'],
		    'conf'        => $row['conf'],
		    'score'       => $row['score'],
		    'avgScore'    => $avgGrades[$rId]['avgScore'],
		    'avgConf'     => $avgGrades[$rId]['avgConf'],
		    'alias'       => $row['alias']);
    for ($i=0; $i<count($criteria); $i++) {
      $review["grade_{$i}"] = $auxGrades[$sId][$rId][$i];
      $review["avgGrade_{$i}"] = $avgAuxGrades[$rId][$i];
    }

    if (isset($_GET['withReviews'])) { // get also the comments
      $review["cmnts2athr"] = $row["cmnts2athr"];
      $review["cmnts2PC"] = $row["cmnts2PC"];
      if ($isChair) $review["cmnts2chr"] = $row["cmnts2chr"];
    }
    array_push($subs[$currentId]['reviews'], $review);
  }
}

if (isset($_GET['withDiscussion'])) { // get also the discussions
  $flags |= 128;
  $qry = "SELECT 0 AS depth, postId, parentId, subject, comments, 
     UNIX_TIMESTAMP(whenEntered) whenEntered, pc.name name, subId
  FROM {$SQLprefix}posts pst, {$SQLprefix}committee pc
  WHERE pc.revId=pst.revId
  ORDER BY subId, whenEntered";
  $res = pdo_query($qry);
  while ($row = $res->fetch()) {
    $subId = (int) $row['subId'];
    if (!isset($subs[$subId])) continue; // make sure there is such submission

    if (!isset($subs[$subId]['posts']))  // an array for the discussion
      $subs[$subId]['posts'] = array();
    array_push($subs[$subId]['posts'], $row);

    //    print '<pre>$subs['.$subId."]=".print_r($subs[$subId])."</pre>"; //debug
  }
  
}

// Display results to the user
if ($isChair) {
  $chairExtra = 
    '<script type="text/javascript" src="setStatusFromList.js"></script>';
}
else $chairExtra = "";

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML>
<html><head><meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="../common/review.css" />
<link rel="stylesheet" type="text/css" href="../common/tooltips.css" />
<link rel="stylesheet" type="text/css" href="../common/saving.css"/>
<script type="text/javascript" src="{$JQUERY_URL}"></script>
<script type="text/javascript" src="../common/ui.js"></script>
<script type="text/javascript" src="toggleImage.js"></script>
$chairExtra
<style type="text/css">
h1 {text-align: center; }
tr { vertical-align: top; }
.lightbg { background-color: rgb(245, 245, 245); } 
.darkbg { background-color: lightgrey; } 
.hidden {display:none;}
.shown {display:inline;}
td.ctr { text-align: center;} 
div.fixed { font: 14px monospace; width: 90%;}
a.tooltips:hover span { width: 300px;}
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
  print '<div class="revList">';
  $i=1;
  foreach ($watch as $sub) {
    $subHeader_fnc($sub, $revId, true, $i++);
    $showReviews_fnc($sub['reviews'], $revId);

    if (isset($_GET['withReviews']) && !empty($sub['rebuttal']))// show rebuttal
      $showRebuttal_fnc($sub['rebuttal']);
    if (isset($_GET['withDiscussion']) && isset($sub['posts']) && is_array($sub['posts'])) { 
      print $showPosts_fnc($sub['posts'], $sub['subId'], false, $bigNumber);
    }
    $otherTtl = true;
  }
  print '</div>';
}
else { $otherTtl = false; }

if (count($others)>0) {
  if($otherTtl) print "<br /><br /><h2>Other Submissions:</h2>\n";
  print '<div class="revList">';
  $i=1;
  foreach ($others as $sub) {
    $subHeader_fnc($sub, $revId, true, $i++);
    $showReviews_fnc($sub['reviews'], $revId);
    if (isset($_GET['withReviews']) && !empty($sub['rebuttal']))// show rebuttal
      $showRebuttal_fnc($sub['rebuttal']);
    if (isset($_GET['withDiscussion']) 
	&& isset($sub['posts']) && is_array($sub['posts'])) {
      print $showPosts_fnc($sub['posts'], $sub['subId'], false, $bigNumber);
    }
  }
  print '</div>';
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
  pdo_query("UPDATE {$SQLprefix}committee SET flags=? WHERE revId=?", 
	    array($pcmFlags,$revId));
}
/*********************************************************************/
?>
