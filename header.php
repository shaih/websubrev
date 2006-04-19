<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 /********* standard header for the top directory ************/

if (!file_exists('./includes/confConstants.php')) { // Not yet customized
  header("Location: chair/customize.php");
  exit();
}
require './includes/confConstants.php'; 
require './includes/confUtils.php';     
$confShortName = CONF_SHORT.' '.CONF_YEAR;

if (defined('SHUTDOWN')) exit("<h1>Site is Closed</h1>");

if (defined('REVIEW_PERIOD') && REVIEW_PERIOD===true) {
  // only the chair is allowed access to submissions pages after the deadline
  $chair = false;
  if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
    $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			    $_SERVER['PHP_AUTH_PW'], CHAIR_ID);
  if ($chair === false) {
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Submission Deadline Expired</h1>");
  }
}

// If 'magic quotes' are on, get rid of them
if (get_magic_quotes_gpc()) {
  $_GET  = array_map('stripslashes_deep', $_GET);
  $_POST  = array_map('stripslashes_deep', $_POST);
}

function show_sub_links($current=0, $prt=false) 
{
  global $chair;
  $html = "<div style=\"text-align: center;\">\n"
    . make_link(CONF_HOME, CONF_SHORT.' '.CONF_YEAR.' Home')
    . make_link('index.php', 'Instructions', ($current == 2));

  if (!defined('REVIEW_PERIOD'))
    $html .= make_link('submit.php', 'New Submission Form', ($current == 3));

  if (!defined('REVIEW_PERIOD') || $chair[0]==CHAIR_ID) {
    $html .= make_link('revise.php', 'Revision Form', ($current == 4))
           . make_link('withdraw.php', 'Withdrawal Form', ($current == 5));
  }
  if (defined('CAMERA_PERIOD')) {
    $html .= make_link('cameraready.php', 'Camera-Ready Form', ($current==6));
  }
  $html .= make_link('documentation/submitter.html', 'Documentation')."</div>\n";

  if ($prt) {print $html; return true;}
  else return $html;
}
?>
