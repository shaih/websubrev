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
			    $_GET['password'], CHAIR_ID, $notCustomized);
  }

  // next try uname/pwd from HTTP authentication
  if ($chair===false && isset($_SERVER['PHP_AUTH_USER'])
                     && isset($_SERVER['PHP_AUTH_PW'])) {
    $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			    $_SERVER['PHP_AUTH_PW'], CHAIR_ID, $notCustomized);
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

function status_summary($statuses)
{
  $html = '<table style="text-align: center;" border=1><tbody><tr>
  <td class="setNO"><b>None<br />'
    . (isset($statuses['None']) ? $statuses['None'] : 0) .'</b></td>
  <td class="setRE"><b>Reject<br />'
    . (isset($statuses['Reject']) ? $statuses['Reject'] : 0) . '</b></td>
  <td class="setMR"><b>Maybe Reject<br />'
    . (isset($statuses['Perhaps Reject']) ? $statuses['Perhaps Reject'] : 0)
    . '</b></td>
  <td class="setDI"><b>Discuss<br />'
    . (isset($statuses['Needs Discussion'])? $statuses['Needs Discussion']: 0)
    . '</b></td>
  <td class="setMA"><b>Maybe Accept<br />'
    . (isset($statuses['Maybe Accept'])? $statuses['Maybe Accept']: 0)
    . '</b></td>
  <td class="setAC"><b>Accept<br />'
    . (isset($statuses['Accept'])? $statuses['Accept'] : 0) .'</b></td>
</tr></tbody></table>';

  return $html;
}

function show_chr_links($current = 0) 
{
  $cnnct = db_connect();
  $qry = "SELECT MAX(version) FROM parameters";
  $res = db_query($qry, $cnnct);
  $row = mysql_fetch_row($res);
  $maxVersion = $row[0];

  if (PARAMS_VERSION>1 || PARAMS_VERSION<$maxVersion)
       $undoLink = make_link('undoLast.php', 'Undo/Redo', ($current==4));
  else $undoLink = '';

  $html = "<div style=\"text-align: center;\">\n";
  $html .= make_link('index.php', 'Administer', ($current==1))
    . make_link('../review/', 'Review', ($current==3))
    . make_link('view-log.php', 'Log file', ($current==2))
    . make_link('../review/password.php', 'Change password')
    . make_link('../documentation/chair.html', 'Documentation')
    . $undoLink
    . "</div>";

  return $html;
}
?>
