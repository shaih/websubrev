<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';
$cName = CONF_SHORT.' '.CONF_YEAR;

// If the guidelines were specified, update the review guidelines file
require 'review/revFunctions.php';
if (isset($_POST["setGuidelines"])) {
  $links = show_rev_links(1);
  $schedule = trim($_POST["schedule"]);
  if (!empty($schedule))
    $schedule = "<h2>Schedule</h2>\n".nl2br($schedule)."\n";

  $assignments = trim($_POST["assignments"]);
  if (!empty($assignments))
    $assignments = "<h2>Assignments</h2>\n".nl2br($assignments)."\n";

  $externalRevs = trim($_POST["externalRevs"]);
  if (!empty($externalRevs))
    $externalRevs = "<h2>External reviewers</h2>\n".nl2br($externalRevs)."\n";

  $writingReviews = trim($_POST["writingReviews"]);
  if (!empty($writingReviews))
    $writingReviews= "<h2>Writing a Report</h2>\n".nl2br($writingReviews)."\n";

  $grades = showSemantics("Overall grades", $_POST["grade"], MAX_GRADE, false);
  $confLvls = showSemantics("Confidence leveles",
			    $_POST["conf"], MAX_CONFIDENCE, false);
  if (is_array($criteria)) {
    $auxGrades = "";
    $i = 1;
    foreach ($criteria as $cr) {
      $auxGrades .= showSemantics($cr[0], $_POST["grade_{$i}"], $cr[1], false);
      $i++;
    }
  }

  $reportContent = trim($_POST["reportContent"]);
  if (!empty($reportContent))
    $reportContent = "<h3>Report contents</h3>\n".nl2br($reportContent)."\n";

  $contactAuthors = trim($_POST["contactAuthors"]);
  if (!empty($contactAuthors))
    $contactAuthors = "<h2>Contacting authors</h2>\n".nl2br($contactAuthors)."\n";

  $discussPhase = trim($_POST["discussPhase"]);
  if (!empty($discussPhase))
    $discussPhase= "<h2>The Discussion Phase</h2>\n".nl2br($discussPhase)."\n";

  $closing = nl2br(trim($_POST["closing"]));

  $guidelines =<<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Review Guidelines for $cName</title>
</head>
<body>
$links
<hr/>
<h1>Review Guidelines for $cName</h1>
$schedule

$assignments

$externalRevs

$writingReviews
<a name="grades"></a>
$grades
$confLvls
$auxGrades

$reportContent

$contactAuthors

$discussPhase<br/>
<br/>
$closing
<hr/>
$links
</body>
</html>

EndMark;

  // Move the old guidelines file to backup
  $gdFile = SUBMIT_DIR."/guidelines.html";
  $bkFile = SUBMIT_DIR."/guidelines.bak.html";
  if (file_exists($gdFile)) {
    if (file_exists($bkFile)) unlink($bkFile);
    rename($gdFile, $bkFile);
  }

  // Write the new file
  if (!($fd = fopen($gdFile, 'w'))) { // open for write
    exit("<h1>Cannot create the guidelines file at $gdFile</h1>\n");
  }
  if (!fwrite($fd, $guidelines)) {
    exit ("<h1>Cannot write into guidelines file $gdFile</h1>\n");
  }
  fclose($fd);
  chmod($gdFile, 0664); // makes debugging a bit easier
  header("Location: index.php");
  exit();
}
else if (isset($_POST["uploadGuidelines"])
	 && $_FILES['guidelinesFile']['size'] > 0
	 && is_uploaded_file($_FILES['guidelinesFile']['tmp_name'])) {
  // Move the old guidelines file to backup
  $gdFile = SUBMIT_DIR."/guidelines.html";
  $bkFile = SUBMIT_DIR."/guidelines.bak.html";
  if (file_exists($bkFile)) unlink($bkFile);
  rename($gdFile, $bkFile);

  // Write the new file
  move_uploaded_file($_FILES['guidelinesFile']['tmp_name'], $gdFile)
    or rename($bkFile, $gdFile); // if failed - recover backup

  chmod($gdFile, 0664); // makes debugging a bit easier
  header("Location: index.php");
  exit();
}

// The default text for the various sections
$schedule = "&lt;dl&gt;&lt;dt&gt;XXX to YYY: Individual review. 
&lt;dd&gt;Reviewers do not communicate with each other about the submissions. Please enter all the assigned reviews by the deadline. 

&lt;dt&gt;XXX to YYY: Discussion phase.
&lt;dd&gt;Reviewers can see each other's reviews and discuss them using the review web site. Hopefully, we can decide (almost) everything by YYY.

&lt;dt&gt;XXX: Program-Committee meeting.

