<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
function show_rev_links($current = 0)
{
  global $pcMember;
  $html = "<div style=\"text-align: center;\">\n";

  if (is_chair($pcMember[0]))
    $html .= make_link('../chair/', 'Administer');
  
  $html .= make_link('guidelines.php', 'Guidelines', ($current==1))
    . make_link('index.php', 'Review Home', ($current==2));
  if (PERIOD>=PERIOD_REVIEW ||(PERIOD==PERIOD_SUBMIT &&USE_PRE_REGISTRATION)) {
    $html.= make_link('listSubmissions.php', 'List submissions',($current==3));
    if (REVPREFS && !$pcMember[3])
      $html .= make_link('prefs.php', 'Preferences', ($current==4));
  }
  $html .= make_link('password.php', 'Change Password', ($current==5))
    . make_link('../documentation/reviewer.html', 'Documentation')."</div>\n";

  return $html;
}

function order_clause()
{
  $order = $heading = $comma = '';
  $flags = 0;

  if (isset($_GET['sortByStatus'])) {
    $flags = 8;
    $order = $heading = 'status';
    $comma = ', ';
  }

  $ord = isset($_GET['sortOrder']) ? trim($_GET['sortOrder']) : NULL;
  if (isset($ord)) {
    $dir = substr($ord, -4);
    if (strcasecmp($dir, " ASC")==0)
         $ord = substr($ord, 0, strlen($ord)-4);
    else $dir = "DESC";
    if ($ord=='num') {
      $order .= $comma . 's.subId';
      $heading .= $comma . 'number';
    } else if ($ord=='mod') {
      $order .= $comma . "lastModif $dir, s.subId";
      $heading .= $comma . 'modified';
      $flags |= 1;
    } else if ($ord=='wAvg') {
      $order .= $comma . "s.wAvg $dir, s.subId";
      $heading .= $comma . 'weighted average';
      $flags |= 2;
    } else if ($ord=='avg')  {
      $order .= $comma . "s.avg $dir, s.subId";
      $heading .= $comma . 'average';
      $flags |= 3;
    } else if ($ord=='delta'){
      $order .= $comma . "delta $dir, s.subId"; 
      $heading .= $comma . 'difference in grades';
      $flags |= 4;
    } else if ($ord =='conf'){
      $order .= $comma . "avgConf $dir, s.subId";
      $heading .= $comma . 'confidence';
      $flags |= 5;
    } else if ($ord =='minGrade'){
      $order .= $comma . "minGrade $dir, s.subId";
      $heading .= $comma . 'min grade';
      $flags |= 6;
    } else if ($ord =='maxGrade'){
      $order .= $comma . "maxGrade $dir, s.subId";
      $heading .= $comma . 'max grade';
      $flags |= 7;
      }
  }

  return array($order, $heading, $flags);
}

function showReviewsBox($flags=0)
{
  $flags = ($flags & 0xffffff00) >> 8;
  $srt0=$srt1=$srt2=$srt3=$srt4=$srt5=$srt6='';
  switch ($flags % 8) {
  case 1:
    $srt1=' checked="checked"';
    break;
  case 2:
    $srt2=' checked="checked"';
    break;
  case 3:
    $srt3=' checked="checked"';
    break;
  case 4:
    $srt4=' checked="checked"';
    break;
   case 5:
   	$srt5=' checked="checked"';
   	break;
   case 6:
   	$srt6=' checked="checked"';
    break;
  default:
    $srt0=' checked="checked"';
  }
  $sttsChk  = ($flags &   8) ? ' checked="checked"' : '';
  $wtchChk  = ($flags &  16) ? ' checked="checked"' : '';
  $noWtchChk= ($flags &  32) ? ' checked="checked"' : '';
  $revwChk  = ($flags &  64) ? ' checked="checked"' : '';
  $dscsChk  = ($flags & 128) ? ' checked="checked"' : '';

  $html =<<<EndMark
<form accept-charset="utf-8" action="listReviews.php" method="get">
<div class="frame">
<table><tbody>
<tr style="background: blue; color: white;">
  <td colspan=2><input type="submit" name="showRevBox" value="Show">
      <strong><big> Review Grades by:</big></strong>
  </td>
</tr>
<tr><td><input type="checkbox" name="sortByStatus"{$sttsChk}> Status+</td>
  <td><input type="radio" name="sortOrder" value="num"{$srt0}>
    submission number</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="mod"{$srt1}> modification date</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="wAvg"{$srt2}> weighted average</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="avg"{$srt3}> average</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="maxGrade"{$srt5}> max grade</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="minGrade"{$srt6}> min grade</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="delta"{$srt4}> grade difference</td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="watchOnly"{$wtchChk}>
    Only submissions on my watch list</td>
</tr>
<tr>
  <td colspan=2>or <input type="checkbox" name="ignoreWatch"{$noWtchChk}>
    Ignore watch-list designation<hr /></td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="withReviews"{$revwChk}>
    Show with reviews</td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="withDiscussion"{$dscsChk}>
    Show with discussion</td>
</tr>
</tbody></table>
</div></form>
EndMark;

  return $html;
}

