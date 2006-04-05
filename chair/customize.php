<?php
/* Web Submission and Review Software, version 0.51
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 chdir('..'); // This script is placed in a sub-directory

if (file_exists('./includes/confConstants.php')) { // Already customized
  header("Location: index.php");
  exit();
}
$star = '<small><span style="color: red;">(*)</span></small>';
$webServer = $_SERVER['HTTP_HOST'];
if ($webServer=='localhost' || $webServer=='127.0.0.1') $webServer='';

print <<<EndMark
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">

<style type="text/css">
tr {vertical-align: top;}
h1 {text-align: center;}
</style>
<script language="Javascript" type="text/javascript">
<!--
function checkEmail( fld )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  var pat1 = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
  var pat2 = /^[^@<>]*<\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+>$/;
  if((fld.value != "") && (pat1.test(fld.value)==false)
     && (pat2.test(fld.value)==false)) {
    alert("Not a valid email format");
    fld.focus();
    fld.select();
    return false ;
  }
  return true ;
}
function checkInt( fld, mn, mx )
{
  fld.value = fld.value.replace(/^\s+/g,'').replace(/\s+$/g,''); // trim
  if (fld.value == "") return true;  // allow empty field

  var pat = /^-?[0-9]+$/;
  if((pat.test(fld.value)==false) || (fld.value<mn) || (fld.value>mx)) {
    alert("Field must contain an integet between "+mn+" and "+mx);
    fld.focus();
    fld.select();
    return false ;
  }
  return true ;
}
function checkform( form )
{
  // Checking that all the mandatory fields are present
  var pat = /^\s*$/;
  var st = 0;
  if (pat.test(form.longName.value))    { st |= 1; }
  if (pat.test(form.shortName.value))   { st |= 2; }
  if (pat.test(form.confYear.value))    { st |= 4; }
  if (pat.test(form.admin.value))       { st |= 8; }
  if (form.localMySQL[1].checked &&
      pat.test(form.MySQLhost.value))   { st |= 16; }
  if (pat.test(form.chair.value))       { st |= 32; }
  if (pat.test(form.webServer.value))   { st |= 64; }

  if (st != 0) {
    alert( "You must specify all the fields that are marked with (*)" );
    if (st & 1)        { form.longName.focus();  }
    else if (st & 2)   { form.shortName.focus(); }
    else if (st & 4)   { form.confYear.focus();  }
    else if (st & 8)  { form.admin.focus();     }
    else if (st & 16) { form.MySQLhost.focus(); }
    else if (st & 32)  { form.chair.focus();     }
    else if (st & 64)  { form.webServer.focus(); }

    return false ;
  }

  st = 0;
  if (pat.test(form.rootNm.value))  { st |= 1; }
  if (pat.test(form.rootPwd.value)) { st |= 2; }
  if (pat.test(form.confDB.value))  { st |= 4; }
  if (pat.test(form.user.value))    { st |= 8; }
  if (pat.test(form.pwd.value))     { st |= 16; }

  if ((st & 3) && (st & 28)) {
    alert( "You must specify either the MySQL administarot name and password, or the name of a MySQL database as well as username and password to access that database" );
    form.rootPwd.focus();
    return false ;
  }
  return true ;
}
//-->
</script>

<title>Customizing Submission and Review Software</title>
</head>
<body>

<h1>Customizing Submission and Review Software</h1>

This form is used to customize the initial installation of the submission
and review software for your conference. Use it to specify things like
the conference name, the program chair, what formats are acceptable for
submissions, etc. All the conference parameters that you specify here
can be changed later from the administration page.<br />
<br />
In addition, you must also specify some parameters of the system on which
this software is run. Specifically, the web-server address and some MySQL
parameters to let the software work with the MySQL database. Once specified,
these parameter can only be changed by an administrator on the machine
that hosts this installation.<br/>
<br/>
This form has five sections, corresponding to <a href="#conference">the
conference</a>, <a href="#site">the site</a>, <a href="#committee">program
committee</a>, <a href="#submissions">submissions</a>, and <a
href="#review">reviews</a>.<br />

<form name="customize" onsubmit="return checkform(this);"
 action="confirm-customize.php" enctype="multipart/form-data" method="post">

<table cellpadding="6">
    <tbody>
<!-- ============== Details of the conference ================== -->
      <tr>
        <td colspan="2" style="text-align: center;"><hr /></td>
      </tr>
      <tr>
        <td colspan="2" style="text-align: right;">
        Mandatory fields are marked with $star</td>
      </tr>
      <tr><td style="text-align: right;">
          <big><b><a NAME="conference">The&nbsp;Conference:</a></b></big>
	  </td> <td></td>
      </tr>
      <tr>
        <td style="text-align: right;">
            {$star}Conference&nbsp;Name:</td>
        <td><input name="longName" size="90" type="text"><br />
            E.g., The 18th NBA Annual Symposium on Theory of Basketball
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">
            {$star}Short&nbsp;Name:</td>
	    <td><input name="shortName" size="30" type="text">
	    e.g., BASKET, &nbsp; &nbsp; &nbsp; {$star}Year:&nbsp;
	    20<input name="confYear" size="2" type="text" value="06"
	    maxlength="2" onchange="return checkInt(this, 00, 99)">
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">
            Conference&nbsp;URL:</td>
        <td><input name="confURL" size="90" type="text">
	   <br />URL of the conference home page, where the call-for-papers is found.
        </td>
      </tr>
      <tr><td colspan="2" style="text-align: right;"><hr /></td></tr>
<!-- ============== Parameters of the web-site ================== -->
      <tr><td style="text-align: right;">
             <big><b><a NAME="site">The&nbsp;Site:</a></b></big></td> 
          <td>(you may need the site administrator to help you with these
             values)</td>
      </tr>
      <tr>
        <td style="text-align: right;">{$star}<a href="../documentation/chair.html#webServer" target="documentation" title="click for more help">Web&nbsp;Server:</a></td>
        <td>
	 <input name="webServer" type="text" value="$webServer" size="60"><br/>
	  The DNS name or IP address of the web-server (e.g., "www.nba.com"
						        or "18.7.22.83")
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">{$star}<a href="../documentation/chair.html#SQLserver" target="documentation" title="click for more help">MySQL&nbsp;Server:</a></td>
        <td>
	  <input name="localMySQL" type="radio" value="yes" checked="checked">
	  The MySQL server runs on the same host as the web server<br/>
	  <input name="localMySQL" type="radio" value="no">The MySQL server
	  runs on a different host.<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
          Host name (or IP address) is:
	  <input name="MySQLhost" size="40" type="text">
        </td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;Administrator:</a></td>
        <td>Name: <input name="rootNm" size="32" value="root" type="text"> 
        &nbsp;&nbsp; Password: <input name="rootPwd" size="32" type="password">
          <br />A MySQL user that can create new databases and add new users.
             Specify these details (if you <br />know them) to automatically
             create a database for the conference submissions and reviews.
         </td>
      </tr>
      <tr>
        <td></td>
        <td>If you do not have the password of a MySQL root user, you should
          manually create the database <br />and add a user that can access
          it (or ask the site administrator to do it for you) and
          enter the <br />details below. 
        </td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;Database&nbsp;Name:</a></td>
        <td><input name="confDB" size="90" type="text"></td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#SQLuser" target="documentation" title="click for more help">MySQL&nbsp;User:</a></td>
        <td>Name: <input name="user" size="32" type="text"> 
          &nbsp; &nbsp; Password: <input name="pwd" size="32" type="password">
        </td>
      <tr>
        <td style="text-align: right;">{$star}Administrator&nbsp;Email:</td>
        <td><input name="admin" size="90" type="text"
	     onchange="return checkEmail(this)"><br />
          Who should get the angry emails when there are problems with the
          site?
        </td>
      </tr>
      <tr><td style="text-align: right;"><a href="../documentation/chair.html#emailSettings" target="documentation" title="click for more help">Email&nbsp;settings:</a></td>
	<td>Separate header lines by
          <input type="radio" name="emlCrlf" value="\\n\\r" checked="checked">
          <tt>"\\r\\n"</tt> &nbsp; or by
          <input type="radio" name="emlCrlf" value="\\n"><tt>"\\n"</tt>. 
	  (The default is <tt>"\\r\\n"</tt>, and you should only change it
	  if you know that the server has a bug in the way it handles email
	  headers.)

EndMark;
if (ini_get('safe_mode')) { print <<<EndMark
        <input type="hidden" name="emlExtraPrm" value="none"></td>
      </tr>

EndMark;
} else {  print <<<EndMark
        </td>
      </tr>
      <tr><td></td>
	<td>Extra parameters to sendmail: <input type="text" name="emlExtraPrm"
	  size="59"> Leave empty to use the default setting, i.e., calling
	  <tt>sendmail -f &lt;chair-email&gt;</tt>. Use the word <tt>none</tt>
	  to call sendmail with no extra parameters.
        </td>
      </tr>

EndMark;
}

print <<<EndMark
      <tr><td colspan="2" style="text-align: right;"><hr /></td></tr>
<!-- ================= The Program Committee =================== -->
      <tr><td style="text-align: right;">
          <big><b><a NAME="committee">Program&nbsp;Committee:</a></b></big>
	  </td> <td></td>
      </tr>
      <tr>
        <td style="text-align: right;">{$star}<a href="../documentation/chair.html#PCemail" target="documentation" title="click for more help">Chair&nbsp;Email:</a></td>
        <td><input name="chair" size="90" type="text"
	     onchange="return checkEmail(this)"><br />
          Only one address (e.g., chair@basket06.org or
          Magic.Johnson@retirement.net)
        </td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#PCemail" target="documentation" title="click for more help">Program&nbsp;Committee:</a></td>
        <td><textarea name="committee" rows="15" cols="70">
Shaquille O'Neal &lt;shaq@MiamiHeat.nba.com&gt;;
Larry J. Bird &lt;the-bird@old-timers.org&gt;;
Jordan, Michael &lt;Air-Jordan@nike.com&gt;</textarea><br />
            A <i><b>semi-colon-separated</b></i> list of email addresses
	    (including the chair's personal email address).<br /> Each
            address should be in the format "Name &lt;email-address&gt;". 
            (The names that you enter here <br />will be displayed on the
            reports and discussion boards.)
        </td>
      </tr>
      <tr><td colspan="2" style="text-align: right;"><hr /></td></tr>
<!-- ================= Submissions =================== -->
      <tr><td style="text-align: right;">
          <big><b><a NAME="submissions">Submissions:</a></b></big>
	  </td> <td></td>
      </tr>
      <tr>
        <td style="text-align: right;">
            Submission&nbsp;Deadline:</td>
        <td><input name="subDeadline" size="90" type="text"></td>
      </tr>  
      <tr>
        <td style="text-align: right;">
            Camera&nbsp;ready&nbsp;Deadline:</td>
        <td><input name="cameraDeadline" size="90" type="text"><br />
	    Use any reasonable format (e.g., Jan 15 2005 11:20pm GMT
	    or 2005-1-15 18:20 EST, etc.) <br />The dates will be displayed 
	    for the users, but <b>the software does not enforce these 
            deadlines <br />automatically.
            </b>
        </td>
      </tr>  
      <tr>
        <td style="text-align: right;">Categories:</td>
        <td><textarea name="categories" rows="3" cols="70"></textarea><br />
	    A <b><i>semi-colon-separated</i></b> list of categories for the
	    submissions (leave empty to forgo categories).
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Require&nbsp;Affiliations:</td>
        <td><input name="affiliations" type="checkbox">
	    Check to require submitters to specify their affiliations
        </td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#formats" target="documentation" title="click for more help">Supported Formats:</a></td>
        <td><input checked="checked" name="formatPDF" type="checkbox">PDF 
          &nbsp; <input name="formatPS" type="checkbox">PostScript 
          &nbsp; <input name="formatTEX" type="checkbox">LaTeX 
          &nbsp; <input name="formatHTML" type="checkbox">HTML 
          &nbsp; <input name="formatZIP" type="checkbox">Zip Archive
        </td>
      </tr>
      <tr>
        <td></td>
        <td><input name="formatMSword" type="checkbox">MS-Word 
         &nbsp; <input name="formatPPT" type="checkbox">PowerPoint
         &nbsp; <input name="formatODT" type="checkbox">OpenOffice Document
         &nbsp; <input name="formatODP" type="checkbox">OpenOffice Presentation
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Another&nbsp;Format&nbsp;#1:</td>
        <td>Format Name:<input name="format1desc" size="20" type="text">
          &nbsp; Extension:<input name="format1ext" size="3" type="text">
          &nbsp; MIME-type:<input name="format1mime" size="15" type="text">
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Another&nbsp;Format&nbsp;#2:</td>
        <td>Format Name:<input name="format2desc" size="20" type="text">
          &nbsp; Extension:<input name="format2ext" size="3" type="text">
          &nbsp; MIME-type:<input name="format2mime" size="15" type="text">
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Another&nbsp;Format&nbsp;#3:</td>
        <td>Format Name:<input name="format3desc" size="20" type="text">
          &nbsp; Extension:<input name="format3ext" size="3" type="text">
          &nbsp; MIME-type:<input name="format3mime" size="15" type="text">
        </td>
      </tr>
      <tr><td colspan="2" style="text-align: right;"><hr /></td></tr>
<!-- ================= Reviews =================== -->
      <tr><td style="text-align: right;">
          <big><b><a NAME="review">Reviews:</a></b></big>
	  </td>
          <td>(the default options below should work just fine for most cases)
          </td>
      </tr>
      <tr>
        <td style="text-align: right;">Anonymous&nbsp;Submissions:</td>
        <td><input name="anonymous" type="checkbox">
	    Check to hide author names from the reviewers.
        </td>
      </tr>
      <tr>
        <td style="text-align: right;"><a href="../documentation/chair.html#revPrefs" target="documentation" title="click for more help">Reviewer&nbsp;Preferences:</a></td>
        <td><input name="revPrefs" type="checkbox" checked="checked">
	    Check to let PC members specify their reviewing preferences.
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Overall&nbsp;Grades:</td>
        <td>Min: <input disabled size="1" type="text" value="1"> , &nbsp;
           Max: <input name="maxGrade" size="2" type="text" value="6"
	   maxlength="1" onchange="return checkInt(this, 2, 9)"> &nbsp;
           (later you can assign semantics to these grades).
        </td>
      </tr>
      <tr>
        <td style="text-align: right;">Other&nbsp;Evaluation&nbsp;Criteria:</td>
        <td>Other than overall grades, you can have grades for specific
	   criteria such as presentation, IP status, etc. <br /> The min grade
	   value is always 1 and the max value must be in [2,9].<br />
	<textarea name="criteria" cols="70">Technical(3); Editorial(3); Suitability(3)</textarea><br />
           A <i><b>semi-colon-separated</b></i> list of criteria. Each
           criterion should be in the format "Name (max-val)". <br />For
	   example "Clear Presentation (3); Bribe Amount (9); ..."
        </td>
      </tr>
      <tr><td colspan="2" style="text-align: right;"><hr /></td></tr>
<!-- ================= The Submit Button =================== -->
      <tr>
        <td colspan="2" style="text-align: center;"> 
            <input value="         Submit         " type="submit">
        </td>
      </tr>
    </tbody>
  </table>
</form>
EndMark;
?>
</body>
</html>