&lt;dt&gt;XXX to YYY: Wrap up.
&lt;dd&gt;Time to check the comments again before they are sent to the authors.
&lt;/dl&gt;";


$assignments =
"Most submissions are assigned to three reviewers (except PC-member papers that are assigned to more reviewers). Of course, you are allowed (and encouraged) to review papers that were not assigned to you, but please do so only after you reviewed your assigned lot. The chair(s) may also ask you to review additional submissions if the need arises.";


$externalRevs =
"You are encouraged to ask others outside the committee to help evaluate the papers. External readers should apply the usual confidentiality rules for conference submissions, in particular, not to distribute the paper to other people. It may be necessary to adjust an outside referee's score to make it compatible with your own system. You should understand the paper and external readers' comments well enough that you can discuss them in the public discussions phase. Please mark the names of the external reviewers in the subreferee box on the review form. After the final decision, the chair(s) will send out a list of all the sub-reviewers so you can check that none of your sub-reviewers were forgotten.

&lt;i&gt;Do not forward your account name and password to a subreferee&lt;/i&gt;. The review site contains very sensitive information. The program committee discussions should remain confidential within the program committee.";


$writingReviews =
"Each reviewer assigns a grade (1-".MAX_GRADE.") to each reviewed paper to reflect the recommendation on acceptance/rejection for the paper, as well as a weight (1-".MAX_CONFIDENCE.") to reflect the confidence of the reviewer in the recommendation. ";
if (is_array($criteria) && count($criteria)>0) $writingReviews .= 
"Grades for other specific criteria may also be entered. ";

$writingReviews.="Please use the scale below when completing the review form.\n\n";


if (MAX_GRADE==6) $gradeSemantics = array(
  1 => "Very weak submission, would be an embarrassment to accept",
  2 => "Weak submission, should be rejected",
  3 => "Rather neutral, but I lean toward rejection",
  4 => "Rather neutral, but I lean toward acceptance",
  5 => "Solid submission, should be accepted",
  6 => "Strong submission, would be a shame to reject"
);
else if (MAX_GRADE==9) $gradeSemantics = array(
  1 => "Devoid of any content/known result/wrong conference",
  2 => "Very weak submission, would be an embarrassment to accept",
  3 => "Weak submission, should be rejected",
  4 => "Rather neutral, but I lean toward rejection",
  5 => "Neutral: I cannot make up my mind about this submission",
  6 => "Rather neutral, but I lean toward acceptance",
  7 => "Solid submission, should be accepted",
  8 => "Strong submission, would be a shame to reject",
  9 => "Very strong submission, one of the best in this conference"
);
else $gradeSemantics = array();


if (MAX_CONFIDENCE==3) $confSemantics = array(
  1 => "An educated guess",
  2 => "Quite confident (but I did not check many of the details)",
  3 => "Confident (I know the area and I studied the paper in sufficient detail)"
);
else $confSemantics = array();


if (is_array($criteria)) {
  $i = 0;
  $auxGrades = array();
  foreach ($criteria as $cr) {
    $i++;
    if ($cr[0]=="Technical" && $cr[1]==3)
      $auxGrades[$i] = array(1 => "Low", 2 => "Adequate", 3 => "Good");
    else if ($cr[0]=="Editorial" && $cr[1]==3)
      $auxGrades[$i] = array(1 => "Poorly written",
			     2 => "Could use some improvement",
			     3 => "Well written");
    else if ($cr[0]=="Suitability" && $cr[1]==3)
      $auxGrades[$i] = array(1 => "Does not belong in this conference",
			    2 => "Marginal w.r.t. the scope of the conference",
			    3 => "Fits the conference");
    else $auxGrades[$i] = array();
  }
}


$reportContent = "Reports will consist of grades, a weight, and comments. The comments should help the authors as well as other committee members understand your opinions. Please don't write one-line reviews and don't over-use committee-only comments. There is no need to copy the comments to authors in the committee-only field. The bulk of the report should be visible to the authors (including summary of contributions, general evaluation, and specific comments). Authors of rejected papers deserve to know why their papers were rejected, and we want accepted papers to be improved based on our comments.

There is no additional structure to the reports that you have to follow. Here are a few points that you may want to refer to when organizing and writing your report.
&lt;ul&gt;&lt;li&gt; Summary of the problem and paper contribution. 

&lt;/li&gt;&lt;li&gt; What is best about the paper: new ideas, proofs, simplifications, formalizations, implementation, performance improvement, new insight, etc.

&lt;/li&gt;&lt;li&gt; What are the paper weaknesses: lack of originality, small increment over previous work, unsubstantiated claims, bad presentation, insufficient discussion of relation with prior work etc.

