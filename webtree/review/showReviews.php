<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function subDetailedHeader($sub, $revId=0, $showDiscussButton=true, $rank=0)
{
  global $discussIcon1, $discussIcon2;

  $subId = (int) $sub['subId'];
  $title =  htmlspecialchars($sub['title']);
  $sttus  = show_status($sub['status']);
  $avg   = isset($sub['avg']) ? round($sub['avg'],1) : '*';
  $wAvg  = isset($sub['wAvg']) ? round($sub['wAvg'],1) : '*';
  $delta = isset($sub['delta']) ? ((int) $sub['delta']) : '*';
  $disText = (isset($sub['hasNew'])&&$sub['hasNew']) ? $discussIcon2 : $discussIcon1;

  $lastModif = isset($sub['lastModif']) ? utcDate('M\&\n\b\s\p\;j H:i', ((int)$sub['lastModif'])) : '';

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
  if ($revId==CHAIR_ID) {
    $cnnct = db_connect();
    $qry = "SELECT c.name FROM assignments a, committee c WHERE a.subId=$subId AND a.assign=-1 AND c.revId=a.revId";
    $res = db_query($qry, $cnnct);
    $conflictList = '';
    while ($row = mysql_fetch_row($res)) {
      $conflictList .= '<br/>&nbsp;&nbsp;'.$row[0];
    }
    print "<table><tbody><tr><td valign=middle>";
    if (!empty($conflictList)) {
      $zIx = 2000 - $subId;
      print "<a name=\"stts{$subId}"
	. '" class=tooltips href="#" onclick="return false;" style="z-index:'
	. "$zIx;\">\n";
      print '<img alt="X" title="" height=16 src="../common/stop.GIF" border=0/><span>Conflicts:'.$conflictList."</span></a></td><td>\n";
    }
    else {
      print "&nbsp;&nbsp;&nbsp;&nbsp;</td><td>\n";
    }
    print setFlags_table($subId, $sub['status']); // setFlags_table in revFunctions.php
    print "</td></tr></tbody></table>";
  }

  print "<table style=\"width: 100%;\"><tbody><tr style=\"vertical-align: middle;\">\n";

  if (!empty($extra))
    print "    <td style=\"vertical-align: top;\">$extra</td>\n";

  if ($showDiscussButton) {
    print '    <td style="width: 25px;"><span class="Discuss"><a target="_blank" href="discuss.php?subId='.$subId.'#start'.$subId."\">$disText</a></span>
    </td>"."\n";
  }
    // Submission name and statistics
    print <<<EndMark
    <td style="width: 25px; text-align: right;"><strong>$subId.</strong>&nbsp;
    </td>
    <td><big><a href="submission.php?subId=$subId">$title</a></big></td>
    <td style="width: 50px; text-align: center;">
	<small>Average<br />$avg</small>
    </td>
    <td style="width: 50px; text-align: center;">
	<small>Weighted<br />$wAvg</small>
    </td>
    <td style="width: 50px; text-align: center;">
	<small>Max-Min<br />$delta</small>
    </td>
    <td style="width: 40px; text-align: right;">$sttus</td>
    <td style="width: 50px; text-align: center;"><small>$lastModif</small>
    </td>
    </tr>
    </tbody></table>
    </div>

EndMark;
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

  print "  <div class=\"lightbg\">\n";
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
    $score = (int) $rev['score'];
    if ($score <= 0) $score = '*';

    print "    <tr><td class=\"ctr\"><small>$mod</small></td>
      <td class=\"ctr\">$score</td>
      <td class=\"ctr\">$confidence</td>\n";
    $nCrits = (is_array($criteria)) ? count($criteria) : 0;
    for ($i=0; $i<$nCrits; $i++) {
      $grade = (int) $rev["grade_{$i}"];
      if ($grade <= 0) $grade = '*';
      print "      <td class=\"ctr\">$grade</td>\n";
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
    // If this is my review, show a link to edit it
    if (!defined('CAMERA_PERIOD') && $rid==$revId) 
      $reviseTxt = ' <a target=_blank title="Revise your report" href="review.php?subId='
                   . $sid. '"> [revise]</a>';
    else
      $reviseTxt = ''; 

    $cmnt2athr = isset($rev['cmnts2athr'])?htmlspecialchars($rev['cmnts2athr']):'';
    $cmnt2PC   = isset($rev['cmnts2PC']) ? htmlspecialchars($rev['cmnts2PC']):'';
    $cmnt2chr  = isset($rev['cmnts2chr'])? htmlspecialchars($rev['cmnts2chr']):'';
    $score = isset($rev['score']) ? ((int) $rev['score']) : '*';
    $conf =  isset($rev['conf']) ? ((int) $rev['conf']) : '*';

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
    if ($revId==CHAIR_ID && !empty($cmnt2chr)) {
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
    }
    if ($reviewsShown) print "<br/>\n";
    else print "\n";
  }
}

