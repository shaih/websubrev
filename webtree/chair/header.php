<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 /********* standard header for the /chair directory ************/

if (!file_exists('../init/confParams.php')) { // Not intialized yet
  header("Location: initialize.php");
  exit();
}

require_once('../includes/getParams.php');

if (!isset($needsAuthentication)) $needsAuthentication = true;
if (!isset($notCustomized))       $notCustomized = false;

/* Authenticate the chair unless $needsAuthentication === false (in
 * particular this means authenticate when $needsAuthentication is NULL)
 */
if ($needsAuthentication !== false) {
  $chair = false;

  // first try uname/pwd from the URL (if any)
  if (isset($_GET['username']) && isset($_GET['password'])) {
    // returns either an array (id, name, email, ...) or false 
    $chair = auth_PC_member($_GET['username'], 
			    $_GET['password'], chair_ids(), $notCustomized);
  }

  // next try uname/pwd from HTTP authentication
  if ($chair===false && isset($_SERVER['PHP_AUTH_USER'])
      && isset($_SERVER['PHP_AUTH_PW'])) {
    $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			    $_SERVER['PHP_AUTH_PW'], chair_ids(), $notCustomized);
  }

  // If nothing works, prompt client for credentials
  if ($chair===false) {
    $confShortName = CONF_SHORT.' '.CONF_YEAR;
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("The chair's username/password is needed to access this page");
  }
}

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) {
  $_GET  = array_map('stripslashes_deep', $_GET);
  $_POST  = array_map('stripslashes_deep', $_POST);
}

$php_errormsg = ''; // just so we don't get notices when it is not defined.

function status_summary($statuses, $scstatuses=NULL)
{
  if (isset($scstatuses)) {
    $legend = '<td><br/>visible<br/>scratch</td>';
    $scNO = '<br/><span class="summary" data-status="None">'.(isset($scstatuses['None']) ? $scstatuses['None'] : 0).'</span>';
    $scRE = '<br/><span class="summary" data-status="Reject">'.(isset($scstatuses['Reject']) ? $scstatuses['Reject'] : 0).'</span>';
    $scMR = '<br/><span class="summary" data-status="Perhaps Reject">'.(isset($scstatuses['Perhaps Reject']) ? $scstatuses['Perhaps Reject'] : 0).'</span>';
    $scDI = '<br/><span class="summary" data-status="Needs Discussion">'.(isset($scstatuses['Needs Discussion']) ? $scstatuses['Needs Discussion'] : 0).'</span>';
    $scMA = '<br/><span class="summary" data-status="Maybe Accept">'.(isset($scstatuses['Maybe Accept']) ? $scstatuses['Maybe Accept'] : 0).'</span>';
    $scAC = '<br/><span class="summary" data-status="Accept">'.(isset($scstatuses['Accept']) ? $scstatuses['Accept'] : 0).'</span>';
  }
  else {
    $legend = '';
    $scNO = '';
    $scRE = '';
    $scMR = '';
    $scDI = '';
    $scMA = '';
    $scAC = '';
  }
  $html = '<table style="text-align: center;" border=1><tbody><tr>'.$legend.'
  <td class="setNO"><b>None<br />'
    . (isset($statuses['None']) ? $statuses['None'] : 0).$scNO.'</b></td>
  <td class="setRE"><b>Reject<br />'
    . (isset($statuses['Reject']) ? $statuses['Reject'] : 0).$scRE.'</b></td>
  <td class="setMR"><b>Maybe Reject<br />'
    . (isset($statuses['Perhaps Reject']) ? $statuses['Perhaps Reject'] : 0)
    .$scMR. '</b></td>
  <td class="setDI"><b>Discuss<br />'
    . (isset($statuses['Needs Discussion'])? $statuses['Needs Discussion']: 0)
    .$scDI. '</b></td>
  <td class="setMA"><b>Maybe Accept<br />'
    . (isset($statuses['Maybe Accept'])? $statuses['Maybe Accept']: 0)
    .$scMA. '</b></td>
  <td class="setAC"><b>Accept<br />'
    . (isset($statuses['Accept'])? $statuses['Accept'] : 0).$scAC.'</b></td>
</tr>';
  $html .= '</tbody></table>';
  return $html;
}

function show_chr_links($current = 0, $anotherLink=NULL) 
{
  $undoLink = make_link('undoLast.php', 'Undo/Redo', ($current==4));
  if (PARAMS_VERSION==1) {
    $cnnct = db_connect();
    $qry = "SELECT version FROM paramsBckp WHERE version=".(PARAMS_VERSION+1);
    $res = db_query($qry, $cnnct);
    if (mysql_num_rows($res)==0) $undoLink = '';
  }
  if (isset($anotherLink)) {
    $anotherLink = make_link($anotherLink[0], $anotherLink[1]);
  }
  else $anotherLink = '';

  $html = "<div style=\"text-align: center;\">\n";
  $html .= $anotherLink
    . make_link('index.php', 'Administer', ($current==1))
    . make_link('../review/', 'Review', ($current==3))
    . make_link('viewLog.php', 'Log file', ($current==2))
    . make_link('../review/password.php', 'Change password')
    . make_link('../documentation/chair.html', 'Documentation')
    . $undoLink
    . "</div>";

  return $html;
}

function backup_conf_params($cnnct,$version)
{
  // Delete paramater-sets with larger version number (can happen after undo's)
  $qry = "DELETE FROM paramsBckp WHERE version>=$version";
  mysql_query($qry, $cnnct); //  no need to abort of failure

  $qry = "INSERT IGNORE INTO paramsBckp SELECT * FROM parameters WHERE version=$version";
  mysql_query($qry, $cnnct);
}
?>
