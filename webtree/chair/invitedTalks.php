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

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<title>Add an invited talk for $cName</title>

<link rel="stylesheet" type="text/css" href="../common/submission.css"/>
<style type="text/css">
tr { vertical-align: top; }
</style>
</head>
<body>
$links
<hr />
<h1>Add an invited talk for $cName</h1>
Using this form will add another "accepted submission" to the system.
The "contact author" of this new submission will be able to use the
camera-ready submission form to upload a writeup to the server, just like
any other accepted submision. This could be useful if you want to add an
invited talk to the program.<br/>

<form accept-charset="utf-8" action=doInvitedTalks.php enctype="multipart/form-data" method=POST>
<table cellpadding=6>
<tbody>
  <tr>
    <td style="text-align: right;">Title:</td>
    <td><input name="title" size="90" type="text">
  </tr>
  <tr>
    <td style="text-align: right;">Speaker:</td>
    <td><input name="authors" size="70" type="text">
  </tr>
  <tr>
    <td style="text-align: right;">Affiliations:</td>
        <td><input name="affiliations" size="70" type="text">
  </tr>
  <tr>
    <td style="text-align: right;">Contact Email:</td>
    <td><input name="contact" size="70" type="text"><br/>
    The email address of the person who will upload this talk to the server
    </td>
  </tr>
  <tr>
    <td></td>
    <td><input value="Add this talk to the program" type="submit">
    </td>
  </tr>
</tbody>
</table>
</form>
<hr/>
$links
</body>
</html>

EndMark;
?>
