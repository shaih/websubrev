<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function print_sub_list($sbList, $title, $reviewed=NULL, $disFlag=false,
			$showMore=0, $noIndex=false)
{
  // icons are defined in includes/getParams.php
  global $reviewIcon, $revise2Icon, $reviseIcon, $discussIcon1, $discussIcon2;
  $showAbst = ($showMore & 1);
  $showCat = ($showMore & 2);

  if (!empty($title)) {
    print "    <big><b>$title</b></big>\n<br/>\n";
  }
  print "    <table><tbody>\n";

  $idx = 0;
  foreach ($sbList as $sb) {
    $idx++;
    $subId = (int) $sb['subId']; 
    $title = $sb['title']; 
    if (strlen($title)>70) $title = substr($title, 0, 68).'...';
    $title = htmlspecialchars($title);

    if ($showCat) {
      $catgry = empty($sb['category'])? '*' :htmlspecialchars($sb['category']);
      $catgry = "<td><i>($catgry)</i></td>";
    }
    else $catgry = '';

    $authors = isset($sb['authors']) ? htmlspecialchars($sb['authors']) : ''; 
    $abstract = isset($sb['abstract'])? htmlspecialchars($sb['abstract']):''; 
    $fmt = htmlspecialchars($sb['format']); 
    $status = show_status($sb['status']); // show_status in confUtils.php
    $lastMod = (int) $sb['lastModif']; 
    $watch = (int) $sb['watch']; 
    $avg = isset($sb['avg']) ? round((float)$sb['avg'], 1) : NULL ;
    $score = isset($sb['score']) ? '('.$sb['score'].')' : NULL ;

    if (isset($lastMod)) $lastMod = utcDate('d/m\&\n\b\s\p\;H:i', $lastMod);
    if ($watch == 1) {
      $src = '../common/openeye.gif'; $alt = 'W';
      $tooltip = "Click to remove from watch list";
    }
    else {
      $src = '../common/shuteye.gif'; $alt = 'X';
      $tooltip = "Click to add to watch list";
    }

    // Styles defined in ../common/review.css, text constants in ../includes/getParams.php
    if (defined('CAMERA_PERIOD')) {
      $width = "70px";
      $revStyle = "none";
      $revText = "";
    }
    else if (isset($reviewed[$subId])) {
      $width = "140px";
      $revStyle = "Revise";
      $revText = (($reviewed[$subId]==REPORT_NOT_DRAFT)? $reviseIcon : $revise2Icon);
    } else {
      $width = "140px";
      $revStyle = "Review";
      $revText = $reviewIcon;
    }

    if (!empty($avg)) $avg="($avg)";

    if ($disFlag && !$noIndex) $index = "<td><small>$idx.</small></td>";
    else          $index = "";

    // If this member can discuss - show more details
    if ($disFlag == 1) {
      // The text contants are defiend in confUtils.php
      $disText = ($sb['hasNew']) ? $discussIcon2 : $discussIcon1;

      print <<<EndMark
   <tr>$index<td style="width:20px;"><a href="toggleWatch.php?subId={$subId}">
     <img src="$src" alt="$alt" title="$tooltip" border="0"></a>
   </td>
   <td style="width:$width;"><span class="Discuss"><a target="_blank" href="discuss.php?subId=$subId#start{$subId}">$disText</a></span> <span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
   </td>
   <td style="text-align: right;"><b>$subId.</b></td>
   <td><a href="submission.php?subId=$subId">$title</a></td>$catgry
   <td><small>$lastMod</small></td><td>$status</td><td>$avg</td>
   </tr>

EndMark;
    }  // end if ($disFlag == 1) 

    else { print <<<EndMark
    <tr>$index<td style="width:20px;"><a href="download.php?subId=$subId" title="download">
	<img src="../common/download.gif" alt="download" border=0></a>
    </td>
    <td>$score</td>
    <td style="width:60px;"><span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
    </td>
    <td style="text-align: right;"><b>$subId.</b></td>
    <td><a href="submission.php?subId=$subId">$title</a></td>$catgry
    </tr>

EndMark;
    }

    $abs = '';
    if ($showAbst) { // show abstracts too
      if (!ANONYMOUS && isset($authors)) 
	$abs .= "    <tr><td></td><td colspan=3><i>$authors</i></td></tr>\n";
      if (isset($abstract)) {
	$abs .='    <tr><td></td><td colspan=3 class="fixed"><b>Abstract: </b>'
	  . nl2br($abstract). "<br /><br /></span></td></tr>\n";
      }
    }
    print $abs;
  }
  print "    </tbody></table>\n";

}
?>
