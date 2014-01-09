<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

function print_sub_list($sbList, $title, $reviewed=NULL, $disFlag=false,
			$showMore=0, $noIndex=false, $revId=0)
{
  // icons are defined in includes/getParams.php
  global $reviewIcon, $revise2Icon, $reviseIcon, $discussIcon1, $discussIcon2, $CONFicon, $PCMicon;
  $showAbst = ($showMore & 1);
  $showCat = ($showMore & 2);
  $stdThreshold = isset($_GET['threshold'])? $_GET['threshold']: sqrt(2.0);
  if ($stdThreshold<1.0) $stdThreshold = sqrt(2.0);

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
  $zIdx = 2000;
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
    $conflict = '';
    if (!empty($sb['conflict']) && $sb['conflict']<0)
      $conflict = (($sb['conflict']==-2)? $PCMicon : $CONFicon).' ';

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
    $isChair = is_chair($revId);
    $status = $isGroup? '' :
      $status = show_status($sb['status']); // show_status in confUtils.php

    $avg = isset($sb['avg']) ? round((float)$sb['avg'], 1) : NULL ;
    if (isset($avg) && isset($sb['stdev']) && $sb['stdev']>$stdThreshold)
      $avg = "<span title='score stdev=".round((float)$sb['stdev'],1)."' style='background-color: #FFA500;'>$avg</span>";

    if (isset($lastMod)) $lastMod = utcDate('d/m\&\n\b\s\p\;H:i', $lastMod);
    if ($watch == 1) {
      $eyeSrc = '../common/openeye.gif'; $alt = 'W';
      $watchTooltip = "Click to remove from watch list";
    }
    else {
      $watch = 0; // just making sure
      $eyeSrc = '../common/shuteye.gif'; $alt = 'X';
      $watchTooltip = "Click to add to watch list";
    }
    $revStyle = "none";
    $revText = "";
    if (!$isGroup && !defined('CAMERA_PERIOD')) {
      // Styles in ../common/review.css, constants in ../includes/getParams.php
      if (isset($reviewed[$subId])) {
        $revStyle = "Revise";
        $revText = (($reviewed[$subId]==REPORT_NOT_DRAFT)? $reviseIcon : $revise2Icon);
      } else {
        $revStyle = "Review";
        $revText = $reviewIcon;
      }
    }

    if ($disFlag && !$noIndex) $index = "<td><small>$idx.</small></td>";
    else          $index = "";
    
    // If this member can discuss - show more details
    if ($disFlag) { // Text contants are defiend in confUtils.php
      $disText = ($sb['hasNew']) ? $discussIcon2 : $discussIcon1;
      if ($disFlag==2 && isset($sb['noDiscuss'])) $disText='';

      if (!empty($disText)) {
	$markRead = ($sb['hasNew'])? 0 : 1;
	$toggleText = "<a href='toggleMarkRead.php?subId=$subId&current=$markRead' class='toggleRead' title='Toggle Read/Unread' ID='toggleRead$subId' rel='$markRead'>&bull;</a>"; 
	// we use old fashioned rel attribute to pass data to javascript
      }
      else $toggleText = '';

      $tags = '';
      if (!empty($sb['tags'])) $tags = tagLine($sb['tags'],$subId,$isChair);
      if (!empty($tags)) $tags = "<span>$tags</span>";
      else $tags = '<span style="color: gray;">click to add tags</span>';

      if (isset($_GET['showTags'])) {
	$tagsIcon = '';
	$tagsLine = "<tr><td colspan='4'></td><td colspan='5'>"
		. showTags($sb['tags'], $subId, $isChair)
		. "</td></tr>\n";
      } else {
	$tagsIcon = "<td><a target='_blank' class='tagsIcon tooltips' href='editTags.php?subId={$subId}' style='z-index: $zIdx;'><img alt='tags' src='../common/tags.gif' height='10'/>$tags</a></td>";
	$tagsLine = '';
      }

      print <<<EndMark
<tr class="submission">$index
   <td><a rel='$watch' href='toggleWatch.php?subId={$subId}&current={$watch}'><img src='$eyeSrc' id='toggleWatch$subId' alt='$alt' title='$watchTooltip' border='0'></a></td> 

EndMark;

   if(!$isGroup){ 
     print <<<EndMark
  <td><input type="checkbox" class="download" title="Select to download" name="download[]" value="$subId"/></td>
  <td><span class="$revStyle"><a href="review.php?subId=$subId" target="_blank">$revText</a></span>

EndMark;
     $title = "<td style='width: 99%;'><a href='submission.php?subId=$subId'>$title</a></td>";
   }
   else {
     print "  <td></td><td>";
     $title = "<td style='width: 99%;'>$title</td>";
   }

   print <<<EndMark
<span class="Discuss"><a target="_blank" href="discuss.php?subId=$subId#start{$subId}">$disText</a>\n$toggleText</span></td>
<!--
  <td><a target=_blank href="$emlURL" title="ask a question by email">
	<img height="14" src="../common/email.gif" alt="eml" border=0></a></td>
-->
  <td style="text-align: right;"><b>$subId.</b></td>
  $tagsIcon
  $title
  $catgry
  <td><small>$lastMod</small></td><td>$status</td><td>{$conflict}$avg</td>
</tr>
$tagsLine
EndMark;
 } // end if ($disFlag)
 /********************************************************************/
 else { // disFlag is off
   print <<<EndMark
<tr class="submission" data-subId="$subId">$index
  <td></td><td>
EndMark;

   if(!$isGroup) {
     print <<<EndMark
   <input type="checkbox" class="download" title="Select to download" name="download[]" value="$subId" /></td>
  <td><span class=$revStyle><a href="review.php?subId=$subId" target="_blank">$revText</a></span>
    
EndMark;
   }
   print <<<EndMark
<!--
  </td><td><a target=_blank href="$emlURL" title="ask a question by email">
      <img height="14" src="../common/email.gif" alt="eml" border=0></a>
-->
  </td><td style="text-align: right;"><b>$subId.</b></td>
  <td style="width: 99%;">
EndMark;

   if(!$isGroup)
     print "<a href='submission.php?subId=$subId'>$title</a></td>";
   else print "$title $pcAuthor</td>";
   print "{$catgry}\n</tr>\n";
 }       // end of disFlag = 0
 /********************************************************************/

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
 $zIdx--;
} // end of loop over submissions

print <<<EndMark
</tbody></table>
<div>
<button class="download-btn" type="button">Download Selected</button>
<button class="select-all" type="button">Select All</button>
<button class="deselect-all" type="button">Deselect All</button>
</div>

EndMark;
}

function tagsBox($tags, $subId, $isChair)
{
  $tagLine = $semi = '';
  foreach($tags as $tag) {
    if (!preg_match('/^[\~\$\^]?[0-9a-z_\- ]+$/i', $tag)) continue; //invalid
    if (($tag[0] == '\$') && (!$isChair)) continue; // not a chair
    $tagLine .= $semi . $tag;
    $semi = '; ';
  }
  if (empty($tagLine))
       return '<span style="color: gray;">click to add tags</span>';
  else return "<span>$tagLine</span>";
}
?>
