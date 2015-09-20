<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function subDetailedHeader($sub, $revId=0, $showDiscussButton=true, $rank=0, $showStats = true)
{
  global $discussIcon1, $discussIcon2, $pcMember, $SQLprefix;

  $subId = (int) $sub['subId'];
  $isGroup = $sub['flags'] & FLAG_IS_GROUP;
  $title =  htmlspecialchars($sub['title']);
  $sttus  = show_status($sub['status']);
  $avg   = isset($sub['avg'])?   round($sub['avg'],1) : '*';
  $wAvg  = isset($sub['wAvg'])?  round($sub['wAvg'],1) : '*';
  $delta = isset($sub['delta'])? $sub['delta'] : '*';
  $tags = isset($sub['tags'])?   $sub['tags'] : null;

  $markRead = (isset($sub['hasNew']) && $sub['hasNew'])? 1: 0;
  $disText =  $markRead? $discussIcon2 : $discussIcon1;
  $toggleText = "<a href='toggleMarkRead.php?subId=$subId&current=$markRead' class='toggleRead' title='Toggle Read/Unread' ID='toggleRead$subId' rel='$markRead'>&bull;</a>";

  $minGrade = isset($sub['minGrade']) ? round($sub['minGrade'],1) : '*';
  $maxGrade = isset($sub['maxGrade']) ? round($sub['maxGrade'],1) : '*';
  $lastModif = isset($sub['lastModif']) ? utcDate('M\&\n\b\s\p\;j H:i', ((int)$sub['lastModif'])) : '';

/* // kluggy integration with Boneh's system for sending questions to authors
  $sharedKey='abcXYZ123';
  $qryStr = 'id='.$subId             // submission ID
    .'&title='.urlencode($title)     // submission title
    .'&authEml='.urlencode($sub['contact'])// comma-separated address list
    .'&revEml='.urlencode($pcMember[2]);   // a single email address

  $token = hash_hmac('sha1', $qryStr, $sharedKey);  // authentication token
  $emlURL = "https://crypto.stanford.edu/tcc2013/view.php?$qryStr&token=$token";
*/
  if ($rank>0) {
    $extra = "<small>$rank</small>";
    $br = "<br/>";
  }
  else $extra = $br = '';
  if (isset($sub['watch']) && $sub['watch'])
    $extra .= $br.'<img src="../common/smalleye.gif" alt="W" border=0>';

  print "<br />\n<div class=\"darkbg\">\n";

  // If this is the chair: allow setting the status of this submission
  // and also provide a list of reviewers that have conflict.
  if (is_chair($revId)) {
    $qry = "SELECT c.name,a.assign FROM {$SQLprefix}assignments a, {$SQLprefix}committee c WHERE a.subId=? AND a.assign<0 AND c.revId=a.revId";
    $res = pdo_query($qry, array($subId));
    $conflictList = '';
    $gif = '../common/stop.GIF';
    while ($row = $res->fetch(PDO::FETCH_NUM)) {
      $conflictList .= '<br/>&nbsp;&nbsp;'.$row[0];
      if ($row[1]==-2) {
	$gif = '../common/pcm.gif';
	$conflictList .= '(PCM)';
      }
    }
    print "<table><tbody><tr><td class='nowrp'>";
    if (!empty($conflictList)) {
      $zIx = 2000 - $subId;
      print "<a name=\"stts{$subId}"
	. '" class=tooltips href="#" onclick="return false;" style="z-index:'
	. "$zIx;\">\n";
      print '<img alt="X" title="" height=16 src="'.$gif.'" border=0/><span>Conflicts:'.$conflictList."</span></a></td><td>\n";
    }
    else {
      print "&nbsp;&nbsp;&nbsp;&nbsp;</td><td>\n";
    }
    if(!$isGroup){
    	print setFlags_table($subId, $sub['status']); // setFlags_table in revFunctions.php
    }
    print "</td></tr></tbody></table>";
  }

  print "<table style=\"width: 100%;\"><tbody><tr style=\"vertical-align: middle;\">\n";

  if (!empty($extra))
    print "  <td class='nowrpTop'>$extra</td>\n";

  if ($showDiscussButton) {
    print '  <td class="nowrp"><span class="Discuss"><a target="_blank" href="discuss.php?subId='.$subId.'#start'.$subId."\">$disText</a>\n{$toggleText}</span></td>\n";
  }
  
    // Submission name and statistics
    print <<<EndMark
    <td class="nowrp" style="text-align: right;"><strong>$subId.</strong>&nbsp;
    </td>
    <td width="90%"><big><a href="submission.php?subId=$subId">$title</a></big></td>
EndMark;
  if($showStats && !$isGroup) {
    print <<<EndMark
    <td class="stats">
	<small>Average<br />$avg</small>
    </td>
    <td class="stats">
	<small>Weighted<br />$wAvg</small>
    </td>
    <td class="stats">
	<small>Max Score<br/>$maxGrade</small>
    </td>
   	<td class="stats">
	<small>Min Score<br />$minGrade</small>
    </td>
    
    <td id="statCode$subId" class="nowrp" style="text-align: right;">$sttus</td>
    <td class="stats"><small>$lastModif</small></td>

EndMark;
  }
  print "  </tr></tbody></table>\n";
  if (isset($tags)) 
    print showTags($tags, $subId, is_chair($revId)); // in revFunctions.php
  print "</div>\n";
}


