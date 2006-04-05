<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function print_sub_list($sbList, $title, $reviewed=NULL, $disFlag=false,
			$showAbst=false)
{
  global $reviewIcon, $reviseIcon, $discussIcon1, $discussIcon2;

  if (!empty($title)) {
    print "    <big><b>$title</b></big>\n<br/>\n";
  }
  print "    <table><tbody>\n";

  foreach ($sbList as $sb) {
    $subId = (int) $sb['subId']; 
    $title = $sb['title']; 
    if (strlen($title)>82) $title = substr($title, 0, 80).'...';
    $title = htmlspecialchars($title);

    $authors = htmlspecialchars($sb['authors']); 
    $abstract = htmlspecialchars($sb['abstract']); 
    $fmt = htmlspecialchars($sb['format']); 
    $status = show_status($sb['status']); // show_status in confUtils.php
    $lastMod = (int) $sb['lastModif']; 
    $watch = (int) $sb['watch']; 
    $avg = isset($sb['avg']) ? round((float)$sb['avg'], 1) : NULL ;

    if (isset($lastMod)) $lastMod = date('d/m\&\n\b\s\p\;H:i', $lastMod);
    if ($watch == 1) {
      $src = 'openeye.gif'; $alt = 'W';
      $tooltip = "Click to remove from watch list";
    }
    else {
      $src = 'shuteye.gif'; $alt = 'X';
      $tooltip = "Click to add to watch list";
    }

    // Styles defined in review.css, text constants in confUtils.php
    if (defined('CAMERA_PERIOD')) {
      $width = "70px";
      $revStyle = "none";
      $revText = "";
    }
    else if (isset($reviewed[$subId])) {
      $width = "130px";
      $revStyle = "Revise";
      $revText = $reviseIcon;
    } else {
      $width = "130px";
      $revStyle = "Review";
      $revText = $reviewIcon;
    }

    $subFile = '../'.SUBMIT_DIR."/$subId.$fmt";
    if (!empty($avg)) $avg="($avg)";

    // If this member can discuss - show more details
    if ($disFlag == 1) {
      // The text contants are defiend in confUtils.php
      $disText = ($sb['hasNew']) ? $discussIcon2 : $discussIcon1;

      print <<<EndMark
   <tr><td style="width:20px;"><a href="toggleWatch.php?subId={$subId}">
     <img src="$src" alt="$alt" title="$tooltip" border="0"></a>
   </td>
   <td style="width:$width;"><span class="Discuss"><a target="_blank" href="discuss.php?subId=$subId#start{$subId}">$disText</a></span> <span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
   </td>
   <td style="text-align: right;"><b>$subId.</b></td>
   <td><a href="submission.php?subId=$subId">$title</a></td>
   <td><small>$lastMod</small></td><td>$status</td><td>$avg</td>
   </tr>

EndMark;
    }  // end if ($disFlag == 1) 

    else { print <<<EndMark
    <tr><td style="width:20px;"><a href="$subFile" title="download">
	<img src="download.gif" alt="download" border=0></a>
    </td>
    <td style="width:60px;"><span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
    </td>
    <td style="text-align: right;"><b>$subId.</b></td>
    <td><a href="submission.php?subId=$subId">$title</a></td>
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
