<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true;
require 'header.php';
$links = show_chr_links();
print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 { text-align:center; }
</style>

<title>Assignments of Submissions to Reviewers</title>
</head>
<body>
$links
<hr/>
<h1>Assignments of Submissions to Reviewers</h1>
You have several interfaces available to help you assign submissions to
PC members. Remember that all these interfaces work on a "sketch copy"
of the assignments, which is only visible to the chair. To let the PC
members see their assigned submissions, you need to check the appropriate
box next to the submit buttons in the matrix or list interfaces.
(<a target=documentation href="../documentation/chair.html#scratchAssign"
title="Click for more information">explain this</a>)

<form accept-charset="utf-8" action="scrapAssignments.php" enctype="multipart/form-data" method="post">
<ul>
<li>
<a href="assignmentMatrix.php">The matrix interface</a> displays a matrix of reviewers and submissions, and lets you check boxes to assign submissions to reviewers.
<br/><br/>
</li>
<li>
<a href="assignmentList.php">The list interface</a> displays a list of submissions, and lets you assign reviewers to submissions by typing in their names.<br/><br/>
</li>

EndMark;

if (defined('REVPREFS') && REVPREFS) print <<<EndMark
<li>
<a href="autoAssign.php">The Auto-assignment page</a> allows you to automatically compute an assignment of submissions to reviewers.
The software respects any manual assignments that you made (from the matrix or list interfaces), and uses the reviewer preferences and a network-flow algorithm to complete the assignments. (You can then use the matrix or list interfaces to modify the automatically-generated assignment.) It is recommended that you <a href="conflicts.php">indicate conflict of interests</a> before using the Auto-assignment functionality.
<br/></br>
</li>

EndMark;

print <<<EndMark
<li><a href="assignChairPrefs.php">The chair preferences page</a> lets you record non-binding preferences that you have regarding which reviewer should or should not review what submission. 
These preferences are then displayed for you in the matrix interface (by coloring the check-boxes <span style="color: green;">green</span> or <span style="color: red;">red</span>).<br/>
<br/> 
EndMark;
if (defined('REVPREFS') && REVPREFS) print <<<EndMark
In addition, your choices on that page are used to modify the reviewer
preferences during the auto-assignment procedure: When you indicate that
you DO NOT want a PC member to review a submission, the preference of
the reviewer is decreased by two points (e.g., if the reviewer indicated
a preference of 5, it is treated as a preference of 3). 
If you indicate that the you want a PC member to review a submission, and
if the preference of the reviewer is 3 or more, then it is increased by one
point (e.g., if the reviewer indicated a preference of 3, it is treated as
a preference of 4, etc.)<br/>
<br/>
EndMark;

print <<<EndMark
</li> 
<li>
You can <a href="getSketchAssign.php">backup the current "sketch assignment" to your local machine</a> as a text file. Later, if you want to revert to the current sketch assignments then you can upload this text file back to the server using the "Upload Assignment File" button below:
<p>
<input name="assignmnetFile" size="80" type="file">
<input type="submit" name="upload" value="Upload Assignment File">
</p>
</li>
<li>
Also, you can always start from scratch by using the clear-all button, or
reset the scratch assignments to the assignments that are currently
visible to the reviewers.
<p>
<input type="submit" name="clearAll" value="Clear All Assignments"> or 
<input type="submit" name="reset2visible" value="Reset to Visible Assignments">
</p></li>
</ul></form><br/>

EndMark;
if (defined('REVPREFS') && REVPREFS) print <<<EndMark
<h2>A suggested procedure for using the Auto-assignment functionality</h2>
<ol>
<li> <i>(optional)</i> You can use the <a href="assignChairPrefs.php">chair preferences page</a> to record your own (non-binding) preferences as to who should or should not review what submission.<br/>
<br/></li>
<li> Before using the auto-assignments, make sure that you record all <a href="conflicts.php">conflict-of-interests</a>.<br/>
<br/></li>
<li> You will probably also want to make some manual assignments before computing the auto-assignment, using either the <a href="assignmentMatrix.php">matrix interface</a> or the <a href="assignmentList.php">list interface</a>. Some reasons for using manual assignments before running the Auto-assignment procedure include:
<ul>
<li> Making absolutely sure that PC-member X is assigned to submission Y (e.g., because this PC member is a domain expert on the topic).</li>
<li> Ensuring that there is at least one reviewer that reads both submission Y and submission Z.</li> 
<li> Assigning a reduced load to PC-member X. (You can do this by manually assigning this PC-member however many submissions that you want, and then excluding him/her from the Auto-assignment procedure.)
</ul>
Note that the Auto-assignment algorithm will respect manual assignments that you made before running it. For example, if every submission needs three reviewers  and you already assigned one reviewer to submission Y, then the algorithm will only assign two more reviewers to that submission.<br/>
<br/></li>
<li> After making some manual assignments, you should <a href="getSketchAssign.php">backup these assignments to your local machine</a> (in case you want to revert to them later).<br/>
<br/></li>
<li> Now you can go to the <a href="autoAssign.php">Auto-Assignment page</a> and let the software compute an assignment for you.<br/>
<br/></li>
<li> Repeat steps 3-5 until the assignment looks good to you (using the backup copy that you made in step 4). Also, you can manually modify the Auto-assignment from the <a href="assignmentMatrix.php">matrix interface</a> or the <a href="assignmentList.php">list interface</a>.
</li>
</ol>
EndMark;
print <<<EndMark
<hr/>
$links
</body>
</html>
EndMark;
?>
