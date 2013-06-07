<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
require 'storeReview.php';
$revId = (int) $pcMember[0];
$disFlag=(int) $pcMember[3];

$confName = CONF_SHORT . ' ' . CONF_YEAR;

if (defined('CAMERA_PERIOD'))
   exit("<h1>Site closed: cannot upload new reviews</h1>");

if (!isset($_FILES['scorecard']))    die("No scorecard file uploaded");
if ($_FILES['scorecard']['size']==0) die("Empty scorecard file uploaded.");

$tmpFile=$_FILES['scorecard']['tmp_name'];
$fName = SUBMIT_DIR."/scorecard_{$revId}_".date('is');
if (!move_uploaded_file($tmpFile,$fName)) {
  error_log(date('Ymd-His: ')."move_uploaded_file($tmpFile, $fName) failed\n", 3, LOG_FILE);
  die("Cannot move scorecard file");
}
if (!($fd=fopen($fName,'r')))        die("Could not open scorecard file");

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html><head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
</style>

<title>Upload Reviews for $confName</title></head>
<body>
$links
<hr/>
<h1>Upload Reviews for $confName</h1>

EndMark;

// process the reviews from the scorecard file one by one
while (nextReview($fd));
fclose($fd);
unlink($fName);

print <<<EndMark
<br/><br/>
Reviews uploaded successfully. Go back to <a href=".">review homepage</a>.
<hr/>
$links
</body>
</html>

EndMark;


function nextReview($fd) {
  global $links;

  $skip=false;
  $comments=0;
  $subId=$title=$authors = NULL;
  $score=$conf=$subrev = NULL;
  $cmnt=$pcCmnt=$chrCmnt=$slfCmnt ='';
  $auxGrades = array();
  while (true) {
    if (feof($fd) || ($line=fgets($fd))===false) {
      saveReview($subId,$title,$subrev,
		  $score,$conf,$auxGrades,$cmnt,$pcCmnt,$chrCmnt,$slfCmnt);
      return false; // last review in file
    }

    $line=rtrim($line); // trim trailing white spaces

    // look for record separation: a line of at least 20 '+'s and nothing else
    if (preg_match('/\+{20,}$/', $line)) {
      return saveReview($subId,$title,$subrev,$score,$conf,
			$auxGrades,$cmnt,$pcCmnt,$chrCmnt,$slfCmnt);
    }

    if ($skip || ($comments==0 && (empty($line) || $line[0]=='#')))
      continue; // skip to next line

    // first thing in a record must be subId
    if (!isset($subId)) {
      $subId = getSubId($title,$line);
      if (!$subId) {
	print "<b>Warning:</b> expected '<tt>sub-ID: title</tt>', found '<tt>$line</tt>'. Skipping this review.<br/>\n";
	$skip = true;
      }
      continue;
    }

    // As long as comments didn't statr, look for any "TAG: value" line
    if ($comments==0) {
      if (strncmp($line,"AUTHORS:",8)==0)
	continue;
      else if (strncmp($line,"SUBREVIEWER:",12)==0)
	$subrev=trim(substr($line,12));
      else if (strncmp($line,"SCORE:",6)==0)
	$score=trim(substr($line,6));
      else if (strncmp($line,"CONFIDENCE:",11)==0)
	$conf=trim(substr($line,11));
      else if (strncmp($line,"AUTHOR-COMMENTS:",16)==0) {
	$comments=1;
	$cmnt=trim(substr($line,16));
      }
      else if (strncmp($line,"PC-COMMENTS:",12)==0) {
	$comments=2;
	$pcCmnt=trim(substr($line,12));
      }
      else if (strncmp($line,"CHAIR-COMMENTS:",15)==0) {
	$comments=3;
	$chrCmnt=trim(substr($line,15));
      }
      else if (strncmp($line,"SELF-COMMENTS:",14)==0) {
	$comments=4;
	$slfCmnt=trim(substr($line,14));
      }
      else getAuxGrade($auxGrades,$line);

      continue;
    }

    // once comments start, look only for more comments
    if (strncmp($line,"AUTHOR-COMMENTS:",16)==0) {
	$comments=1;
	$cmnt.=trim(substr($line,16));
    }
    else if (strncmp($line,"PC-COMMENTS:",12)==0) {
      $comments=2;
      $pcCmnt.=trim(substr($line,12));
    }
    else if (strncmp($line,"CHAIR-COMMENTS:",15)==0) {
      $comments=3;
      $chrCmnt.=trim(substr($line,15));
    }
    else if (strncmp($line,"SELF-COMMENTS:",14)==0) {
      $comments=4;
      $slfCmnt.=trim(substr($line,14));
    }
    else switch($comments) { // continue with current comments
      case 1:
	$cmnt .= "\n".$line;
	break;
      case 2:
	$pcCmnt .= "\n".$line;
	break;
      case 3:
	$chrCmnt .= "\n".$line;
	break;
      case 4:
	$slfCmnt .= "\n".$line;
      default:
	break;
    }
  }
}

