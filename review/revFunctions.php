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

  if ($pcMember[0]==CHAIR_ID)
    $html .= make_link('../chair/', 'Administer');

  $html .= make_link('guidelines.php', 'Guidelines', ($current==1))
    . make_link('index.php', 'Review Home', ($current==2));
  if (defined('REVIEW_PERIOD')) {
    $html.=make_link('listSubmissions.php', 'List submissions',($current==3));
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

  if (isset($_GET['sortByStatus'])) {
    $order = $heading = 'status';
    $comma = ', ';
  }

  $ord = isset($_GET['sortOrder']) ? trim($_GET['sortOrder']) : NULL;
  if (isset($ord)) {
    if ($ord=='num') {
      $order .= $comma . 's.subId';
      $heading .= $comma . 'number';
    } else if ($ord=='mod') {
      $order .= $comma . 'lastModif DESC, s.subId';
      $heading .= $comma . 'modified';
    } else if ($ord=='wAvg') {
      $order .= $comma . 's.wAvg DESC, s.subId';
      $heading .= $comma . 'weighted average';
    } else if ($ord=='avg')  {
      $order .= $comma . 's.avg DESC, s.subId';
      $heading .= $comma . 'average';
    } else if ($ord=='delta'){
      $order .= $comma . 'delta DESC, s.subId'; 
      $heading .= $comma . 'max-min grade';
    }
  }

  return array($order, $heading);
}

function showReviewsBox()
{
  $html =<<<EndMark
<form action="listReviews.php" method="get">
<div class="frame">
<table><tbody>
<tr style="background: blue; color: white;">
  <td colspan=2><input type="submit" value="Show">
      <strong><big> Review Grades by:</big></strong>
  </td>
</tr>
<tr><td><input type="checkbox" name="sortByStatus"> Status+</td>
  <td><input type="radio" name="sortOrder" value="num" checked="checked">
    submission number</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="mod"> modification date</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="wAvg"> weighted average</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="avg"> average</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="delta"> max-min grade</td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="watchOnly">
    Only submissions on my watch list</td>
</tr>
<tr>
  <td colspan=2>or <input type="checkbox" name="ignoreWatch">
    Ignore watch-list designation<hr /></td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="withReviews">
    Show with reviews</td>
</tr>
<tr>
  <td colspan=2><input type="checkbox" name="withDiscussion">
    Show with discussion</td>
</tr>
</tbody></table>
</div></form>
EndMark;

  return $html;
}

function listSubmissionsBox($canDiscuss)
{
  if ($canDiscuss) {
    $stts = '<input type="checkbox" name="sortByStatus"> Status+';
  } else { $stts = '&nbsp;'; }

  $html =<<<EndMark
<form action="listSubmissions.php" method="get">
<div class="frame">
<table><tbody>
<tr style="background: blue; color: white; text-align: center;">
  <td colspan=2><input type="submit" value="List">
      <strong><big> Submissions Sorted by:</big></strong>
  </td>
</tr>
<tr><td>$stts</td>
  <td><input type="radio" name="sortOrder" value="num" checked="checked">
    submission number</td>
</tr>

EndMark;

  if ($canDiscuss) { // discussion phase
    $html .=<<<EndMark
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="mod"> modification date</td>
</tr>
<tr><td></td>
  <td><input type="radio" name="sortOrder" value="wAvg"> weighted average</td>
</tr>

EndMark;
  }

  $html .=<<<EndMark
<tr><td colspan=2><input type="checkbox" name="onlyAssigned">
  Only submissions assigned to me<br/>
  or <input type="checkbox" name="ignoreAssign">Show all submissions in one list</td>
</tr>
<tr><td colspan=2><hr/>
    <input type="checkbox" name="abstract">Show with abstracts</td>
</tr>
</tbody></table>\n</div></form>
EndMark;
    
  return $html;
}


function setFlags_table($subId, $status)
{
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
<form action="../chair/setStatus.php{$params}"
		enctype="multipart/form-data" method="post">
  <table border=1><tbody>
     <tr><td><input type="submit" value="Set status:"></td>
       <td class="setNO"><input type="radio" name="subStts{$subId}" value="None" $chk0><b>None</b></td>
       <td class="setRE"><input type="radio" name="subStts{$subId}" value="Reject" $chk2><b>Reject</b></td>
       <td class="setMR"><input type="radio" name="subStts{$subId}" value="Perhaps Reject" $chk3>
         <b>Maybe Reject</b></td>
       <td class="setDI"><input type="radio" name="subStts{$subId}" value="Needs Discussion" $chk4>
         <b>Discuss</b></td>
       <td class="setMA"><input type="radio" name="subStts{$subId}" value="Maybe Accept" $chk5>
         <b>Maybe Accept</b></td>
       <td class="setAC"><input type="radio" name="subStts{$subId}" value="Accept" $chk6><b>Accept</b></td>
     </tr>
  </tbody></table></form>
EndMark;

  return $html;
}

?>