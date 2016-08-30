<?php
$output = null;

foreach ($facetsResult as $k => $facetResult) {
    if (count($facetResult) <= 1) {
        continue;
    }
    $output .= '<div class="module">';
    $output .= '<a id="facet_' . $k . '" class="anchor"></a>';
    $output .= '<h3>' . ucfirst($k) . '<h3>';
    arsort($facetResult);
    $sum = $result['response']['numFound'];
    $output .= '<pre style="max-height:500px; overflow-y:scroll;"><table class="table table-hover table-condensed">';
    $class = 'active';
    foreach ($facetResult as $key => $value) {
        $class = ($class == 'info') ? 'active' : 'info';
        $facetParams = $f;
        $facetParams[$k] = $key;
        $url = '?q=' . $q . '&' . http_build_query(array('f' => $facetParams));
        $perc = number_format(($value / $sum * 100), 1);
        $output .= '<tr class="' . $class . '"><td style="width:550px;"><a href="' . $url . '">';
        $output .= $key . '</a></td><td style="width:50px;"><b>' . $value . '</b></td><td style="width:50px;"><b>' . $perc . '%</b></td>';
        $output .= '<td>';
        $output .= '<div style="background-color:#4285F4;width:' . $perc . '%;height:20px;"></div>';
        $output .= '</td>';
        $output .= '</tr>';
    }
    $output .= '</table></pre></div>';
}

echo $output;
