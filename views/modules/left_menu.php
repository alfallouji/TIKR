<?php
$output = null;

foreach ($facetsResult as $k => $facetResult) {
    if (count($facetResult) <= 1) {
        continue;
    }
    $output .= '<a href="#' . $k . '">' . $k . '</a><br/>';
}

echo $output;