/********************************************************************
 * $postsArray is an array of posts, each post is defined as
 * $post = array(depth, postId, parentId,
 *               subject, comments, whenEntered, name)
 ********************************************************************/
function show_posts($postsArray, $subId, $threaded=true, 
		    $lastSaw=0, $pageWidth=720)
{
  // exit("<pre>".print_r($postsArray, true)."</pre>");
  if (!is_array($postsArray)) return;

  $thrdPid = $thrdSubj = NULL;
  $newPosts = false;
  foreach($postsArray as $post) {

    $depth = isset($post['depth']) ? $post['depth'] : 0;
    if ($depth > 3) $depth = 3;

    if ($depth==0) {          // first post in a thread
      if ($threaded && isset($thrdPid)) {// not the first thread, close prior one
	print reply_to_thread($subId, $thrdPid, $thrdSubj);
	print "<hr />\n\n";
      }
      // reset the thread-pid and thread-subject 
      $thrdPid = $post['postId'];
      $thrdSubj = isset($post['subject']) ? $post['subject'] : '';

    } // end if ($depth==0)

    // Display the current post
    $depth *= 20;
    $width = $pageWidth - $depth;

    $pid = (int) $post['postId'];
    if (defined('CAMERA_PERIOD')) {
      $class = "hidden";
      $reply = "";
    }
    else if (isset($_GET['rply2post']) && $_GET['rply2post']==$pid) { // show the reply box
      $class = "shown";
      $reply = "[<a target=\"_blank\" href=\"discuss.php?subId=$subId#p$pid\""
	. " onclick=\"return expandcollapse('r$pid');\">Reply</a>]";
    }
    else {                          // initially hide the reply box
      $class = "hidden";
      $reply = "[<a target=\"_blank\" href=\"discuss.php?subId=$subId&amp;rply2post=$pid#p$pid\""
	. " onclick=\"return expandcollapse('r$pid');\">Reply</a>]";
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
    $cmnts = trim($post['comments']);
    $cmnts = (empty($cmnts)) ? '<br/>'
      : nl2br(htmlspecialchars($cmnts)).'<br/><br/>';
    $subject = isset($post['subject']) ? htmlspecialchars($post['subject']) : '';
    if (strncasecmp($subject, "re:", 3)!=0)
      $subject = 'Re: ' . $subject;

    // Print the subject line
    print <<<EndMark
<!-- =========================================================== -->
<div style="position: relative; left: {$depth}px; width: {$width}px;">
<div style="float: right;">
$nameWhen $reply
</div>
$startHere<a name="p$pid"> </a>
&#8722;&nbsp;<span class="sbjct">$sbjct</span>$editPostLink

<div style="position: relative; left: 12px; top:6px;">
$cmnts
   <form id="r$pid" class="$class" action="doPost.php"
		enctype="multipart/form-data" method="post">
     Subject:&nbsp;&nbsp;<input style="width: 91%;" type="text"
		          name="subject" value="$subject">
     <br /><textarea style="width: 100%;" rows="9" name="comments"></textarea>
     <br /><input type="submit" value="Submit Reply">
     <input type="hidden" name="subId" value="$subId">
     <input type="hidden" name="parent" value="$pid">
   <br />
   <br />
   </form>
</div>
</div>

EndMark;
  }
  if ($threaded && isset($thrdPid)) {// close last thread
    print reply_to_thread($subId, $thrdPid, $thrdSubj);
    print "<hr />\n\n";
  }
}

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
   <form id="rp{$thrdPid}" class="$class" action="doPost.php"
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
?>