function show_reviews(&$reviews, $revId)
{
  global $criteria;

  // All the reviews for this submission. Each review is an array of 
  // (PCmember, subReviewer, modified, conf, score, grade_0, grade_1, ...)
  if (!is_array($reviews) || count($reviews)<=0)
    return;

  $sid = isset($reviews[0]['subId']) ? intval($reviews[0]['subId']) : 0;
  if (!defined('CAMERA_PERIOD') && $sid>0) {
    $revTxt = "<small><a target=_blank title=\"Submit/Revise a report\" href=\"review.php?subId=$sid\">[review]</a></small>";
  }
  else $revTxt = "";
  $is_chair = is_chair($revId);
  print "  <div class=\"lightbg\">\n";
  if ($is_chair) {
  	print "<table cellpadding=0 cellspacing=5 border=0><tbody><tr><td>Note: Scores in parentheses are the average score that the reviewer has given in that
  			category across all reviews</td></tr></tbody></table> ";
  }
  print "  <table cellpadding=0 cellspacing=5 border=0><tbody>\n";
  print "    <tr><td></td>\n";
  print "        <td class=\"ctr\"><small>Score&nbsp;</small></td>
        <td class=\"ctr\"><small>Confidence&nbsp;</small></td>\n";
  if (is_array($criteria)) foreach ($criteria as $c) {
    print "      <td class=\"ctr\"><small>".$c[0]."</small></td>\n";
  }
  print "        <td></td><td>$revTxt</td></tr>\n";

  $nReviews = count($reviews);
  for ($j=0; $j<$nReviews; $j++) {
    $rev = $reviews[$j];
    $pcm = htmlspecialchars($rev['PCmember']);
    $subRev = $rev['subReviewer'];
    if (!empty($subRev)) $subRev = " (".htmlspecialchars($subRev).")";
    $mod = isset($rev['modified']) 
           ? utcDate('M\&\n\b\s\p\;j H:i', ((int)$rev['modified'])) : '';
    $confidence = (int) $rev['conf'];
    if ($confidence <= 0) $confidence = '*';
	if (isset($rev['avgConf'])) {    
    	$is_chair? $avgConf = '('.round($rev['avgConf'], 2).')': $avgConf='';
	} else {
		$avgConf = -1;
	}
	if ($is_chair) {
    	if ($avgConf < 0) $avgConf = "*";
	} else {
		$avgConf = '';
	}
    $score = (int) $rev['score'];
    if ($score <= 0) $score = '*';
    if (isset($rev['avgScore'])) {
    	$is_chair? $avgScore = '('.round($rev['avgScore'], 2).')': $avgScore='';
    } else {
    	$avgScore = -1;
    }
    if ($is_chair) {
    	if ($avgScore < 0) $avgScore = "*";
    } else {
    	$avgScore = '';
    }
    print "    <tr><td class=\"ctr\"><small>$mod</small></td>
      <td class=\"ctr\">$score $avgScore</td>
      <td class=\"ctr\">$confidence $avgConf</td>\n";
    $nCrits = (is_array($criteria)) ? count($criteria) : 0;
    for ($i=0; $i<$nCrits; $i++) {
      $grade = (int) $rev["grade_{$i}"];
      if (isset($rev["avgGrade_{$i}"])) {
      	$is_chair? $avgGrade = '('.round($rev["avgGrade_{$i}"], 2).')': $avgGrade='';
      } else {
      	$avgGrade = -1;
      }
      if ($grade <= 0) $grade = '*';
      if($is_chair){
      	if (substr($avgGrade, 1, -1) <= 0) $avgGrade = '(*)';
      } else {
      	$avgGrade = '';
      }
      print "      <td class=\"ctr\">$grade $avgGrade</td>\n";
    }
    print "      <td>&nbsp;</td>\n      <td><b>$pcm{$subRev}</b></td></tr>\n";
  }
  print "<tr><td></td></tr></tbody></table></div>\n\n";
}

