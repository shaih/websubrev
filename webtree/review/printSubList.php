<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function print_sub_list($sbList, $title, $reviewed=NULL, $disFlag=false,
			$showMore=0, $noIndex=false, $revId=0, $thresh=2.0)
{
  // icons are defined in includes/getParams.php
  global $reviewIcon, $revise2Icon, $reviseIcon, $discussIcon1, $discussIcon2, $PCicon, $HVRicon, $pcMember;
  $showAbst = ($showMore & 1);
  $showCat = ($showMore & 2);
  $showPCMark = ($showMore & 4);
  $showHVR = ($showMore & 8);
  if (!empty($title)) {
    print "    <big><b>$title</b></big>\n<br/>\n";
  }
  print <<<EndMark
<table>
<!--<th></th>    
<th><img src="../common/download.gif"/></th>-->
<tbody>
EndMark;

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
    $pcAuthor = (has_pc_author($authors, $subId) && $showPCMark) ? $PCicon:''; // in confUtils.php
    $hvr = (high_variance_reviews($subId, $thresh) && $showHVR) ? $HVRicon:'';

/*  // kluggy integration with Boneh's system for sending questions to authors
    $sharedKey='abcXYZ123';
    $qryStr = 'id='.$subId             // submission ID
      .'&title='.urlencode($title)     // submission title
      .'&authEml='.urlencode($sb['contact'])// comma-separated list of addresses
      .'&revEml='.urlencode($pcMember[2]);  // a single email address

    $token = hash_hmac('sha1', $qryStr, $sharedKey); // authentication token
    $emlURL = "https://crypto.stanford.edu/tcc2013/view.php?$qryStr&token=$token";
*/
    $emlURL = '';
    $lastMod = (int) $sb['lastModif']; 
    $watch = (int) $sb['watch']; 
    $isGroup = ($sb['flags'] & FLAG_IS_GROUP);
    $isChair = is_chair($pcMember[0]);
    if(!$isGroup) {
    	$status = show_status($sb['status']); // show_status in confUtils.php
    } else {
    	$status = '';
    }
    $avg = isset($sb['avg']) ? round((float)$sb['avg'], 1) : NULL ;
    if (isset($lastMod)) $lastMod = utcDate('d/m\&\n\b\s\p\;H:i', $lastMod);
    if ($watch == 1) {
      $watchclass = "open";
      //$src = '../common/openeye.gif'; $alt = 'W';
      $tooltip = "Click to remove from watch list";
    }
    else {
      $watchclass = "close";
      //$src = '../common/shuteye.gif'; $alt = 'X';
      $tooltip = "Click to add to watch list";
    }
    $revStyle = "none";
    $revText = "";
    $width = "";
    if(!$isGroup) {
	    // Styles defined in ../common/review.css, text constants in ../includes/getParams.php
      if (defined('CAMERA_PERIOD')) {
        $width = "70px";
        $revStyle = "none";
        $revText = "";
      }
      else if (isset($reviewed[$subId])) {
        $width = "150px";
        $revStyle = "Revise";
        $revText = (($reviewed[$subId]==REPORT_NOT_DRAFT)? $reviseIcon : $revise2Icon);
      } else {
        $width = "150px";
        $revStyle = "Review";
        $revText = $reviewIcon;
      }
    }

    if ($disFlag && !$noIndex) $index = "<td><small>$idx.</small></td>";
    else          $index = "";
    
    // If this member can discuss - show more details
    if ($disFlag) {
      // The text contants are defiend in confUtils.php
      $disText = ($sb['hasNew']) ? $discussIcon2 : $discussIcon1;
      if ($disFlag==2 && isset($sb['noDiscuss'])) $disText='';

      print <<<EndMark
   <tr class="submission">$index<td style="width:20px;">
   <button class="watch $watchclass" type="button" data-subId="$subId"></button>
   </td> <td style="width:20px;">
EndMark;
   if(!$isGroup){ print <<<EndMark
 <input type="checkbox" class="download" name="download[]" value="$subId" />
EndMark;
}
   print "</td>\n<td style=\"width:$width;\">";
   if(!$isGroup) {
      print '<span class='.$revStyle.'><a href="review.php?subId='.$subId.'" target="_blank">'.$revText.'</a></span>';
    }
   print "<span class=\"Discuss\"><a target=\"_blank\" href=\"discuss.php?subId=$subId#start{$subId}\">$disText</a></span>";
    print <<<EndMark
   </td>
<!--
 <td><a target=_blank href="$emlURL" title="ask a question by email">
	<img height="14" src="../common/email.gif" alt="eml" border=0></a></td>
-->
   <td style="text-align: right;"><b>$subId.</b></td>
EndMark;
    if(!$isGroup){print <<<EndMark
   <td style="width: 540px;"><a href="submission.php?subId=$subId">$title</a></td>$catgry
EndMark;
    } else {
    	print <<<EndMark
    	<td style="width: 540px;">$title</td>$catgry
EndMark;
    }
    print <<<EndMark
   <td><small>$lastMod</small></td><td style="width:100px;">$status $pcAuthor</td><td>$avg</td>
   </tr>

EndMark;
    }  // end if ($disFlag == 1)

    else { print <<<EndMark
    <tr class="submission" data-subId="$subId">$index
    <td style="width:20px;">
    <button class="watch $watchclass" type="button" data-subId="$subId"></button>
    </td><td style="width:20px;">
EndMark;
    if(!$isGroup) {print <<<EndMark
   <input type="checkbox" class="download" name="download[]" value="$subId" /></td>
   <td style="width:60px;"><span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
    
EndMark;
	}
	print <<<EndMark
<!--
    </td><td><a target=_blank href="$emlURL" title="ask a question by email">
	<img height="14" src="../common/email.gif" alt="eml" border=0></a>
-->
    </td style="width:540px"><td style="text-align: right;"><b>$subId.</b></td>
EndMark;
	if(!$isGroup){print <<<EndMark
    <td><a href="submission.php?subId=$subId">$title $pcAuthor</a></td>$catgry
EndMark;
	} else {
		print <<<EndMark
		<td width='140px;'>$title $pcAuthor</td>$catgry
EndMark;
	}
	print <<<EndMark
    </tr>

EndMark;
    }
    
    $abs = '';
    if ($showAbst) { // show abstracts too
      if (!ANONYMOUS && isset($authors)) 
	$abs .= "    <tr><td></td><td></td><td></td><td></td><td colspan=3><i>$authors</i></td></tr>\n";
      if (isset($abstract)) {
	$abs .='    <tr><td></td><td></td><td></td><td></td><td colspan=3 class="fixed"><b>Abstract: </b>'
	  . nl2br($abstract). "<br /><br /></span></td></tr>\n";
      }
    }
    print $abs;
  }

  print <<<EndMark
    </tbody></table>

<div>
<button class="select-all" type="button">Select All</button>
<button class="deselect-all" type="button">Deselect All</button>
<button class="download-btn" type="button">Download Selected</button>
</div>

EndMark;
}
?>
