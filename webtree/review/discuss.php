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

if (isset($_GET['subId'])) { $subId = (int) trim($_GET['subId']); }
else exit("<h1>No Submission specified</h1>");

// Check that this reviewer is allowed to discuss submissions
if (!$disFlag || ($disFlag == 2 && !has_reviewed_paper($revId, $subId)))
  exit("<h1>$revName cannot discuss submissions yet</h1>");

// Make sure that this submission exists and the reviewer does not have
// a conflict with it. Also get the last time that this reviewer viewed
// the discussion on this submission.

$cnnct = db_connect();
$qry = "SELECT s.subId subId, s.title title, s.rebuttal rebuttal,
      UNIX_TIMESTAMP(s.lastModified) lastModif, s.status status,
      s.avg avg, s.wAvg wAvg, VAR_POP(r.score) delta,
      lp.lastSaw lastSaw, a.assign assign, lp.lastVisited lastVisited, s.flags flags
    FROM submissions s 
      LEFT JOIN assignments a ON a.revId='$revId' AND a.subId='$subId'
      LEFT JOIN lastPost lp ON lp.revId='$revId' AND lp.subId='$subId'
      LEFT JOIN reports r ON r.subId = '$subId' 
    WHERE s.subId='$subId' AND (status!='Withdrawn' OR (s.flags & ".FLAG_IS_GROUP."))
	GROUP BY subId";