function show_reviews_with_comments(&$reviews, $revId)
{
  global $criteria;
  $nCrits = is_array($criteria) ? count($criteria) : 0;

  if (!is_array($reviews)) return;
  $nReviews = count($reviews);
  for ($j=0; $j<$nReviews; $j++) {
    $rev = $reviews[$j];

    $sid = $rev['subId'];
    $rid = $rev['revId'];
    // If this is my review (or if I'm the chair), show a link to edit it
    if (PERIOD<PERIOD_CAMERA && ($rid==$revId || is_chair($revId)))
      $reviseTxt = ' <a target=_blank title="Revise this report" href="review.php?subId='.$sid.'&amp;revId='.$rid.'"> [revise]</a>';
    else
      $reviseTxt = ''; 

    $cmnt2athr = isset($rev['cmnts2athr'])?htmlspecialchars($rev['cmnts2athr']):'';
    $cmnt2PC   = isset($rev['cmnts2PC']) ? htmlspecialchars($rev['cmnts2PC']):'';
    $cmnt2chr  = isset($rev['cmnts2chr'])? htmlspecialchars($rev['cmnts2chr']):'';
    $score = isset($rev['score']) ? ((int) $rev['score']) : '*';
    $conf =  isset($rev['conf']) ? ((int) $rev['conf']) : '*';
    $feedback  = isset($rev['feedback'])? htmlspecialchars($rev['feedback']):'';

    $PCmember = htmlspecialchars($rev['PCmember']);
    $subRev = isset($rev['subReviewer']) ?
      " (".htmlspecialchars($rev['subReviewer']).")" : "";

    if (isset($rev['attachment'])) {
      $attachment = $rev['attachment'];
      $ext = strtoupper(file_extension($attachment));
      $attachment = "<br/><br/><a target=_blank href=\"download.php?attachment=$attachment\">$ext attachment</a>\n";
    }
    else $attachment = '';

    print <<<EndMark
<table class="lightbg" style="text-align: center;" cellspacing=0 cellpadding=5>
<tbody>
<tr>
  <td style="text-align: left; width: 400px;"><h3>$PCmember{$subRev}$reviseTxt</h3></td>
  <td></td>
  <td>Score<br/>$score</td>
  <td>Confidence<br/>$conf</td>

EndMark;

    for ($i=0; $i<$nCrits; $i++) {
      $name = $criteria[$i][0];
      $grade = isset($rev["grade_{$i}"]) ? ((int) $rev["grade_{$i}"]) : '*';
      print "  <td>$name<br />$grade</td>\n";
    }
    $lastModif = isset($rev['modified']) ? 
                 utcDate('M\&\n\b\s\p\;j\<\b\r\/\>H:i', ((int)$rev['modified'])) : '';

    print "  <td class=\"ctr\">$lastModif</td>\n</tr>\n";
    print "</tbody></table>\n";

    $reviewsShown = false;
    if (is_chair($revId) && !empty($cmnt2chr)) {
      $reviewsShown = true;
      print "<br/><b>Comments to Chair:</b><br />\n";
      print '<div class="fixed">'.nl2br($cmnt2chr).'</div>';
    }
    if (!empty($cmnt2PC)) {
      $reviewsShown = true;
      print "\n<br/><b>Comments to Committee:</b><br />\n";
      print '<div class="fixed">'.nl2br($cmnt2PC).'</div>';
    }
    if (!empty($cmnt2athr) || !empty($attachment)) {
      $reviewsShown = true;
      print "\n<br/><b>Comments to Authors:</b>\n";
      print '<div class="fixed">'.nl2br($cmnt2athr).$attachment.'</div>';
      if (is_chair($revId) && !empty($feedback)) { // show feedback to chair
	print "\n<br/><b>Author's Feedback:</b>\n";
	print '<div class="fixed">'.nl2br($feedback).'</div>';
      }
    }
    if ($reviewsShown) print "<br/>\n";
    else print "\n";
  }
}

