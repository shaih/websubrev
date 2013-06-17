<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$MYSQL_HOST = $MYSQL_DB = $MYSQL_USR = $MYSQL_PWD = $SUBMIT_DIR
  = $LOG_FILE = $ADMIN_EMAIL = $CONF_SALT = $MYSQL_PREFIX = '';
$prmsFile = '../init/confParams.php';
if (file_exists($prmsFile)) { // file already there
  $lines=file($prmsFile);
  foreach ($lines as $line) {
    $i = strpos($line, '=');           // look for NAME=value
    if ($i==0 || substr($line,0,2)=='//') 
      continue; // comment or no 'NAME=' found
    $nm = substr($line,0,$i);
    $vl = rtrim(substr($line,$i+1));
    if ($nm=='MYSQL_HOST'      || $nm=='MYSQL_DB'   || $nm=='MYSQL_USR'
	|| $nm=='MYSQL_PWD'    || $nm=='SUBMIT_DIR' || $nm=='LOG_FILE'
	|| $nm=='ADMIN_EMAIL'  || $nm=='CONF_SALT'  || $nm=='BASE_URL'
	|| $nm=='MYSQL_PREFIX') {
      if (empty($vl)) die("<h1>Parameter $nm cannot be empty</h1>");
      $$nm = $vl;
    }
  }
}

$sqlHostClass = (empty($MYSQL_HOST))? '': " class='hidden'";
if ($MYSQL_HOST=='localhost' || $MYSQL_HOST=='127.0.0.1') $MYSQL_HOST='';
$remoteSQL = $localSQL = '';
if (empty($MYSQL_HOST))
  $localSQL = ' checked="checked"';
else {
  $remoteSQL = ' checked="checked"';
}

$webServer = $_SERVER['HTTP_HOST'];
if ($webServer=='localhost' || $webServer=='127.0.0.1') $webServer='';

// guess what year is the conference
$month = date('n');
$year = date('Y');
if ($month>6) $year++;

$sqlUserLine = '';
if (empty($MYSQL_USR) || empty($MYSQL_PWD)) {
  $sqlUserLine = "<tr><td class=rjust><a href='../documentation/chair.html#SQLdb' target='_blank' title='click for more help'>MySQL&nbsp;User:</a></td>
  <td>Name: <input name='user' size='32' type='text'> &nbsp; &nbsp;
   Password: <input name='pwd' size='32' type='password'><br/>
   If you did not specify the MySQL administrator above and you are using an existing database, enter here the details of a user that can access it (and create tables if needed).</td>
</tr>";
}

$uploadLine = '';
if (empty($SUBMIT_DIR)) {
  $uploadLine = "<tr><td class=rjust><a href='../documentation/chair.html#UploadDir' target='_blank' title='click for more help'>UPLOAD&nbsp;Directory:</a></td>
  <td><input name='subDir' size='90' type='text'><br/>
    A directory on the server where the submissions would be stored, must
    be writable by the web-server. If this field is left empty, it
    defaults to the <tt>subs</tt> subdirectory under the BASE directory.
</tr>";
}

$adminEmailLine = '';
if (empty($ADMIN_EMAIL)) {
  $adminEmailLine = "<tr><td class=rjust><a href='../documentation/chair.html#adminEmail' target='_blank' title='click for more help'>Administrator&nbsp;Email:</a></td>
  <td><input name='admin' size='90' type='text' class='required'><br/>Who should get the angry emails when there are problems with the site?</td>
</tr>";
}
// Set some default name for the database
$sqlDBLine = '';
if (empty($MYSQL_DB)) {
  $rnd = mt_rand() % 100;
  $MYSQL_DB = "Conf{$rnd}_$year";
  $sqlDBLine = "<tr><td class=rjust><a href='../documentation/chair.html#SQLdb' target='_blank' title='click for more help'>MySQL&nbsp;Database&nbsp;Name:</a></td>
  <td><input name='confDB' size='40' type='text' value='$MYSQL_DB'> (e.g., FOCS2009)<br/>
Specify here the name of the MYSQL database to use. If this is a new database then you must specify the MySQL administrator below.</td>
</tr>";
}

