<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
//if (file_exists('../init/confParams.php')) { // Already customized
//  header("Location: index.php");
//  exit();
//}
$webServer = $_SERVER['HTTP_HOST'];
if ($webServer=='localhost' || $webServer=='127.0.0.1') $webServer='';

// guess what year is the conference
$month = date('n');
$year = date('Y');
if ($month>6) $year++;

// Set some default name for the database
$rnd = mt_rand() % 100;
$dbName = "Conf{$rnd}_$year";

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Creating a New Submission/Review Site</title>
<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<style type="text/css">
tr {vertical-align: top;}
h1 {text-align: center;}
</style>
<script type="text/javascript" src="../common/validate.js"></script>
<script language="Javascript" type="text/javascript">
<!--
function checkform( form )
{
  // Checking that all the mandatory fields are present
  var pat = /^\s*$/;
  var st = 0;
  if (pat.test(form.webServer.value))   { st |= 1; }
  if (form.localMySQL[1].checked &&
      pat.test(form.MySQLhost.value))   { st |= 2; }
  if (pat.test(form.chair.value))       { st |= 4; }
  if (pat.test(form.admin.value))       { st |= 8; }
  if (pat.test(form.confDB.value))      { st |= 16; }

  if (st != 0) {
    alert( "Some mandatory fields are missing" );
    if (st & 1)       { form.webServer.focus(); }
    else if (st & 2)  { form.MySQLhost.focus(); }
    else if (st & 4)  { form.chair.focus();     }
    else if (st & 8)  { form.admin.focus();     }
    else if (st & 16) { form.confDB.focus();    }

    return false ;
  }

  st = 0;
  if (pat.test(form.rootNm.value))  { st |= 1; }
  if (pat.test(form.rootPwd.value)) { st |= 2; }
  if (pat.test(form.user.value))    { st |= 4; }
  if (pat.test(form.pwd.value))     { st |= 8; }

  if ((st & 3) && (st & 12)) {
    alert( "You must specify either the MySQL administarot name and password, or the name and password of a user that can access the conference database" );
    form.rootPwd.focus();
    return false ;
  }
  return true ;
}
//-->
</script>
</head>
<body>

<h1>Creating a New Submission/Review Site</h1>
This page lets you create a new submission and review site.
(The newly created site would later need to be "customized" for your
conference.) Here you can only specify sytem parameters, such as
username/pwd for MySQL, a local directory where the submissions are
kept, etc.<br/>
<br/>
<form action="doInitialize.php"  onsubmit="return checkform(this);" enctype="multipart/form-data" method="post">

<table cellpadding="6">
<tbody>
<tr><td class=rjust><a href="../documentation/chair.html#webServer" target="documentation" title="click for more help">Web&nbsp;Server:</a></td>
  <td><input name="webServer" type="text" value="$webServer" size="60"><br/>
    The DNS name or IP address of the web-server (e.g., <tt>www.myConf.org</tt> or <tt>18.7.22.83</tt>)</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#SQLserver" target="documentation" title="click for more help">MySQL&nbsp;Server:</a></td>
  <td><input name="localMySQL" type="radio" value="yes" checked="checked">
    The MySQL server runs on the same host as the web server<br/>
    <input name="localMySQL" type="radio" value="no">The MySQL server runs
    on a different host.<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Host name
    (or IP address) is: <input name="MySQLhost" size="40" type="text"></td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;Administrator:</a></td>
  <td>Name: <input name="rootNm" size="32" value="root" type="text"> 
    &nbsp;&nbsp; Password: <input name="rootPwd" size="32" type="password">
    <br/>A MySQL user that can create new databases and add new users.
    Specify these details <br/>(if you know them) to automatically
    create a database for this installation</td>
</tr>
<tr><td></td>
  <td>If you do not have the password of a MySQL root user, you should
    manually create the <br/>database and add a user that can access
    it (or ask the site administrator to do it for you) and <br/>enter the
    details below.</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;User:</a></td>
  <td>Name: <input name="user" size="32" type="text"> &nbsp; &nbsp;
    Password: <input name="pwd" size="32" type="password"></td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;Database&nbsp;Name:</a></td>
  <td><input name=confDB size=40 type=text value="$dbName"> (e.g., FOCS2009)
    <br/>Either the name of an existing database (if you specified username
     and password above), or the<br/>name for the newly created database
     (if you specified the administrator name and password above).</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#UploadDir" target="documentation" title="click for more help">UPLOAD&nbsp;Directory:</a></td>
  <td><input name="subDir" size="90" type="text"><br/>
    A directory on the server where the submissions would be stored, must
    be writable by the <br/>web-server. If this field is left empty, it
    defaults to the <tt>subs</tt> subdirectory under the BASE directory.
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#chairEmail" target="documentation" title="click for more help">Chair&nbsp;Email:</a></td>
  <td><input name="chair" size="90" type="text" onchange="return checkEmail(this)"><br/>
     Only one address (e.g., <tt>My Name &lt;chair@myConf.org&gt;</tt> or <tt>My.Email@company.com</tt>)</td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#adminEmail" target="documentation" title="click for more help">Administrator&nbsp;Email:</a></td>
  <td><input name="admin" size="90" type="text" onchange="return checkEmail(this)"><br/>
    Who should get the angry emails when there are problems with the site?</td>
</tr>
<tr><td class=ctr colspan="2" >
  <input value="         Submit         " type="submit"></td>
</tr>
</tbody></table>
</form>
</body>
</html>

EndMark;
?>