/********************************************************************
 * $postsArray is an array of posts, each post is defined as
 * $post = array(depth, postId, parentId,
 *               subject, comments, whenEntered, reviewerName)
 ********************************************************************/
function show_posts($postsArray, $subId, $threaded=true, 
		    $lastSaw=0, $pageWidth=720, $closeLast=true)
{
  global $isChair;
  // exit("<pre>".print_r($postsArray, true)."</pre>");
  if (!is_array($postsArray)) return '';

  $html = '';
  $thrdPid = $thrdSubj = NULL;
  $newPosts = false;
  foreach($postsArray as $post) {

    if (!is_array($post)) continue;

    $depth = isset($post['depth']) ? $post['depth'] : 0;
    if ($depth > 3) $depth = 3;

    if ($depth==0) {          // first post in a thread
      if ($threaded && isset($thrdPid)) {// not the first thread, close prior one
	// print reply_to_thread($subId, $thrdPid, $thrdSubj);
	$html .= "<hr />\n\n";
      }
      // reset the thread-pid and thread-subject 
      $thrdPid = $post['postId'];
      $thrdSubj = isset($post['subject']) ? $post['subject'] : '';

    } // end if ($depth==0)

    // Display the current post
    $nextPXdepth = $PXdepth =  $depth * 20;
    $nextWidth = $width = $pageWidth - $PXdepth;
    if ($threaded && $depth < 3) {
      $depth++;  // The depth of a reply
      $nextPXdepth += 20;
      $nextWidth -= 20;
    }
    $pid = (int) $post['postId'];
    if (isset($_GET['rply2post']) && $_GET['rply2post']==$pid) { // show the reply box
      $class = "shown";
      $reply = "[<a target=\"_blank\" href=\"discuss.php?subId=$subId#p$pid\""
	. " onclick=\"return expandcollapse('replyTo$pid');\">Reply</a>]";
    }
    else {                          // initially hide the reply box
      $class = "hidden";
      $reply = "[<a target=\"_blank\" href=\"discuss.php?subId=$subId&amp;rply2post=$pid#p$pid\""
	. " onclick=\"return expandcollapse('replyTo$pid');\">Reply</a>]";
    }

    $nameWhen = htmlspecialchars($post['name']);
    if (isset($post['whenEntered']))
      $nameWhen .= ", " . utcDate('j/n H:i', ((int)$post['whenEntered']));

    $sbjct = htmlspecialchars($post['subject']);
    $startHere = '';
    if ($pid > $lastSaw) { // new post, show subject, name, date in boldface
      $nameWhen = "<b>$nameWhen</b>";
      $sbjct = "<b>$sbjct</b>";

      if (!$newPosts) { // if this the 1st new post, put a startHere mark on it
	$startHere = "<a name=\"start{$subId}\"> </a>";
	$newPosts = true;
      }
    }
    if (isset($_GET['allowEdit']) && $post['mine']) {
      $editPostLink = ' <a target=_blank href="editPost.php?postId='.$post['postId'].'"><small>[Edit] </small></a>';
    }
    else $editPostLink = '';
    if (($isChair || $post['mine']) && SEND_POSTS_BY_EMAIL)
      $sendByEmail = ' <a target="_blank" href="sendPost.php?pid='.$pid.'"><img height="14" src="../common/mail_replayall.png" title="Send this comment by email" alt="[eml]" border="1"></a>';
    else $sendByEmail = "";
    $cmnts = trim($post['comments']);
    $cmnts = (empty($cmnts)) ? '<br/>'
      : nl2br(htmlspecialchars($cmnts)).'<br/><br/>';
    $subject = isset($post['subject'])? htmlspecialchars($post['subject']) :'';
    if (strncasecmp($subject, "re:", 3)!=0)
      $subject = 'Re: ' . $subject;

    // Print the subject line
    $html .= <<<EndMark
<!-- =========================================================== -->
<div style="position: relative; left: {$PXdepth}px; width: {$width}px;">
<div style="float: right;">
$nameWhen $reply
</div>
$startHere<a name="p$pid"> </a>
&#8722;&nbsp;<span class="sbjct">$sbjct</span>{$sendByEmail}{$editPostLink}

<div style="position: relative; left: 12px; top:6px;">
$cmnts
</div></div>
<div style="position: relative; left: {$nextPXdepth}px; width: {$nextWidth}px;">
  <form accept-charset="utf-8" enctype="multipart/form-data" id="replyTo$pid"
   class="$class" action="doPost.php" method="POST"
   onsubmit="return ajaxPostComment(this);">
   Subject:&nbsp;&nbsp;<input size="80" type="text" name="subject" value="$subject">
   <br/><textarea style="width: 100%;" rows="9" name="comments"></textarea>
   <input type="submit" value="Submit Reply">
   <input type="hidden" name="subId" value="$subId">
   <input type="hidden" name="parent" value="$pid">
   <input type="hidden" name="depth" value="$depth"><br/><br/>
  </form>
</div>

EndMark;
  }
  if ($threaded && isset($thrdPid) && $closeLast) {// close last thread
    // print reply_to_thread($subId, $thrdPid, $thrdSubj);
    $html .= "<hr/>\n\n";
  }
  return $html;
}

