<?php
$output = null;

foreach ($facetsResult as $k => $facetResult) {
    if (count($facetResult) <= 1) {
        continue;
    }
    $output .= '<a href="#' . $k . '">' . ucfirst($k) . '</a><br/>';
}

if (!empty($docs)) {
    if ($output) {
        $output .= '<hr/>';
    }
    $output .= '<a href="#docs">Docs</a><br/>';
}

echo $output;
