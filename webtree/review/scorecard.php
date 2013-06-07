<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
$revId = (int) $pcMember[0];
$revName = $pcMember[1];
$confName = CONF_SHORT . ' ' . CONF_YEAR;

if (isset($_GET['download'])) { // Display current scorecard

  // get all the reviews that this reviewer submitted
  $qry = "SELECT subReviewer, confidence conf, score, r.comments2authors cmnt,"
    ." r.comments2committee pcCmnt, r.comments2chair chrCmnt,"
    ." r.comments2self slfCmnt, title, authors, s.subId subId"
    ." FROM {$SQLprefix}submissions s, {$SQLprefix}reports r"
    ." WHERE r.subId=s.subId AND r.revId=? ORDER BY subId";

  $qry2 = "SELECT subId, gradeId, grade FROM {$SQLprefix}auxGrades WHERE revId=? ORDER BY subId, gradeId";

  $res = pdo_query($qry, array($revId));
  $auxRes = pdo_query($qry2, array($revId));
  
  $reviews = array();
  while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $subId = (int) $row['subId'];
    $reviews[$subId] = $row;
  }
  while ($row = $auxRes->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $row[0];
    $gId = (int) $row[1];
    $grade = isset($row[2]) ? ((int)$row[2]) : NULL;
    if (isset($reviews[$subId])) $reviews[$subId]["grade_{$gId}"] = $grade;
  }

  // add empty reviews for submissions that are assigned to this reviewer
  $qry = "SELECT a.subId, s.title, s.authors FROM {$SQLprefix}assignments a, {$SQLprefix}submissions s WHERE a.revId=? AND a.assign=1 AND s.subId=a.subId";
  $res = pdo_query($qry, array($revId));
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $subId = (int) $row[0];
    if (!isset($reviews[$subId])) {
      $reviews[$subId] = array('title'=>$row[1], 'authors'=>$row[2]);
    }
  }

  // sort by submission-ID
  ksort($reviews);

  header("Content-Type: text/plain");
  header('Content-Disposition: inline; filename="scorecard.txt"');
  print <<<EndMark
# SCORECARD for $revName, reviews for $confName
#################################################################
# A line that begins with the symbol '#' is considered a comment
# line and is ignored by the software <i>except when it is part of
# the comments to author/committee/chair</i>.
# 
# See the bottom of this file for formatting information, and
# try not to deviate significantly from the specified format.


EndMark;

  foreach ($reviews as $subId=>$review) {
    $title = trim($review['title']);
    if (strlen($title)>65) $title=substr($title,0,62).'...';
    print "$subId: $title\n";
    if (!ANONYMOUS) {
      print "AUTHORS: ".(isset($review['authors'])?$review['authors']:'')."\n";
    }
    print "SUBREVIEWER: ".(isset($review['subReviewer'])?$review['subReviewer']:'')."\n";
    print "SCORE: ".(isset($review['score'])?$review['score']:'')."\n";
    print "CONFIDENCE: ".(isset($review['conf'])?$review['conf']:'')."\n";
    if (is_array($criteria) && ($nCrit=count($criteria))>0) { 
      for ($i=0; $i<$nCrit; $i++) {
	print $criteria[$i][0].": ";
	if (isset($review["grade_{$i}"])) print $review["grade_{$i}"];
	print "\n";
      }
    }
    $cmnt2athr= isset($review['cmnt']) ? ("\n".wordwrap($review['cmnt'])) : '';
    $cmnt2PC  = isset($review['pcCmnt']) ? ("\n".wordwrap($review['pcCmnt'])) : '';
    $cmnt2chr = isset($review['chrCmnt']) ? ("\n".wordwrap($review['chrCmnt'])) : '';
    $cmnt2slf = isset($review['slfCmnt']) ? ("\n".wordwrap($review['slfCmnt'])) : '';
    print "AUTHOR-COMMENTS: ".$cmnt2athr."\n";
    print "PC-COMMENTS: ".$cmnt2PC."\n";
    print "CHAIR-COMMENTS: ".$cmnt2chr."\n";
    if (!empty($cmnt2slf)) print "SELF-COMMENTS: ".$cmnt2slf."\n";
    print "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\n\n";
  }

  print <<<EndMark
#################################################################
# Formatting information
#################################################################
# The reviews should look as follows:
# 
#     num: Title
#     AUTHORS: names (optional)
#     SUBREVIEWER: name (optional)
#     SCORE: num
#     CONFIDENCE: num
#     aux-criteria-1: num
#     aux-criteria-2: num
#     [...]
#     AUTHOR-COMMENTS: anything here is considered comments to the
#       authors
#     PC-COMMENTS: anything here is considered comments to 
#       the program committee
#     CHAIR-COMMENTS: anything here is considered comments
#       to the program chair
#     SELF-COMMENTS: anything here is considered notes to yourself,
#       no one else can see these comments
#     +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
#  
# Different reviews in the same file are separated with a line of '+'
# symbols (at least 20 '+' symbols, with no white-spaces or anything
# else). 
# 
# Each review starts by a line containing the submission number and
# optionally the title. The software ignores the title and only uses
# the submission number to identify the submission that you are
# reviewing. (It will, however, display a warning if you supply a
# title that is "too far from the right one"). You can optionally put
# the authors names in the next line, the software will ignore this.
# 
# Next you can put the name of a sub-reviewer. If this line is
# missing or the name left empty, the software will record no
# sub-reviewer for this review. 
# 
# Next comes lines for the score and confidence. If the num is not
# supplied, the software will record the special symbol 'ignore'.
# Next you can put lines for the other evaluation criteria that the
# chair specified (e.g., Technical, Editorial quality, etc). If the
# software does not recognize a criterion it will ignore that line
# and display a warning. 
#
# Next comes the comments. Anything after the tag "AUTHOR-COMMENTS:"
# (which must be at the beginning of a line) and on the following
# lines is considered comments-to-authors, until the end of the
# review or until a line that begins with "PC-COMMENTS:" or
# "CHAIR-COMMENTS:". Similarly, these two tags denote the beginning
# of the comments-to-committee or comments-to-chair, respectively.

EndMark;
  exit();
} // endif (isset($_GET['download']))

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
</style>

<title>Work with scorecard files for $confName</title></head>
<body>
$links
<hr/>
<h1>Work with scorecard files for $confName</h1>
Instead of uploading the reviews one at a time, you can prepare a file
with many reviews (called a <i>scorecard</i> file) and then upload all
the reviews to the server at once. 
As a starting point, you can <a href="scorecard.php?download=yes">
download your current scorecard file</a> and save it into a text file.
(This file contains all the reviews that you already uploaded, and
also empty reviews for the submissions that were assigned to you but
for which you did not yet upload a review.) Then you can fill that text
file with more reviews and upload it back to the server.<br/>

EndMark;
if (!defined('CAMERA_PERIOD')) print <<<EndMark
<br/>
<font color=red><big><strong>Warning:</strong></big></font>
a review that you upload to the server in this manner will
<b>overwrite any review that you previously uploaded</b>. For example,
if you uploaded a review via the web interface and then uploaded from
here a file that includes an old version of that review, then that "old
version" will overwrite the "newer version" that was uploaded before.<br/>
<br/>
<form accept-charset="utf-8" action="parseScorecard.php" enctype="multipart/form-data" method=post>
Scorecard file: <input type=file size=60 name=scorecard>
<input type=submit value="Upload">
</form>

EndMark;
print <<<EndMark
<hr/>
$links
</body>
</html>

EndMark;
?>
