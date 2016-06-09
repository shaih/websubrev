<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */
$needsAuthentication = true; 
require 'header.php';

$cName = CONF_SHORT.' '.CONF_YEAR;
$cNameLowCase = strtolower(CONF_SHORT.CONF_YEAR);
$cFullName = CONF_NAME;
if (PERIOD<PERIOD_REVIEW) die("<h1>Too early to produce TOC</h1>");

if (isset($_POST['makeTOC'])) { // read user input
  $papers = array();
  foreach ($_POST['pOrder'] as $subId => $ord) {
    $subId = (int) $subId;
    if (isset($papers[$subId]) && !is_array($papers[$subId])) // check that this subId has a record
      $papers[$subId] = array();
    $papers[$subId]['pOrder'] = (int) trim($ord);
  }
  foreach ($_POST['nPages'] as $subId => $n) {
    $subId = (int) $subId;
    if (!is_array($papers[$subId])) // check that this subId has a record
      $papers[$subId] = array();
    $papers[$subId]['nPages'] = (int) trim($n);
  }
  foreach ($_POST['title'] as $subId => $ttl) {
    $subId = (int) $subId;
    if (!is_array($papers[$subId])) // check that this subId has a record
      $papers[$subId] = array();
    $papers[$subId]['title'] = $ttl;
  }  
  foreach ($_POST['authors'] as $subId => $athr) {
    $subId = (int) $subId;
    if (!is_array($papers[$subId])) // check that this subId has a record
      $papers[$subId] = array();
    $papers[$subId]['authors'] = $athr;
  }

  // Compute the partition to volumes
  uasort($papers, "cmpOrder"); // sort by order
  $vol = 0;
  foreach ($papers  as $subId => $ppr) {
    if (empty($_POST['pOrder'][$subId])) {
      $papers[$subId]['volume'] = -1;
        continue;
    }
    if (!empty($_POST['volFst'][$subId])) // beginning of new volume
        $vol++;
    if ($vol<1) $vol=1; // Always start from 1 (or more)
    $papers[$subId]['volume'] = $vol;
  }
  // exit('<pre>'.print_r($papers, true).'</pre>');
  
  // Update database with the given user input
  foreach ($papers as $subId => $ppr) {
    
    if (isset($papers[$subId]['nPages']) || isset($papers[$subId]['pOrder'])) {
      $updates = $sep = '';
      if (isset($papers[$subId]['nPages'])) {
        $updates = "nPages=".$papers[$subId]['nPages']; $sep = ',';
      }
      if (isset($papers[$subId]['pOrder'])) {
        $updates .= "{$sep}pOrder=".$papers[$subId]['pOrder']; $sep = ',';
      }
      pdo_query("UPDATE {$SQLprefix}acceptedPapers SET $updates WHERE subId=$subId");
    }

    if (isset($papers[$subId]['title']) || isset($papers[$subId]['authors'])) {
      $updates = $sep = '';
      $prms = array();
      if (isset($papers[$subId]['title'])) {
	$updates = "title=?";
	$prms[] = $papers[$subId]['title'];
	$sep = ',';
      }
      if (isset($papers[$subId]['authors'])) {
	$updates .= "{$sep}authors=?";
	$prms[] = $papers[$subId]['authors'];
	$sep = ',';
      }
      $qry = "UPDATE {$SQLprefix}submissions SET $updates WHERE subId=?";
      $prms[] = $subId;
      pdo_query($qry, $prms);
    }
  }

  // Get the sub-reviewer list and list of PC members from the database. The
  // condition subReviewer>'' is met when subReviewer is neither NULL nor empty.

  $qry = "SELECT subReviewer FROM {$SQLprefix}reports WHERE subReviewer>''";
  $res = pdo_query($qry);
  $subRevs = array();
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $names = trim($row[0]);
    if (empty($names)) continue;
    $names = explode(';', $names);
    if (is_array($names)) foreach ($names as $nm) {
        $nm = trim($nm); if (empty($nm)) continue;
        $nameKey = lastNameFirst($nm); // use "Last, First M." as key to array
        $subRevs[$nameKey] = $nm;
      }
  }
  $qry = "SELECT name FROM {$SQLprefix}committee WHERE !(flags & ?)";
  $res = pdo_query($qry, array(FLAG_IS_CHAIR));
  $pcMembers = array();
  while ($row = $res->fetch(PDO::FETCH_NUM)) {
    $name = trim($row[0]);
    if (empty($name)) continue;
    $nameKey = lastNameFirst($name); // use "Last, First M." as key to array
    $pcMembers[$nameKey] = $name;    
  }
  
  // sort reviewer and PC-member arrays by keys
  ksort($subRevs);
  ksort($pcMembers);

  // Generate the LaTeX file

  $ltxFile = <<<EndMark
