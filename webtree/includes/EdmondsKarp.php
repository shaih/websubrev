<?php
/* Web Submission and Review Software
 * Written by Shai Halevi
 * This software is distributed under the terms of the open-source license
 * Common Public License (CPL) v1.0. See the terms in the file LICENSE.txt
 * in this package or at http://www.opensource.org/licenses/cpl1.0.php
 */

/* The function maximum_flow gets as parameter a 2-D array $caps, where
 * $caps[i][j] is the capacity of the edge i -> j.  It returns an array
 * $flow where $flow[i][j] is the flow on edge i -> j.
 */
function maximum_flow(&$flow, $caps, $src, $sink)
{
  $flowVal = 0;
  $path = array();

  // Make sure that if $caps[$i][$j] is defined then so are
  // $caps[$j][$i], $flow[$i][$j], and $flow[$j][$i]
  foreach($caps as $i => $x) foreach ($x as $j => $c) {
    if (!isset($caps[$j][$i])) $caps[$j][$i] = 0;
    if (!isset($flow[$i][$j])) $flow[$i][$j] = 0;
    if (!isset($flow[$j][$i])) $flow[$j][$i] = 0;
  }

  // The Ford-Fulkerson/Edmonds-Karp/Dinic algorithm itself
  while (true) {
    // Find an augmenting path in the network
    $more = augmenting_path($path, $caps, $flow, $src, $sink);
    if ($more==0) break;

    // Add the augmenting path to the flow
    $next = $sink;
    while ($next != $src) {
      $prev = $path[$next];
      if (!isset($flow[$next][$prev])) $flow[$next][$prev] = 0;
      if (!isset($flow[$prev][$next])) $flow[$prev][$next] = 0;
      if ($flow[$next][$prev] >= $more) // reduce flow in opposite direction
	$flow[$next][$prev] -= $more;
      else {                            // add flow in this direction
	$flow[$prev][$next] += $more - $flow[$next][$prev];
	$flow[$next][$prev] = 0;
      }
      $next = $prev;
    }
    $flowVal += $more;
  }

  // Remove from $flow all the flow-zero edges
  foreach($flow as $i => $x) {
    foreach ($x as $j => $f)
      if ($f == 0) unset($flow[$i][$j]);
    if (empty($flow[$i])) unset($flow[$i]);
  }
  return $flowVal;
}

/* The function augmenting_path performs a BFS on the netwrok *
 * to find a shortest sugmenting path                         */
function augmenting_path(&$path, $caps, &$flow, $src, $sink)
{
  // initialize the path to an empty one
  foreach($caps as $i => $x) $path[$i] = NULL;
  $path[$sink] = NULL;       // just making sure

  // Initialize a queue with the source in it
  $queue = array($src);
  $head = $tail = 0;

  $path[$src] = $src;        // mark the source as visited
  $done = ($src == $sink);
  while ($head <= $tail && !$done) {
    $current = $queue[$head++];

    foreach($caps[$current] as $neighbor => $capacity) {
      if (isset($path[$neighbor])) continue; // already visited here
      $flowPlus = $flow[$current][$neighbor];
      $flowMinus= $flow[$neighbor][$current];

      if ($capacity>$flowPlus || $flowMinus>0) {
	// insert the neighbor into the queue and the path
	$path[$neighbor] = $current;
	$queue[++$tail] = $neighbor;

	// Test if the new neighbor is the sink
	if ($neighbor == $sink) {
	  $done = true;
	  break;
	}
      }
    }
  }

  // If we reached the sink, calculate the flow on the augmenting path
  if ($done) {
    $flowVal = defined(PHP_INT_MAX)? PHP_INT_MAX : 0x7fffffff;
    $next = $sink;
    while ($next != $src) {
      $prev = $path[$next];
      $flowPlus = $flow[$prev][$next];
      $flowMinus= $flow[$next][$prev];
      if ($flowVal > ($caps[$prev][$next]-$flowPlus) + $flowMinus)
	  $flowVal = ($caps[$prev][$next]-$flowPlus) + $flowMinus;
      $next = $prev;
    }
  }
  else $flowVal = 0;

  return $flowVal;
}
/********** A simple network to test the code from above ************
$caps = array(
	0 => array( 1 => 3, 2 => 3),
	1 => array( 2 => 2, 3 => 3),
	2 => array( 3 => 3, 4 => 2),
	3 => array( 4 => 4, 5 => 3),
	4 => array( 5 => 3)
);
$flow = array();
$f = maximum_flow($flow, $caps, 0, 5);
echo "<pre>\n";
print_r($flow);
print_r($f);
echo "</pre>\n";
*********************************************************************/
?>