<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

define('FLAG_PCPREFS', 1);
define('FLAG_ANON_SUBS', 2);
define('FLAG_AFFILIATIONS', 4);
define('FLAG_EML_HDR_CRLF', 8);
define('FLAG_EML_HDR_X_MAILER', 16);
define('FLAG_EML_EXTRA_PRM', 32);
define('FLAG_SSL', 64);
define('FLAG_REV_ATTACH', 128);

define('FLAGS_EML_MY_REPORT', 0x01000000);
define('FLAG_EML_WATCH_EVENT', 0x02000000);
define('FLAG_ORDER_REVIEW_HOME', 0x04000000);

define('PERIOD_SETUP', 0);
define('PERIOD_PREREG', 1);
define('PERIOD_SUBMIT', 2);
define('PERIOD_REVIEW', 3);
define('PERIOD_CAMERA', 4);
define('PERIOD_FINAL', 5);

define('FLAG_VOTE_ON_SUBS', 1);
define('FLAG_VOTE_ON_ALL', 2);
define('FLAG_VOTE_ON_RE', 4);
define('FLAG_VOTE_ON_MR', 8);
define('FLAG_VOTE_ON_NO', 16);
define('FLAG_VOTE_ON_DI', 32);
define('FLAG_VOTE_ON_MA', 64);
define('FLAG_VOTE_ON_AC', 128);

define('CHAIR_ID', 1);

define('VOTE_ON_SUBS', 1);
define('VOTE_ON_ALL',  2);
define('VOTE_ON_RE',   4);
define('VOTE_ON_MR',   8);
define('VOTE_ON_NO',  16);
define('VOTE_ON_DI',  32);
define('VOTE_ON_MA',  64);
define('VOTE_ON_AC', 128);

define('REPORT_NOT_DRAFT', 1);

define('SUBMISSION_NEEDS_STAMP', 1);
?>