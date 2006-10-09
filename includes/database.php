<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
 // SQL statements to create all the database tables

function create_tabels($cnnct, $nCrits=0)
{
  // The submission table. The numReviewers lets the chair specify how many
  // reviewers should be assigned to review the submission initally (to be
  // used by the "matching" algorithm). The revisionOf and oldVersionOf are
  // meant to deal with revisions (e.g., if this is used for jounral reviews)
  //
  // The status titles were chosen so that alphabetical order coninsides
  // with logical order, to overcome this "feature" of MySQL that only lets
  // you sort by alphabetical order.
  $createSubTbl = "CREATE TABLE IF NOT EXISTS submissions (
    subId smallint(5) NOT NULL auto_increment,
    title varchar(255) NOT NULL,
    authors text NOT NULL,
    affiliations text,
    contact varchar(255) NOT NULL,
    abstract text,
    category varchar(255),
    keyWords varchar(255),
    comments2chair text,
    format varchar(32),
    subPwd varchar(16) BINARY,
    status enum('Accept',
                'Maybe Accept',
                'Needs Discussion',
                'None',
                'Perhaps Reject',
                'Reject',
                'Withdrawn') NOT NULL DEFAULT 'None',
    whenSubmitted datetime NOT NULL,
    lastModified timestamp,
    numReviewers tinyint(1),
    avg float,
    wAvg float,
    minGrade tinyint(1),
    maxGrade tinyint(1),
    revisionOf smallint(5),
    oldVersionOf smallint(5),
    PRIMARY KEY (subId),
    KEY pwd (subPwd(2))
  )";
  db_query($createSubTbl, $cnnct, "Cannot CREATE submissions table: ");

  $createAccPprTbl = "CREATE TABLE IF NOT EXISTS acceptedPapers (
    subId smallint(5) NOT NULL,
    nPages smallint(3) DEFAULT 0 NOT NULL,
    pOrder smallint(3) DEFAULT 0 NOT NULL,
    PRIMARY KEY (subId),
    INDEX (pOrder)
  )";
  db_query($createAccPprTbl, $cnnct, "Cannot CREATE acceptedPapers table: ");

  // The committee table
  $createCmteTbl ="CREATE TABLE IF NOT EXISTS committee (
    revId smallint(3) NOT NULL auto_increment,
    revPwd varchar(255) BINARY NOT NULL,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    canDiscuss tinyint(1) NOT NULL DEFAULT 0, 
    threaded tinyint(1) NOT NULL DEFAULT 1, 
    flags int NOT NULL DEFAULT 0,
    PRIMARY KEY (revId),
    KEY pw (revPwd(2))
  )";
  db_query($createCmteTbl, $cnnct, "Cannot CREATE committee table: ");

  // The reports table(s)
  $reports = "subId smallint(5) NOT NULL,
    revId smallint(3) NOT NULL,
    subReviewer varchar(255),
    confidence tinyint(1),
    grade tinyint(2),\n";
  for ($i=0; $i<$nCrits; $i++) { // additional evaluation criteria
    $reports .= "    grade_{$i} tinyint(2),\n";
  }
  $reports .= "    comments2authors text,
    comments2committee text,
    comments2chair text,
    whenEntered datetime NOT NULL,";

  // The main table for reports
  $createRprtTbl = "CREATE TABLE IF NOT EXISTS reports (
    $reports
    lastModified datetime NOT NULL,
    PRIMARY KEY (subId, revId)
  )";
  db_query($createRprtTbl, $cnnct, "Cannot CREATE reports table: ");

  /* A table to store backup of old reports */
  $createRprtBckp = "CREATE TABLE IF NOT EXISTS reportBckp (
    $reports
    version smallint(3) NOT NULL DEFAULT 0,
    PRIMARY KEY (subId, revId, version)
  )";
  db_query($createRprtBckp, $cnnct, "Cannot CREATE report backup table: ");

  // The assignments table, relating PC members to submissions.
  //
  // The pref column is the reviewer's preferences, ranging from 0 
  // (conflict) and 1 (don't want to review) through 5 (want to review). 
  //
  // the compatible field is the extent to which the chair thinks that
  // this reviewer is a good reviewer for that submission, and it can be
  // either -1 (not compatible) 0 (default) or 1 (should review this).
  //
  // The sktchAssgn field can be -1 (conflict), 1 (assigned) or 0 (neither).
  // This is used in the process of assigning submissions to reviewers.
  //
  // The assign field can be either -1 (conflict), 1 (assigned) or 0 (neither).
  //
  // The watch field (0/1) is used to let reviewers specify a list of
  // papers that they want to watch during the discussion phase.

  $createAsgnTbl = "CREATE TABLE IF NOT EXISTS assignments (
    subId smallint(5) NOT NULL,
    revId smallint(3) NOT NULL, 
    pref tinyint(1) NOT NULL DEFAULT 3, 
    compatible tinyint(1) NOT NULL DEFAULT 0,
    sktchAssgn tinyint(1) NOT NULL DEFAULT 0,
    assign tinyint(1) NOT NULL DEFAULT 0,
    watch tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (revId, subId)
  )";
  db_query($createAsgnTbl, $cnnct, "Cannot CREATE assignments table: ");

  // The post table: inividual posts in a discussion
  $createPostTbl = "CREATE TABLE IF NOT EXISTS posts (
    postId smallint(5) NOT NULL auto_increment,
    parentId smallint(5),
    subId smallint(5) NOT NULL,
    revId smallint(3) NOT NULL,
    subject varchar(255),
    comments text,
    whenEntered datetime NOT NULL,
    PRIMARY KEY (postId),
    INDEX (subId)
  )";
  db_query($createPostTbl, $cnnct, "Cannot CREATE posts table: ");

  // The lastPost table is used to keep track of the last post for each
  // submission that a reviewer saw, so we can mark posts as "unread"
  $createDiscussTbl = "CREATE TABLE IF NOT EXISTS lastPost (
    subId smallint(5) NOT NULL,
    revId smallint(3) NOT NULL, 
    lastSaw smallint(5) NOT NULL,
    lastVisited timestamp, 
    PRIMARY KEY (revId, subId)
  )";
  db_query($createDiscussTbl, $cnnct, "Cannot CREATE lastPost table: ");

  // The vote tables lets the chair set up votes on submissions

  /* voteFlags is a bit-flag field, currently supporting the following fields
   *     1 - vote on submissions (otherwise vote on "other things")
   *     2 - vote on all submission
   *     4 - vote on submission with status RE
   *     8 - vote on submission with status MR
   *    16 - vote on submission with status NO
   *    32 - vote on submission with status DI
   *    64 - vote on submission with status MA
   *   128 - vote on submission with status AC
   *
   * If the vote-on-submissions flag is set, then the submissions included
   * in the vote are determined by the following procedure: 
   *  - If the vote-on-all flag is set then all the submissions are included
   *  - Otherwise, the vote-on-XX flags are consulted to add all the
   *    submissions of a certain status. In addition, if the voteOnThese
   *    field includes a comma-separated list of submission-IDs then these
   *    submissions will be also added to the vote. 
   * If the vote-on-submissions flag is not set Otherwise then the voteOnThese
   * field MUST include a description of things to vote on, in the form of a
   * semi-colon-separated list.
   */
  $createVotePrms = "CREATE TABLE IF NOT EXISTS votePrms (
    voteId smallint(3) NOT NULL auto_increment,
    voteActive tinyint(1) NOT NULL default 0,
    voteType enum ('Choose','Grade') NOT NULL default 'Choose',
    voteFlags integer NOT NULL default 0,
    voteBudget smallint NOT NULL default 0,
    voteMaxGrade tinyint NOT NULL default 1,
    voteOnThese text,
    voteTitle text,
    instructions text,
    deadline text,
    PRIMARY KEY (voteId)
  )";
  db_query($createVotePrms, $cnnct, "Cannot CREATE vote-params table: ");

  $createVoteTbl = "CREATE TABLE IF NOT EXISTS votes (
    voteId smallint(3) NOT NULL default 1, 
    revId smallint(3) NOT NULL, 
    subId smallint(5) NOT NULL,
    vote tinyint,
    PRIMARY KEY (voteId, revId, subId)
  )";
  db_query($createVoteTbl, $cnnct, "Cannot CREATE votes table: ");
}
?>
