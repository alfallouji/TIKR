<?php
$output = null;
foreach ($result['stats']['stats_fields'] as $k => $values) {
    
    $values = array( 
        'min' => (int) ($values['min'] / 1024) . ' ko',
        'max' => (int) ($values['max'] / 1024) . ' ko',
        'count' => $values['count'],
        'missing' => $values['missing'],
        'sum' => (int) ($values['sum'] / 1024) . ' ko',
        'mean' => (int) ($values['mean'] / 1024) . ' ko',
        'stddev' => (int) ($values['stddev'] / 1024) . ' ko',
    );

    $output .= '<div class="module">';
    $output .= '<a class="anchor" id="general_stats_' . $k . '"></a>';
    $output .= '<h3>General stats : ' . $k . '</h3>';
    $output .= '<pre><table class="table table-hover table-condensed">';
    $class = 'active';
    foreach ($values as $key => $value) {
        $class = ($class == 'info') ? 'active' : 'info';
        $facetParams = $f;
        $facetParams[$k] = $key;
        $output .= '<tr class="' . $class . '"><td style="width:200px;">';
        $output .= $key . '</td><td><b>' . $value . '</b></td></tr>';
    }
    $output .= '</table></pre></div>';
}

echo $output;