% LaTeX2e file in llncs formal with TOC and indexes for $cName.
% You can store this page to file $cNameLowCase.tex
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% In order to generate an Author Index do the following:
% After TeXing this document start the program MakeIndex by typing
%   makeindex -s sprmindx.sty <filename>
% into the command line.
% (On DOS systems you may need to use the command MAKEINDX.)
% Now TeX this file once again, then you will get an Author Index.
% TeX this file once more, then the TOC will be complete.
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
\\documentclass{llncs}
%
\\usepackage{makeidx}  % allows for index generation
\\makeindex
%
\\begin{document}
%
\\frontmatter          % for the preliminaries
%
\\pagestyle{headings}  % switches on printing of running heads
%
\\chapter*{Preface}
%
$cName was held at...
%\\input{preface.tex}

\\begin{flushright}\\noindent
January 2001\\hfill Your Name Here\\\\
$cName Program Chair
\\end{flushright}
%
\\chapter*{External reviewers}
%
\\begin{multicols}{3}
\\noindent

EndMark;

  // print the list of sub-reviewers, sorted by last name
  foreach ($subRevs as $subrev) {
    $ltxFile .= $subrev . "\\\\ \n";
  }
 
  $ltxFile .= <<<EndMark
\\end{multicols}
%
\\chapter*{{\\huge $cName}\\\\
\\ \\\\
$cFullName}

\\vspace{-\\bigskipamount}
\\vspace{-\\bigskipamount}
\\begin{center}
{\\large Some Location, Some City, State or Country\\\\
Dates}

\\bigskip\\bigskip Sponsored by \\emph{Somebody}

\\medskip \\mbox{Organized in cooperation with \\emph{Somebody else}}

\\bigskip\\bigskip\\textbf{\\large General Chair}

 \\smallskip
Name and Affiliation

\\bigskip\\bigskip\\textbf{\\large Program Chair}

\\smallskip
Name and Affiliation


\\bigskip\\textbf{\\large Program Commitee}

\\smallskip\\begin{tabular}{@{}p{4.2cm}@{}p{7.2cm}@{}}

EndMark;

  // print the list of PC members in the formt "Name & Affiliation \\"
  foreach ($pcMembers as $pcm) {
    $ltxFile .= $pcm . "\t& affiliations\\\\ \n";
  }

  $ltxFile .= <<<EndMark
\\end{tabular}

%\\bigskip\\bigskip\\textbf{\\large Organizing Committee}
%
%\\smallskip\\begin{tabular}{@{}p{4.2cm}@{}p{7.2cm}@{}}
%One Person    & Affiliations \\\\
%Second Person & Affiliations \\\\
%\\end{tabular}
\\end{center}

%
%\\section*{Sponsoring Institutions}
%
%Bernauer-Budiman Inc., Reading, Mass.\\\\
%The Hofmann-International Company, San Louis Obispo, Cal.\\\\
%Kramer Industries, Heidelberg, Germany
%
\\tableofcontents
%
\\mainmatter              % start of the contributions

%% add session names to the table-of-contents with the \\addtocmark command
%\\addtocmark{Zero-Knowledge}


EndMark;

  // print title/authors/page-number of submissions
  $curVol = 0;
  $curPage = 1;
  foreach ($papers as $subId => $ppr) if ($ppr['pOrder']>0) {
    if ($ppr['volume'] != $curVol) {
      $curVol = $ppr['volume'];
      $curPage = 1;
      $ltxFile .= <<<EndMark
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%%%%                     Volume $curVol                             %%%%
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

EndMark;
    }
    $ltxFile .= "\\setcounter{page}{".$curPage."}\n";
    $curPage += $ppr['nPages'];
    $ltxFile .= "\\title{".$ppr['title']."}\n";
    $ltxFile .= "\\author{".str_replace(';', ' \and ', $ppr['authors'])."}\n";
    $authors = explode(';', $ppr['authors']);
    foreach($authors as $athr) {
      $athr = trim($athr);
      if (!empty($athr)) 
	$ltxFile .= "\\index{" . lastNameFirst($athr) . "}\n";
    }
    $ltxFile .= "\\maketitle\n\\clearpage\n\n";
  }

  $ltxFile .= <<<EndMark
%---------------------------------------------------------------
\\setcounter{page}\{$curPage}
\\addtocmark[2]{Author Index}

%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

\\renewcommand{\\indexname}{Author Index}
\\printindex

\\end{document}

EndMark;

}

header("Content-Type: application/x-tex; charset=utf-8");
header("Content-Disposition: inline; filename=$cNameLowCase.tex");
print $ltxFile;
//print "<pre>" . htmlspecialchars($ltxFile) . "</pre>\n"; //debug
exit();

function cmpOrder($a, $b)
{
  return $a['pOrder'] - $b['pOrder'];
}

function lastNameFirst($name)
{
  str_replace('\t', ' ', trim($name)); // replace tabs with spaces
  $pos = strrpos($name, ' ');          // find last space
  if ($pos === false) {                // no spaces? is it all a single word?
    return $name;
  }

  // we assume that the last name appears after the last space
  $surName = substr($name, $pos+1);  // persumably this is the last name
  $given = substr($name, 0, $pos);   // and thats the rest of the name

  return $surName . ', ' . rtrim($given);
}
?>