function listSubmissionsBox($canDiscuss, $flags=0)
{
  global $pcMember;
  global $revId;
  $flags = $pcMember[5];
  
  $srt0=$srt1=$srt2=$srt5='';
  switch ($flags % 8) {
  case 1:
    $srt1=' checked="checked"'; // sorted by modification date
    break;
  case 2:
    $srt2=' checked="checked"'; // sorted by weighted average
    break;
  default:
    $srt0=' checked="checked"'; // sorted by submission number
  }
  $sttsChk  = ($flags &  8) ? ' checked="checked"' : '';
  $asgnChk  = ($flags & 16) ? ' checked="checked"' : '';
  $tagsChk  = ($flags & 32) ? ' checked="checked"' : '';
  $abstChk  = ($flags & 64) ? ' checked="checked"' : '';
  $catChk  = ($flags & 128) ? ' checked="checked"' : '';
  $disChk  = ($flags & 0x10000) ? ' checked="checked"' : '';
             // only submissions that I discussed
  $optChk   = ($flags & 0x20000) ? ' checked="checked"' : '';
             // only opt-in submissions

  if ($canDiscuss) {
    $stts = '<input type="checkbox" name="sortByStatus"'.$sttsChk.'> Status+';
    $viewDiscussed = '<input type="checkbox" name="onlyDiscussed"'.$disChk.'> Only submissions I discussed<br/>';
    $search= "Search <a href='../documentation/reviewer.html#tags' target='_blank'>tags</a>:<input name='allTags' placeholder='tag1; -tag2; ...'>";
    $showTags = "<input type='checkbox' name='showTags'{$tagsChk}>Show with tags<br/>";
  } else { 
    $stts = '&nbsp;';
    $viewDiscussed = $search = $showTags = '';
  }
  $viewChecked = "";
  $highVar = "";
  if(defined("OPTIN_TEXT") && is_chair($revId)) {
    $viewChecked = "<input type='checkbox' name='optedIn' $optChk/> Only \"opt-in\" submissions<br/>";
  }
  
  $html =<<<EndMark
<form accept-charset="utf-8" action="listSubmissions.php" method="get">
<div class="frame">
<table><tbody>
<tr style="background: blue; color: white; text-align: center;">
  <td colspan=2><input type="submit" name="listBox" value="List">
      <strong><big> Submissions Sorted by:</big></strong>
  </td>
</tr>
<tr><td>$stts</td>
  <td><input type="radio" name="sortOrder" value="num"{$srt0}>
    submission number</td>
</tr>
EndMark;

  if ($canDiscuss) { // discussion phase
    //   $highVar = '<input type="checkbox" name="hvr"'.$hvrChk.'>Highlight high variance scores <input style="margin-left: 21px;" type="text" name="thresh" placeholder="2.0">';
    $html .=<<<EndMark
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="mod"{$srt1}> modification date</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="wAvg"{$srt2}> weighted average</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="conf"{$srt5}> confidence</td>
</tr>

EndMark;
  }

  $html .=<<<EndMark
  <tr><td colspan=2>
  <input type="checkbox" name="onlyAssigned"{$asgnChk}> Only submissions assigned to me<br/>
  $viewDiscussed
  $viewChecked
  $search  
</td></tr>
<tr><td colspan=2><hr/>
   $showTags
   <input type="checkbox" name="abstract"{$abstChk}>Show with abstracts<br/>
   <input type="checkbox" name="category"{$catChk}>Show with category<br/>
   $highVar
</td></tr>
</tbody></table>\n</div></form>
EndMark;

  return $html;
}