/********************************************************************
 * $postsArray is an array of posts, each post is defined as
 * $post = array(depth, postId, parentId,
 *               subject, comments, whenEntered, name)
 * Intended to be used by Javascript for front-end.
 ********************************************************************
function show_posts_json($postsArray, $subId, $threaded=true,
                         $lastSaw=0) {
  if (!is_array($postsArray)) return json_encode(array());

  $thrdPid = $thrdSubj = NULL;
  $newPosts = false;

  return json_encode(array('posts'=>$postsArray, 'subId'=>$subId, 'threaded'=>$threaded, 'lastSaw'=>$lastSaw));
}
 ********************************************************************/


/********************************************************************
function reply_to_thread($subId, $thrdPid, $thrdSubj)
{
  if (defined('CAMERA_PERIOD')) return ''; // read-only mode: cannot reply

  if (!isset($thrdSubj)) $thrdSubj='';
  else if (strncasecmp($thrdSubj, "re:", 3)!=0)
    $thrdSubj = "Re: " . $thrdSubj;

  // If the user didn't specifically ask to reply to this thread,
  // then initially show just a link that allows the user to ask this
  // and hide the reply box itself
  if (empty($_GET['rply2thrd']) || $_GET['rply2thrd']!=$thrdPid) {
    $class = "hidden";
    $html =
      "<a target=\"_blank\" href=\"discuss.php?subId=$subId&amp;rply2thrd=$thrdPid#rply$thrdPid\""
      . " onclick=\"return expandcollapse('rp$thrdPid');\">"
      . "Reply to this thread</a>\n";
  }
  else { // If the user asked to reply to this thread, put a textarea etc.
    $class = "shown";
    $html = "<a target=\"_blank\" href=\"discuss.php?subId=$subId#rply$thrdPid.\""
      . " onclick=\"return expandcollapse('rp$thrdPid');\">"
      . "Reply to this thread</a>\n";
  }  

  $html .=<<<EndMark
   <a name="rply{$thrdPid}"> </a>
   <form accept-charset="utf-8" id="rp{$thrdPid}" class="$class" action="doPost.php"
         enctype="multipart/form-data" method="post">
   <br />
       Subject:&nbsp;&nbsp;<input
           style="width: 91%;" type="text" name="subject" value="$thrdSubj">
     <br /><textarea style="width: 100%;" rows="9" name="comments"></textarea>
     <br /><input type="submit" value="Submit Reply">
     <input type="hidden" name="subId" value="$subId">
     <input type="hidden" name="parent" value="$thrdPid">
   </form><br />

EndMark;
  return $html;
}
 ********************************************************************/

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
  while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
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
  //  $posts[] = $rows[$idx]['postId']; // just a marker for the reply box
  if ($node->nxtSibIdx > 0) {
    $depth=depth_first_search($node->nxtSibIdx, $depth, $rows, $graph, $posts);
    if ($d > $depth) $depth = $d;
  }
  return $depth;
}
?>
