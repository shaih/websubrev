<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 /********* standard header for the top directory ************/
if (!file_exists('../init/confParams.php')) { // Not yet customized
  header("Location: ../chair/customize.php");
  exit();
}
require_once('../includes/getParams.php'); 
$confShortName = CONF_SHORT.' '.CONF_YEAR;
$php_errormsg = ''; // just so we don't get notices when it is not defined.

// Only the chair can use these scripts outside the submission periods
$chair = false;
$submission = false;

if ((isset($allow_rebuttal) && active_rebuttal())
     || (isset($finalFeedback) && active_feedback())) {
  if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
    $submission = auth_author($_SERVER['PHP_AUTH_USER'],
                              $_SERVER['PHP_AUTH_PW']);
  if ($submission === false) {
    header("WWW-Authenticate: Basic realm=\"$confShortName\"");
    header("HTTP/1.0 401 Unauthorized");
    exit("<h1>Authentication Error</h1><h2>Invalid Submission-ID or Password given</h2>.");
  }
} else { // For anything other than rebuttal/feedback, check that it is allowed
  if (PERIOD>PERIOD_SUBMIT && PERIOD!=PERIOD_CAMERA && !isset($bypassAuth)) { // only the chair
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
      $chair = auth_PC_member($_SERVER['PHP_AUTH_USER'],
			      $_SERVER['PHP_AUTH_PW'], chair_ids());
    if ($chair === false) {
      header("WWW-Authenticate: Basic realm=\"$confShortName\"");
      header("HTTP/1.0 401 Unauthorized");
      exit("<h1>Submission Deadline Expired</h1>Please contact the chair.");
    }
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

  if (!defined('REVIEW_PERIOD') || is_chair($chair[0])) {
    $html .= make_link('revise.php', 'Revision Form', ($current == 4))
      . make_link('withdraw.php', 'Withdrawal Form', ($current == 5));
  }
  if (defined('CAMERA_PERIOD')) {
    $html .= make_link('cameraready.php', 'Camera-Ready Form', ($current==6));
  }
  $html .= make_link('../documentation/submitter.html', 'Documentation')."</div>\n";

  if ($prt) {print $html; return true;}
  else return $html;
}
?>