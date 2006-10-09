<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication=true;
require 'header.php';   // defines $pcMember=array(id, name, ...)
require 'store-review.php';
$revId  = (int) $pcMember[0];
$revName= htmlspecialchars($pcMember[1]);
$disFlag= (int) $pcMember[3];
$pcmFlags= (int) $pcMember[5];
$confName = CONF_SHORT . ' ' . CONF_YEAR;

$links = show_rev_links();
print <<<EndMark
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML Transitional 4.01//EN"
  "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<link rel="stylesheet" type="text/css" href="../review.css" />
<style type="text/css">
h1 { text-align: center; }
</style>
<title>Browse Old Reviews</title>
</head>
<body>
$links
<hr/>
<h1>Browse Your Old Reviews</h1>
Below is a list of submissions for which you have older versions of your
review. Choose the version that you want to see and then click the "View"
button.
<br/>
<br/>
<table><tbody>
<tr><td><b>Choose old review</b></td><td><b>Submission</b></td>
EndMark;

$qry ="SELECT s.subId subId, title, r.version version, UNIX_TIMESTAMP(r.whenEntered)
  FROM submissions s, reportBckp r WHERE r.subId=s.subId AND r.revId=$revId
  ORDER BY subId ASC, version DESC";
$res = db_query($qry, $cnnct);
$subId = -1;
$title = '';
while ($row=mysql_fetch_row($res)) {
  if ($row[0]!=$subId) {    // finish previous row and start a new one
    if ($subId>0) {
      print "  </select><input type=submit value=View>\n";
      print "  <input type=hidden name=subId value=$subId>\n";
      print "  <input type=hidden name=revId value=$revId></form></td>\n";
      print "  <td>$subId.&nbsp;$title</td>\n";
    }
    $subId = $row[0];
    $title = $row[1];
    print "</tr>\n<tr><td><form action=receipt-report.php method=GET>\n";
    print "  <SELECT name=bckpVersion>";
  }
  $version = (int) $row[2];
  $when = utcDate('M-j H:i (T)', $row[3]);
  print "  <option value=$version>$when</option>\n";
}
if ($subId>0) {
  print "  </select><input type=submit value=View>\n";
  print "  <input type=hidden name=subId value=$subId>\n";
  print "  <input type=hidden name=revId value=$revId></form></td>\n";
  print "  <td>$subId.&nbsp;$title</td>\n";
}
else          print "</tr>\n<tr><td colspan=6>No old reviews found</td>";
print <<<EndMark
</tr>
</tbody></table>
<hr/>
$links
</body>
</html>

EndMark;
?>
