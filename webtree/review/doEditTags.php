<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php'; // defines $pcMember=array(id, name, email, ...)

$revId = (int) $pcMember[0];
$isChair = is_chair($revId);

if (($subId = (int) $_POST['subId']) <= 0)
  exit("Submission must be specified");

if (isset($_POST['tags'])) {
  $tags = explode(';',$_POST['tags']);

  $qry = "INSERT IGNORE INTO {$SQLprefix}tags (tagName,subId,type,flags) VALUES (?,$subId,?,?)";
  $stmt = $db->prepare($qry);

  // remove all tags, then insert only the ones in the input field
  $qry = "DELETE FROM {$SQLprefix}tags WHERE subId=? AND ";
  if ($isChair) $qry .= '(type=? OR type<=0)';
  else          $qry .= 'type IN (?,0) AND NOT (flags &'.FLAGS_STICKY_TAG.')';
                // non-chair PC members cannot remove sticky tags
  pdo_query($qry, array($subId,$revId));

  $tagsLine = $semi = '';
  foreach($tags as $tag) {
    $tag = trim($tag);
    if (empty($tag)) continue;
    if (!preg_match('/^[\~\$\^]?[0-9a-z_\-]+$/i', $tag)) continue; //invalid

    $c = $tag[0]; // check for special first letters
    if ($c == '$') {    // chair-only tag (only a chair can save one)
      if ($isChair) $stmt->execute(array($tag,-1,0));
    } elseif ($c == '^') { // public sticky tag (only a chair can save one)
      if ($isChair) $stmt->execute(array($tag,0,FLAGS_STICKY_TAG));
    } elseif ($c == '~') { // private tag
      $stmt->execute(array($tag,$revId,0));
    } else {              // public tag
      $stmt->execute(array($tag,0,0));
    }
    $tagsLine .= $semi . $tag;
    $semi = '; ';
  }
}

if (isset($_POST['ajax'])) {
  $qry = "SELECT tagName FROM {$SQLprefix}tags WHERE subId=? AND ";
  if ($isChair) $qry .= '(type=? OR type<=0)';
  else          $qry .= 'type IN (?,0)';
  $qry .= ' ORDER BY tagName';
  $res = pdo_query($qry,array($subId,$revId));
  $tags = array();
  while ($t = $res->fetchColumn()) $tags[] = $t;
  $tags = implode('; ', $tags);
  header("Cache-Control: no-cache");
  exit($tags);
}
else return_to_caller('editTags.php','save=Okay');
?>
