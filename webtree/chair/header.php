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
  $stts = array('NO'=>'None', 'RE'=>'Reject', 'MR'=>'Perhaps Reject',
		'DI'=>'Needs Discussion','MA'=>'Maybe Accept','AC'=>'Accept');
  foreach ($stts as $st) if (empty($statuses[$st])) $statuses[$st] = 0;

  $legend = '';
  $txtNO = 'None<br/><span class="statusSummary" ID="sumNO">'.$statuses['None'].'</span>';
  $txtRE = 'Reject<br/><span class="statusSummary" ID="sumRE">'.$statuses['Reject'].'</span>';
  $txtMR = 'Maybe Reject<br/><span class="statusSummary" ID="sumMR">'.$statuses['Perhaps Reject'].'</span>';
  $txtDI = 'Discuss<br/><span class="statusSummary" ID="sumDI">'.$statuses['Needs Discussion'].'</span>';
  $txtMA = 'Maybe Accept<br/><span class="statusSummary" ID="sumMA">'.$statuses['Maybe Accept'].'</span>';
  $txtAC = 'Accept<br/><span class="statusSummary" ID="sumAC">'.$statuses['Accept'].'</span>';

  if (!empty($scstatuses)) {
    foreach ($stts as $st) if (empty($scstatuses[$st])) $scstatuses[$st] = 0;

    $legend = '<td><br/>visible<br/>scratch</td>';
    $txtNO .= '<br/><span class="statusSummary" ID="scSumNO">'.$scstatuses['None'].'</span>';
    $txtRE .= '<br/><span class="statusSummary" ID="scSumRE">'.$scstatuses['Reject'].'</span>';
    $txtMR .= '<br/><span class="statusSummary" ID="scSumMR">'.$scstatuses['Perhaps Reject'].'</span>';
    $txtDI .= '<br/><span class="statusSummary" ID="scSumDI">'.$scstatuses['Needs Discussion'].'</span>';
    $txtMA .= '<br/><span class="statusSummary" ID="scSumMA">'.$scstatuses['Maybe Accept'].'</span>';
    $txtAC .= '<br/><span class="statusSummary" ID="scSumAC">'.$scstatuses['Accept'].'</span>';
  }
  $html = <<<EndMark
<table style="text-align: center;" border=1><tbody><tr>
  $legend
  <td class="setNO"><b>$txtNO</b></td>
  <td class="setRE"><b>$txtRE</b></td>
  <td class="setMR"><b>$txtMR</b></td>
  <td class="setDI"><b>$txtDI</b></td>
  <td class="setMA"><b>$txtMA</b></td>
  <td class="setAC"><b>$txtAC</b></td>
</tr></tbody></table>
EndMark;
  return $html;
}

function show_chr_links($current = 0, $anotherLink=NULL) 
{
  global $SQLprefix;
  $undoLink = make_link('undoLast.php', 'Undo/Redo', ($current==4));
  if (PARAMS_VERSION==1) {
    $qry = "SELECT count(*) FROM {$SQLprefix}paramsBckp WHERE version=?".(PARAMS_VERSION+1);
    $res = pdo_query("SELECT version FROM {$SQLprefix}paramsBckp WHERE version=?",
		     array(PARAMS_VERSION+1));
    if ($res->fetchColumn()<=0) $undoLink = ''; // no redo information
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

function backup_conf_params($version)
{
  global $SQLprefix;
  // Delete paramater-sets with larger version number (can happen after undo's)
  pdo_query("DELETE FROM {$SQLprefix}paramsBckp WHERE version>=?", array($version));

  // Insert the current row into the backup table
  pdo_query("INSERT IGNORE INTO {$SQLprefix}paramsBckp SELECT * FROM {$SQLprefix}parameters WHERE version=?", array($version));
}
?>
