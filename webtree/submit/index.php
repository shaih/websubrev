<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
require 'header.php'; // brings in the constants file and utils file
$confName = CONF_SHORT . ' ' . CONF_YEAR;

$links = show_sub_links(2);
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta charset="utf-8">
<title>Submission/Revision Instructions, $confName</title>
<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
</head>

<body>
$links
<hr />

EndMark;

// Final-version submissions
if (PERIOD >= PERIOD_CAMERA) { 
  $timeleft = show_deadline(CAMERA_DEADLINE);
  $deadline = 'Deadline is '
             . utcDate('r (T)', CAMERA_DEADLINE); // when is the deadline
  $cameraInstructions = htmlspecialchars(CAMERA_INSTRUCTIONS);

  print <<<EndMark
<h1>Final-Version Submission Instructions, $confName</h1>
<h3 class=timeleft>$deadline<br/>
$timeleft</h3>

Use the <a href="cameraready.php">camera-ready revision form</a> (with
the password that you got when you submitted the paper) to submit the
camera-ready version. Instructions for preparing the camera-ready
version are available <a href="cameraInstructions.php">on this page</a>.
Don&prime;t forget to sign and return also the copyright form by the
deadline.
<hr />
$links
$footer
</body></html>
EndMark;
  exit();
} /********** end if (PERIOD >= PERIOD_CAMERA) ************/


// Initial registration/submission period
if (USE_PRE_REGISTRATION) { // pre-registration is required
  if (PERIOD==PERIOD_PREREG) {
    $subLink = '<a href="submit.php">Registration form</a> to register a new submission.';
  } else {                         // pre-registration deadline expired
    $subLink = 'The deadline for registering submissions has expired.';
  }
  $submit = "registering";
  $revise = "submission/revision";
  $revLink = '<a href="revise.php">Submission/Revision form</a> to submit/revise the actual paper after registering it.';
}
else {                          // pre-registration is NOT required
  $submit = "submitting";
  $revise = "revision";
  $subLink = '<a href="submit.php">Submission form</a> to submit a new paper.';
  $revLink = '<a href="revise.php">Revision form</a> to revise a submission before the deadline.';
}

if (PERIOD==PERIOD_PREREG) {
  $ddline = REGISTER_DEADLINE;
  $whatDdline = 'Registration';
} else {
  $ddline = SUBMIT_DEADLINE;
  $whatDdline = 'Submission';
}
  
$timeleft = show_deadline($ddline);
$deadline = "$whatDdline deadline is ". utcDate('r (T)', $ddline); // when is the deadline

print <<<EndMark
<h1>Submission Instructions, $confName</h1>
<h3 class=timeleft>$deadline<br/>
$timeleft</h3>

The following forms are available:
<ul>
<li>$subLink</li>
<li>$revLink</li>
<li><a href="withdraw.php">Withdrawal form</a> to withdraw a submission
before the deadline.</li>
</ul>
These forms are fairly self-explanatory, and additional documentation can
be found <a target="documentation" href="../documentation/submitter.html">
here</a>. When $submit a new paper, you get a submission-ID and a
password that you can then use with the $revise and withdrawal forms.
<i>Please save the submission-ID and password also after the deadline.</i>
You will need them to submit the final version of your paper, should
it be accepted to the conference.

<h2><A NAME="formats">Supported Formats</A></h2>
The following formats are supported for submissions to $confName:<br />

<table style="text-align: left;" cellspacing="5">
<tbody>
  <tr><th>Format</th> <th>Extension</th> <th>MIME type</th></tr>

EndMark;
foreach ($confFormats as $ext => $f) { // one row for each format
  print "  <tr>\n";
  print "    <td>$f[0]</td><td>.$ext</td><td>$f[1]</td>\n";
  print "  </tr>\n";
}
print <<<EndMark
</tbody></table>
<hr />
$links
$footer
</body>
</html>

EndMark;
?>