$sqlPrefixLine = '';
if (empty($MYSQL_PREFIX)) {
  $MYSQL_PREFIX = $MYSQL_DB;
  $sqlPrefixLine = "<tr><td class=rjust><a href='../documentation/chair.html#SQLdb' target='_blank' title='click for more help'>Table-name&nbsp;prefix:</a></td>
  <td><input name='SQLprefix' size='40' value='$MYSQL_PREFIX' type='text' class='required'> (e.g., FOCS2009)
<br/>
If you use the same SQL database for multiple conferences, specify a prefix for the names of the tables used for the current installation.</td>
</tr>";
}

print <<<EndMark
<!DOCTYPE HTML>
<html>
<head><meta charset="utf-8">
<title>Creating a New Submission/Review Site</title>
<link rel="stylesheet" type="text/css" href="../common/review.css"/>
<style type="text/css">
tr {vertical-align: top;}
h1 {text-align: center;}
</style>
<script src="../common/ui.js"></script>
</head>
<body>

<h1>Creating a New Submission/Review Site</h1>
<p>This page lets you create a new submission and review site.
(The newly created site would later need to be "customized" for your
conference.) Here you can only specify sytem parameters, such as
username/pwd for MySQL, a local directory where the submissions are
kept, etc.</p>

<form accept-charset="utf-8" action="doInitialize.php"  enctype="multipart/form-data" method="POST">

<table cellpadding="6">
<tbody>
<tr><td class="rjust"><a href="../documentation/chair.html#webServer" target="_blank" title="click for more help">Web&nbsp;Server:</a></td>
  <td><input name="webServer" type="text" value="$webServer" size="60" class="required"><br/>
    The DNS name or IP address of the web-server (e.g., <tt>www.myConf.org</tt> or <tt>18.7.22.83</tt>)</td>
</tr>
<tr{$sqlHostClass}><td class=rjust><a href="../documentation/chair.html#SQLdb" target="_blank" title="click for more help">MySQL&nbsp;Server:</a></td>
<td><input name="localMySQL" type="radio" value="yes"{$localSQL}>
    The MySQL server runs on the same host as the web server<br/>
    <input name="localMySQL" type="radio" value="no"{$remoteSQL}>The MySQL server runs
    on a different host.<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Host name
    (or IP address) is: <input name="MySQLhost" size="40" type="text" value="$MYSQL_HOST"></td>
</tr>
<tr><td class=rjust><a href="../documentation/chair.html#SQLdb" target="_blank" title="click for more help">Database&nbsp;details:</a></td>
<td><input type="radio" name="newDB" value="newDB" checked="checked"> 
Create a new database for this installation. You have to specify the MySQL admin details below.<br/>
<input type="radio" name="newDB" value="newTbls">
Use an existing database, but create new tables for this installation.<br/>
<input type="radio" name="newDB" value="existing">
Use an existing database, which already has all the tables in it. (Use this option if you already created the database manually.)</td>
</tr>
$sqlDBLine
$sqlPrefixLine
<tr><td class=rjust><a href="../documentation/chair.html#SQLdb" target="_blank" title="click for more help">MySQL&nbsp;Administrator:</a></td>
  <td>Name: <input name="rootNm" size="32" value="root" type="text"> 
    &nbsp;&nbsp; Password: <input name="rootPwd" size="32" type="password">
    <br/>A MySQL user that can create new databases and add new users.</td>
</tr>
$sqlUserLine
$uploadLine
<tr><td class=rjust><a href="../documentation/chair.html#chairEmail" target="_blank" title="click for more help">Chair&nbsp;Email:</a></td>
  <td><input name="chair" size="90" type="text" class="required"><br/>
    Only one address, e.g., <tt>My Name &lt;chair@myConf.org&gt;</tt> or <tt>My.Email@company.com</tt>. You will be able to add more PC chairs later.</td>
</tr>
$adminEmailLine
<tr><td class=ctr colspan="2" >
  <input value="         Submit         " type="submit"></td>
</tr>
</tbody></table>
</form>
</body>
</html>

EndMark;
?>