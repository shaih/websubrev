<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 $needsAuthentication = true; 
require 'header.php';

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");

$cName = CONF_SHORT.' '.CONF_YEAR;
$links = show_chr_links();
$cmrDdline = utcDate('r (T)', CAMERA_DEADLINE);

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 {text-align: center;}
h2 {text-align: center;}
</style>
<title>Generate Accept/Reject Letters</title>
</head>
<body>
$links
<hr />
<h1>Generate Accept/Reject Letters</h1>
<h2>$cName</h2>

EndMark;

// the default accept letter
$accSbjct = ACCEPT_SBJCT;
if (empty($accSbjct)) $accSbjct="Your submission was accepted to $cName";

$acc = ACCEPT_LTR;
if (empty($acc)) $acc = 'Dear <$authors>,

It is our pleasure to inform you that your submission

  "<$title>"

was accepted to '.$cName.'. Congratulations.

Please confirm receipt of this email, and provide us with the
(corrected) title and complete list of authors and affiliations.
This will allow us to distribute the list of accepted papers.

The selection of the papers was a challenging and difficult task. The
Program Committee members have put in a significant effort in order to
provide useful feedback to the authors, but due to time constraints, this
was not always possible.

In a few days, we will forward the comments on your paper and the
instructions for producing and sending the final version. You will
need your submission-ID and password in order to upload the camera-ready
version. These are:

Submission-ID: <$subId>
Password:      <$subPwd>

In order to be included in the proceedings, the final version of your paper
must be received no later than '.$cmrDdline.'.
This is a firm deadline.

Thank you very much for contributing to '.$cName.'.
We are looking forward to seeing you at the conference.

Sincerely,

'.$cName.' program chair(s)';
$acc = htmlspecialchars($acc);

// the default reject letter
$rejSbjct = REJECT_SBJCT;
if (empty($rejSbjct)) $rejSbjct="Your $cName submission";

$rej = REJECT_LTR;
if (empty($rej)) $rej = 'Dear <$authors>,

We are sorry to inform you that your submission

  "<$title>"

was not accepted to '.$cName.'.

We received many good submissions, but could only accept a small number
of them to the program.

The selection of the papers was a challenging and difficult task. The
Program Committee members have put in a significant effort in order to
provide useful feedback to the authors, but due to time constraints, this
was not always possible. We will send you the comments on your paper in a
few days. 

Thank you very much for submitting your work to '.$cName.',
and we hope to see you at the conference.

Sincerely,

'.$cName.' program chair(s)';

$rej = htmlspecialchars($rej);

// Allow the chair to customize the emails
print <<<EndMark
<p>
Use the forms below to customize your accept/reject letters. The email
notifications will be sent when you hit the <tt>Send</tt> button at the
bottom of each form. You can also make changes to the text of these
letetrs and then save it without sending the email yet by using the
<tt>Save Text</tt> button instead. Note that you have to send the emails
<b>separately to the accepted submissions and to the rejected ones</b>.
</p>
<p>If you want to send different messages to some particular submissions
(e.g. conditional accepts), use for that purpose the generic interface
for <a href="emailAuthors.php">sending email to authors</a>.
</p>
<p>You can include in the text any of the keywords
<code>&lt;&#36;authors&gt;</code>, 
<code>&lt;&#36;title&gt;</code>,
<code>&lt;&#36;subId&gt;</code>,
<code>&lt;&#36;subPwd&gt;</code>, and
<code>&lt;&#36;comments&gt;</code>, and they will be replaced by the
authors, title, submission-ID, password, and comments-to-authors as
they appear in the database. To be recognized as keywords, these words
MUST include the '&lt;' and '&gt;' characters and the dollar-sign.
The keywords <code>&lt;&#36;title&gt;</code> and
<code>&lt;&#36;subId&gt;</code> can also be included in the subject
line.
</p>
<h3>Acceptance letters</h3>
<form accept-charset="utf-8" name="acceptLetters" action="doEmailAuthors.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="saveText_ACC" value="true">
<input type="hidden" name="emailTo" value="AC">
Subject: <input type=text name="subject" size=90 maxlength=80 value="$accSbjct">
<br/>
<textarea name="message" cols=80 rows=13>$acc</textarea>
<br/>
<input type="submit" name="notifySubmitters" value="Send Acceptance Notifications">
or only
<input type="submit" name="saveOnly" value="Save Text">
</form>

<h3>Rejection letters</h3>
<form accept-charset="utf-8" name="rejectLetters" action="doEmailAuthors.php" enctype="multipart/form-data" method="post">
<input type="hidden" name="saveText_REJ" value="true">
<input type="hidden" name="emailTo" value="RE">
Subject: <input type=text name="subject" size=90 maxlength=80 value="$rejSbjct"><br/>
<textarea name="message" cols=80 rows=13>$rej</textarea>
<br/>
<input type="submit" name="notifySubmitters" value="Send Rejection Notifications">
or only
<input type="submit" name="saveOnly" value="Save Text">
</form>
<hr />
$links
</body>
</html>

EndMark;
exit();
?>