&lt;/li&gt;&lt;li&gt; Target audience: who will be interested in the results, who will be benefited from its publication in the proceedings, who will want to hear the talk at the conference.

&lt;/li&gt;&lt;li&gt; Summary of recommendation: Accept/Reject, main reason.

&lt;/li&gt;&lt;li&gt; Detailed technical comments.&lt;/li&gt;&lt;/ul&gt;";


if (ANONYMOUS) $contactAuthors=
"If you need to contact the authors of a submission, ask the chair(s) to contact them on your behalf.";

else $contactAuthors=
"If you need to contact the authors of a submission, you can do this either through the chair(s) or directly. In the latter case, please let the chair(s) know that you contacted the authors.";


$discussPhase = 
"Please use the web site discussion boards for the discussions (and not the e-mail list); this will ensure that people who join in the discussion later can follow its history. (Conversation on the discussion boards are not accessible to program committee members who are authors of the paper in question or otherwise have a conflict of interest.)";


$closing = "Have fun.\n\nThe chair(s)";


// Show the forms with the default text in, and let the chair edit it

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<style type="text/css">
h1 {text-align: center;}
</style>
<title>Set Review Guideline for $cName</title>
</head>
<body>
$links
<hr />
<h1>Set Review Guideline for $cName</h1>
You can either upload your own guidelines HTML file or use the HTML text
in the form below. A reasonable algorithm is to first use the form below,
then download <a href='../review/guidelines.php'>the resulting guidelines 
file</a> and save it locally, and then edit it to suit your needs and upload
it back to the server.<br/>
<br/>
<form action="guidelines.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20000000">
<input type="submit" name="uploadGuidelines" value="Upload Review Guidelines File:"><input name="guidelinesFile" size="60" type="file">
</form>
<hr/>
<h2>HTML Guidelines Form</h2>

Use this form to edit the guidelines for reviewers. If you leave empty any of the text-areas, the corresponding section will not appear in the guidelines page. (Please note that newlines in the text below will be replaced by &lt;br/&gt; in the guidelines HTML page.)<br/>
<br/>
<b>TIP:</b> You can use the "Bottom greeting" to
add anything you want to the guidelines. Simply insert the following text
in the text-area at the bottom of this page.

<pre>
  &lt;h2&gt;Title-of-new-section&lt;/h2&gt;
  whatever-you-want-to-say
</pre>


<form action="guidelines.php" enctype="multipart/form-data" method="post">
<h2>Schedule</h2>
<textarea cols=80 rows=15 name="schedule">$schedule</textarea>

<h2>Assignments</h2>
<textarea cols=80 rows=5 name="assignments">$assignments</textarea>

<h2>External reviewers</h2>
<textarea cols=80 rows=14 name="externalRevs">$externalRevs</textarea>

<h2>Writing a Report</h2>
<textarea cols=80 rows=5 name="writingReviews">$writingReviews</textarea>

EndMark;

print showSemantics("Overall grades",
		    $gradeSemantics, MAX_GRADE, true, "grade");
print showSemantics("Confidence levels",
		    $confSemantics, MAX_CONFIDENCE, true, "conf");
$i = 0;
if (is_array($criteria)) foreach ($criteria as $cr) {
  $i++;
  print showSemantics($cr[0], $auxGrades[$i], $cr[1], true, "grade_{$i}");
}

print <<<EndMark
<h3>Report contents</h3>
<textarea cols=80 rows=27 name="reportContent">$reportContent</textarea>

<h2>Contacting authors</h2>
<textarea cols=80 rows=5 name="contactAuthors">$contactAuthors</textarea>

<h2>The Discussion Phase</h2>
<textarea cols=80 rows=5 name="discussPhase">$discussPhase</textarea>

<h2>Bottom greeting</h2>
<textarea cols=80 rows=14 name="closing">$closing</textarea><br/>
<br/>

<input type="submit" name="setGuidelines" value="Store These Review Guidelines">
</form>
<hr/>
$links
</body>
</html>

EndMark;


function showSemantics($title, $semantics, $max, $acceptInput, $name="X")
{
  // No semantics defined and you don't want to define them - return
  if (!$acceptInput && (!is_array($semantics) || count($semantics)==0))
    return "";

  $html = "<dl>\n<dt><b>$title:</b>\n";

  for ($i=1; $i<=$max; $i++) {
    $html .= "<dd>$i: ";
    if ($acceptInput) $html .= "<input type=text name=\"{$name}[$i]\" size=80 value=\"".(isset($semantics[$i]) ? $semantics[$i] : '')."\">\n";
    else $html .= (isset($semantics[$i]) ? $semantics[$i] : '')."\n";
  }
  $html .= "</dl>\n";
  return $html;
}

?>
