<?php
$output = null;

foreach ($result['stats']['stats_fields'] as $k => $values) {
    $output .= '<a href="#general_stats_' . $k . '">' . ucfirst($k) . '</a><br/>';
} 

if ($output) { 
    $output = '<div class="facet_block_menu"><b>General Stats</b><br/>' . $output . '</div>';
}

$outputFacet = null;
foreach ($facetsResult as $k => $facetResult) {
    if (count($facetResult) <= 1) {
        continue;
    }

    $outputFacet .= '<a href="#facet_' . $k . '">' . ucfirst($k) . '</a><br/>';
}

if ($outputFacet) { 
    $output .= '<div class="facet_block_menu"><b>Facets</b></br>' . $outputFacet . '</div>';
}

if (!empty($docs)) {
    $output .= '<div class="facet_block_menu"><a href="#docs">Docs</a></div>';
}

echo $output;
