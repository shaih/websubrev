<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; // Just a precaution
require 'header.php';

if (!USE_PRE_REGISTRATION) { exit("<h1>Pre-registration is Disabled</h1>"); }
if (PERIOD>PREREG_PERIOD) { exit("<h1>Pre-registration is Closed</h1>"); }

$regDdline = utcDate('r (T)', REGISTER_DEADLINE);
$now= utcDate('r (T)');

$links = show_chr_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head><meta charset="utf-8">
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Closing Pre-Registration</title>
</head>
<body>
$links
<hr/>
<h1>Closing Pre-Registration</h1>
<center>
<table><tbody>
<tr><td>Pre-registration deadline:</td><td style="color: red;">$regDdline</td></tr>
<tr><td>The time now is:</td><td style="color: blue;">$now</td></tr>
</tbody></table>
</center>

<h2>Are you sure you want to close the pre-registration?</h2>
To close the pre-registration, click on "Close Pre-Registration" below.
Authors who already registered their submission will still be able to
submit their paper (and revise it) until you close the submission site.
<p> </p>
<form accept-charset="utf-8" action="doClosePrereg.php">
<input value="Close Pre-Registration" type="submit">
</form>
<hr/>
$links
</body>
</html>

EndMark;
?>
