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
define('FLAG_REBUTTAL_ON', 256);

//Flags for committee members
define('FLAGS_EML_MY_REPORT', 0x01000000);
define('FLAG_EML_WATCH_EVENT', 0x02000000);
define('FLAG_ORDER_REVIEW_HOME', 0x04000000);
define('FLAG_IS_CHAIR', 0x08000000);

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

//Flag for submissions
define('SUBMISSION_NEEDS_STAMP', 1);
define('FLAG_IS_GROUP', 2);
define('FLAG_IS_CHECKED', 4);
define('FLAG_CONSENT_SLIDES', 8);
define('FLAG_CONSENT_VIDEO', 16);
define('FLAG_CONSENT_OTHER', 32);
define('FLAG_PCM_SUBMISSION', 64);

/* //Colors for topics
$COLORS = array(0=>'00CCCC', 1=>'FF66FF', 
 2=>'FFCC00',  3=>'99FF00',  4=>'FF0000',  5=>'FFFF33',  6=>'CC6600', 
 7=>'FF3399',  8=>'990099',  9=>'3399FF', 10=>'33FFFF', 11=>'009933',
12=>'990033', 13=>'FF9933', 14=>'CCCCCC', 15=>'E9C2A6', 16=>'00FF99',
17=>'FAFAD2', 18=>'CCFFCC', 19=>'F5F5DC', 20=>'E7C6A5', 21=>'FC1501', 
22=>'EED5D2', 23=>'FF8247', 24=>'FF9955', 25=>'33FF33', 26=>'003399');

//Kept as reference for colors
define('0_COLOR', '00CCCC'); //BLUE2_TOPIC
define('1_COLOR', 'FF66FF'); //PINK2_TOPIC
define('2_COLOR', 'FFCC00'); //ORANGE_TOPIC
define('3_COLOR', '99FF00'); //LIME_TOPIC
define('4_COLOR', 'FF0000'); //RED_TOPIC
define('5_COLOR', 'FFFF33'); //YELLOW_TOPIC
define('6_COLOR', 'CC6600'); //BROWN_TOPIC
define('7_COLOR', 'FF3399'); //PINK_TOPIC
define('8_COLOR', '990099'); //PURPLE_TOPIC
define('9_COLOR', '3399FF'); //BLUE_TOPIC
define('10_COLOR', '33FFFF'); //SKY_TOPIC
define('11_COLOR', '009933'); //FOREST_TOPIC
define('12_COLOR', '990033'); //MAROON_TOPIC
define('13_COLOR', 'FF9933'); //ORANGE2_TOPIC
define('14_COLOR', 'CCCCCC'); //GREY_TOPIC
define('15_COLOR', 'E9C2A6'); //LIGHT WOOD
define('16_COLOR', '00FF99'); //BLUE-GREEN TOPIC
define('17_COLOR', 'FAFAD2'); //GOLDENROD? TOPIC
define('18_COLOR', 'CCFFCC'); //PALE WHITE TOPIC
define('19_COLOR', 'F5F5DC'); //BEIGE LIGHT TOPIC
define('20_COLOR', 'E7C6A5'); //ESPRESSO TOPIC
define('21_COLOR', 'FC1501'); //GUMMIT RED TOPIC
define('22_COLOR', 'EED5D2'); //MISTY ROSE TOPIC
define('23_COLOR', 'FF8247'); //SIENNA TOPIC
define('24_COLOR', 'FF9955'); //PEACH TOPIC
*/
?>