$res = db_query($qry, $cnnct);
if (!($submission = mysql_fetch_assoc($res))
    || $submission['assign']==-1) {
  exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

$lastSaw = isset($submission['lastSaw']) ? (int)$submission['lastSaw'] : 0;
$lastVisited = $submission['lastVisited'];
if(has_group_conflict($revId, $submission['title'])) {
	exit("<h1>Submission does not exist or reviewer has a conflict</h1>");
}

// Get the reviews for this subsmission

$grades = "r.confidence conf, r.score score";
$chrCmnts = is_chair($revId) ? "r.comments2chair cmnts2chr, " : "";
$qry = "SELECT r.subId subId, r.revId revId, c.name PCmember,
       r.subReviewer subReviewer, r.confidence conf, r.score score,  
       r.comments2authors cmnts2athr, r.comments2committee cmnts2PC, $chrCmnts
       UNIX_TIMESTAMP(r.lastModified) modified, r.attachment
    FROM reports r, committee c WHERE  r.revId=c.revId AND r.subId=$subId
    GROUP BY revId
    ORDER BY modified";
$qryAvg = "SELECT r.revId revId, AVG(r.confidence) avgConf, AVG(r.score) avgScore 
    FROM reports r, committee c WHERE r.revId = c.revId
	GROUP BY revId";
$qry2 = "SELECT revId, gradeId, grade FROM auxGrades WHERE subId=$subId";
$qry2Avg = "SELECT revId, gradeId, avg(grade) avgGrade 
			FROM auxGrades
			GROUP BY gradeId, revId";
$res = db_query($qry, $cnnct);
$avgRes = db_query($qryAvg, $cnnct);
$auxRes = db_query($qry2, $cnnct);
$aux2Res = db_query($qry2Avg, $cnnct);
// store the auxiliary grades in a more convenient array
$auxGrades = array();
while ($row = mysql_fetch_row($auxRes)) {
  $rId = (int) $row[0];
  $gId = (int) $row[1];
  $auxGrades[$rId][$gId] = isset($row[2]) ? ((int) $row[2]) : NULL;
}
$avgGrades = array();
while ($row = mysql_fetch_assoc($avgRes)) {
	$rId = (int) $row['revId'];
	$avgGrades[$rId]['avgScore'] = $row['avgScore'];
	$avgGrades[$rId]['avgConf'] = $row['avgConf'];
}
$avgAuxGrades = array();
while ($row = mysql_fetch_assoc($aux2Res)) {
	$rId = (int) $row['revId'];
	$gId = (int) $row['gradeId'];
	$avgAuxGrades[$rId][$gId] = $row['avgGrade'];
}
// store reports for this submission in an array
$reports = array();
while ($row = mysql_fetch_assoc($res)) {
  $rId = (int) $row['revId'];
  for ($i=0; $i<count($criteria); $i++) {
    $row["grade_{$i}"] = $auxGrades[$rId][$i];
   	$row["avgGrade_{$i}"] = $avgAuxGrades[$rId][$i]; 
  }
  $row['avgScore'] = $avgGrades[$rId]['avgScore'];
  $row['avgConf'] = $avgGrades[$rId]['avgConf'];
  $reports[] = $row;
}



// Get the posts for this subsmission. (The depth field is initialized
// to zero and is later set by the logic in make_post_array().)

$qry = "SELECT 0 AS depth, postId, parentId, subject, comments, 
        UNIX_TIMESTAMP(whenEntered) whenEntered, pc.name name,
        pst.revId=$revId mine
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

$res1 = db_query("SELECT c.name FROM committee c JOIN assignments a ON a.revId = c.revId WHERE a.subID = '$subId' AND a.assign=1", $cnnct);
$reviewers = "";
while($row = mysql_fetch_array($res1)){
  $reviewers .= $row['name']. ", ";	
}
$reviewers = substr($reviewers,0,-2);
$headers = '';
if($submission['flags'] & FLAG_IS_GROUP) {
	$headers = '<h1>Discussion of Group '.get_sub_links($submission['title']).'</h1>';
} else {
	$headers = '<h1>Reviews/Discussion of Submission '.$subId.'</h1>
<h5> Reviewers assigned to submisssion '.$subId.' : '.$reviewers.'</h5>';
}

$js_params = "window.params = {};";
if(isset($_GET['allowEdit'])) {
  $js_params .= "window.params.edit = true;";
}

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

<script type="text/javascript">
$js_params
</script>

<script type="text/javascript" src="$JQUERY_URL"></script>
<script type="text/javascript" src="../common/ui.js"></script>
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
<div id="sub-id" data-id="$subId"></div>
$links
<hr />
$headers

EndMark;
if(!($submission['flags'] & FLAG_IS_GROUP)) {
	subDetailedHeader($submission, $revId, false);
}

if (count($changeLog)>0) {
  $chngeLogHtml = '<a class=tooltips href="#" onclick="return false;" style="  font-family: Helvetica; font-weight: bold; background-color: #ffd; color: blue; border: 1px blue solid; z-index:1;">' . "Recent activity<span>\n";
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

EndMark;
}
print $chngeLogHtml;
show_reviews($reports, $revId);
if (is_array($posts) && count($posts)>0) {
  print '<h2 style="text-align: left;"><a name="discuss">Discussion</a></h2>';
}
else {
	print "<br/>\n";
}
if (PERIOD <= PERIOD_REVIEW) {
  	$active = 'true';
} else {  	
	$active = 'false';
}
print "<div class=\"discussion\" data-width=\"725\" data-subId=\"$subId\" data-threaded=\"$threaded\" data-active=\"$active\"></div>";
  //show_posts($posts, $subId, $threaded, $lastSaw, $pageWidth);

if (PERIOD <= PERIOD_REVIEW) {
print <<<EndMark
<a name="endDiscuss"></a>
<big><b>Start a new discussion thread:</b></big><br />
<form class="new-thread" enctype="multipart/form-data" method="post">
<span>Subject:</span>
  <input style="width: 640px;" type="text" name="subject"></td>
  <textarea name="comments" style="width: 100%;" rows="9">
  </textarea>
  <br />
  <button class="post-btn" type="button">Post Comment</button>
</form>

EndMark;
}
print "<hr size=4 noshade=noshade/>\n";

$rebuttal = trim($submission['rebuttal']);
if (!empty($rebuttal)) {
  $rebuttal = nl2br(htmlspecialchars($rebuttal));
  print <<<EndMark
<h2>Author&apos;s Rebuttal</h2>
<p>
$rebuttal
</p>
<hr size=4 noshade=noshade/>
EndMark;
}

show_reviews_with_comments($reports, $revId);

exit("<hr />\n{$links}\n</body>\n</html>\n");
/********************************************************************/
?>