function setFlags_table($subId, $status)
{
  if (PERIOD==PERIOD_FINAL) return;
  $params = implode_assoc('&amp;', $_GET);
  if (!empty($params)) $params = '?'.$params;
  else                 $params = ''; // make sure it's not NULL

  $chk0 = $chk1 = $chk2 = $chk3 = $chk4 = $chk5 = $chk6 = '';
  if ($status == 'Withdrawn') {
    $chk1 = "checked=\"checked\"";
  } else if ($status == 'Reject') {
    $chk2 = "checked=\"checked\"";
  } else if ($status == 'Perhaps Reject') {
    $chk3 = "checked=\"checked\"";
  } else if ($status == 'Needs Discussion') {
    $chk4 = "checked=\"checked\"";
  } else if ($status == 'Maybe Accept') {
    $chk5 = "checked=\"checked\"";
  } else if ($status == 'Accept') {
    $chk6 = "checked=\"checked\"";
  } else { 	$chk0 = "checked=\"checked\""; }

  $html = <<<EndMark
<form accept-charset="utf-8" action="../chair/setStatus.php{$params}" name="sttsForm{$subId}" id="sttsForm{$subId}" enctype="multipart/form-data" onsubmit="return ajaxSetStatus(this);" method="POST">
  <input type="hidden" name="visible" value="true">
  <table border=1><tbody>
     <tr><td><input type="submit" value="Set status:"></td>
       <td class="setNO"><input type="radio" name="scrsubStts{$subId}" value="None" $chk0><b>None</b></td>
       <td class="setRE"><input type="radio" name="scrsubStts{$subId}" value="Reject" $chk2><b>Reject</b></td>
       <td class="setMR"><input type="radio" name="scrsubStts{$subId}" value="Perhaps Reject" $chk3>
         <b>Maybe Reject</b></td>
       <td class="setDI"><input type="radio" name="scrsubStts{$subId}" value="Needs Discussion" $chk4>
         <b>Discuss</b></td>
       <td class="setMA"><input type="radio" name="scrsubStts{$subId}" value="Maybe Accept" $chk5>
         <b>Maybe Accept</b></td>
       <td class="setAC"><input type="radio" name="scrsubStts{$subId}" value="Accept" $chk6><b>Accept</b></td>
     </tr>
  </tbody></table></form>
EndMark;

  return $html;
}

function showTags($tags, $subId, $isChair)
{
  $tags = tagLine($tags, $subId, $isChair);
  if (empty($tags))
    $tagLine = "<span class='tags' style='color: gray;'>Click to add tags: tag1; tag2;...</span>";
  else
    $tagLine = "<span class='tags' style='color: black;'>$tags</span>";

  return <<<EndMark
<div class="showTags">
  <a href="editTags.php?subId=$subId" title="click to edit tags" class="tagsLink"><div ID='showTags{$subId}'><img src="../common/tags.gif" alt="Tags:"> $tagLine</div></a>
  <form accept-charset="utf-8" action="doEditTags.php" method="POST" enctype="multipart/form-data" class="tagsForm hidden">
  <input type='hidden' name='subId' value='$subId'/>
  <table style='width: 99%;'><tr class='nowrp'>
  <td style='width: 95%;'><input name='tags' style='width: 100%;' type='text' value='$tags'/></td>
  <td><input type='submit' value='Save'/><button>Cancel</button></td>
  <td><a class='noHandle' href="../documentation/reviewer.html#tags" target='_blank'>what&#39;s this?</a></td></tr></table>
</form>
</div>
EndMark;
}
?>