function getSubId(&$title, $line)
{
  // if ':' not found (or found as first char) return 0
  if (!($colon=strpos($line,':'))) return 0;

  $subId = substr($line, 0, $colon);
  $subId = (int) trim($subId);
  $title = trim(substr($line,$colon+1));

  return $subId;
}

function getAuxGrade(&$auxGrades, $line)
{
  // if ':' not found (or found as first char) return
  if (!($colon=strpos($line,':'))) return;
  $key = trim(substr($line, 0, $colon));
  $val = substr($line,$colon+1);
  $auxGrades[$key]=$val;
}

function saveReview($subId,$title,$subrev,
		     $score,$conf,$auxGrades,$cmnt,$pcCmnt,$chrCmnt,$slfCmnt)
{
  global $revId, $criteria, $disFlag;
  global $SQLprefix;

  if (!isset($subId) || $subId<=0) return true;

  // get the "auxiliary" grades
  $keys = array();
  $grades = array();
  $foundAuxGrades=false;
  for ($i=0; $i<count($criteria); $i++) {
    $key = $criteria[$i][0];
    $keys[$key] = true;
    if (isset($auxGrades[$key])) {
      $val = trim($auxGrades[$key]);
      if (!empty($val)) {
	$foundAuxGrades=true;
	$grades["grade_{$i}"] = $val;
      }
    }
  }

  // sanity-check: do not store empty reviews
  $cmnt = trim($cmnt);
  $pcCmnt = trim($pcCmnt);
  $chrCmnt = trim($chrCmnt);
  $slfCmnt = trim($slfCmnt);
  if (empty($score) && empty($conf) && !$foundAuxGrades && empty($cmnt) 
      && empty($pcCmnt) && empty($chrCmnt) && empty($slfCmnt)) {
    print "<b>Notice:</b> ignoring empty review for submission $subId: <tt>"
      .htmlspecialchars($title)."</tt><br/>\n";
    return true;
  }

  $add2watch = !$disFlag;
  $ret = storeReview($subId, $revId, $subrev, $conf, $score, $grades, $cmnt,
		     $pcCmnt, $chrCmnt, $slfCmnt, $add2watch);

  if ($ret==-1 || $ret==-2) return false; // unspecified subId or revId? something is wrong here..
  else if ($ret==-3) {
    print "<b>Warning:</b> submission-ID $subId not found<br/>\n";
    return true;
  }

  // warn if title is specified but "too far from the right one"
  if (!empty($title)) {
    $qry= "SELECT title FROM {$SQLprefix}submissions WHERE subId=?";
    $res = pdo_query($qry, array($subId));
    $row = $res->fetch(PDO::FETCH_NUM);
    $title1 = strtolower(substr($title, 0, 35));
    $title2 = trim($row[0]);
    $title2 = strtolower(substr($title2, 0, 35));
    $dist = levenshtein($title1, $title2); // calculate edit-distance
    if ($dist > 3) {
      if (strlen($title)>35)  $title1 = substr($title, 0, 35).'...';
      else                    $title1 = $title;      
      $title2 = trim($row[0]);
      if (strlen($title2)>35) $title2 = substr($title2, 0, 35).'...';
      print "<b>Warning:</b> incorrect title for submission-ID $subId,\n";
      print "Specified '<tt>$title1</tt>', but title is '<tt>$title2</tt>'<br/>\n";
    }
  }

  // print a warning for aux-grades that are not recognized
  foreach($auxGrades as $key=>$val) {
    if (!isset($keys[$key]))
      print "<b>Warning:</b> found '<tt>$key: $val</tt>', but '<tt>$key</tt>' is not recognized. Ignoring.<br/>\n";
  }

  return true;
}

?>