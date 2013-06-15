<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function ascii_subHeader(&$sub, $revId=0)
{
  $subId = (int) $sub['subId'];
  $title =  htmlspecialchars(substr($sub['title'], 0, 65));
  $sttus  = ascii_showStatus($sub['status']);
  $wAvg  = isset($sub['wAvg']) ? round($sub['wAvg'],1) : '*';

  // Submission name and statistics
  print <<<EndMark
<pre>
============================================================================
$subId. $title ($wAvg) $sttus
============================================================================
</pre>

EndMark;
}

function ascii_showReviews(&$reviews, $revId)
{
  global $criteria;
  $nCrits = is_array($criteria) ? count($criteria) : 0;

  if (!is_array($reviews)) return;
  $nReviews = count($reviews);
  for ($j=0; $j<$nReviews; $j++) {
    $rev =& $reviews[$j];

    $cmnt2athr = isset($rev['cmnts2athr'])? $rev['cmnts2athr']: '';
    $cmnt2PC   = isset($rev['cmnts2PC']) ?  $rev['cmnts2PC'] :  '';
    $cmnt2chr  = isset($rev['cmnts2chr'])?  $rev['cmnts2chr'] : '';
    $cmnt2athr = htmlspecialchars(wordwrap($cmnt2athr));
    $cmnt2PC   = htmlspecialchars(wordwrap($cmnt2PC));
    $cmnt2chr  = htmlspecialchars(wordwrap($cmnt2chr));
    $conf = (int) $rev['conf'];

    $PCmember = $rev['PCmember'];
    if (isset($rev['subReviewer'])) $PCmember .= ' ('.$rev['subReviewer'].')';
    $PCmember = htmlspecialchars($PCmember);
    $score = isset($rev['score']) ? ((int) $rev['score']) : '*';

    print "<pre>$PCmember,  Score: $score, Confidence: $conf\n";
    $comma = '  ';
    for ($i=0; $i<$nCrits; $i++) {
      $name = $criteria[$i][0];
      $grade = isset($rev["grade_{$i}"]) ? ((int) $rev["grade_{$i}"]) : '*';
      print "$comma  $name: $grade";
      $comma = ',';
    }

    if (is_chair($revId) && !empty($cmnt2chr)) {
      print "\n--------------------------------------------------------\n";
      print "Comments to Chair:\n{$cmnt2chr}\n";
    }
    if (!empty($cmnt2PC)) {
      print "\n--------------------------------------------------------\n";
      print "Comments to Committee:\n{$cmnt2PC}\n";
    }
    if (!empty($cmnt2athr)) {
      print "\n--------------------------------------------------------\n";
      print "Comments to Authors:\n{$cmnt2athr}\n";
    }
    print "\n___________________________________________________________________________";
  print "</pre>\n";
  }
}

/********************************************************************
 * $postsArray is an array of posts, each post is defined as
 * $post = array(depth, postId, parentId,
 *               subject, comments, whenEntered, name)
 ********************************************************************/
function ascii_showPosts(&$postsArray)
{
  if (!is_array($postsArray)) return '';

  $ascii = '';
  foreach($postsArray as $post) {
    $name = htmlspecialchars(wordwrap($post['name']));
    $sbjct = htmlspecialchars(wordwrap($post['subject']));
    $cmnts = htmlspecialchars(wordwrap($post['comments']));

    // Print the subject line
    $ascii .= "<pre>\n* {$name}: {$sbjct}\n{$cmnts}\n</pre>\n";
  }
  return $ascii;
}

function ascii_showStatus($status)
{
  if ($status == 'Withdrawn')   return '[WD]';
  else if ($status == 'Reject') return '[RE]';
  else if ($status == 'Perhaps Reject')   return '[MR]';
  else if ($status == 'Needs Discussion') return '[DI]';
  else if ($status == 'Maybe Accept')     return '[MA]';
  else if ($status == 'Accept') return '[AC]';
  else return '[NO]';
}
?>
