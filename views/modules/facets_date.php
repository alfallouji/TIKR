<?php

if (!empty($facetsDate)) {
    foreach ($facetsDate as $k => $facetDate) {
        unset($facetDate['gap']);
        unset($facetDate['end']);
        unset($facetDate['start']);
        if (count($facetDate) <= 1) { 
            continue;
        }
        echo '<h3>' . $k . ' <small>gap : ' . urldecode($facets['date']['gap']) . '</small></h3>';
        echo '<pre style="max-height:500px; overflow-y:scroll;"><table class="table table-hover table-condensed">';
        $class = 'active';
        krsort($facetDate);
        $endDate = ' till now'; 
        $sum = array_sum($facetDate);
        foreach ($facetDate as $key => $value) {
            $class = ($class == 'info') ? 'active' : 'info';
            $url = htmlentities('?q=' . $q . ' AND (' . $k . ':[' . $key . ' TO ' . $key . $facets['date']['gap'] . '])');
            $url .= '&' . http_build_query(array('f' => $f));
            echo '<tr class="' . $class . '"><td style="width:600px;"><a href="' . $url . '">';
            $startDate = date('Y-m-d', strtotime($key));
            echo $startDate . ' -> ' . $endDate . '</a></td><td style="width:50px;"><b>' . $value . '</b></td>';
            $perc = number_format(($value / $sum * 100), 1);
            echo '<td style="width:50px;">' . $perc . '%</td>';
            echo '<td>';
            echo '<div style="background-color:#4285F4;width:' . $perc . '%;height:20px;"></div>';
            echo '</td></tr>';

            $endDate = $startDate;
        }
        echo '</table></pre>'; 
    }
}

if (!empty($facetsDate)) {
    foreach ($facetsDate as $k => $facetDate) {
        unset($facetDate['gap']);
        unset($facetDate['end']);
        unset($facetDate['start']);

        if (count($facetDate) <= 1) { 
            continue;
        }
        $jsonData = null;
        $facetTitle = $k . ' with a gap of ' . urldecode($facets['date']['gap']);
        ksort($facetDate);
        $jsonArray = array("['Time interval', 'Number of documents']");
        foreach ($facetDate as $key => $value) {
            $url = htmlentities('?q=' . $k . ':[' . $key . ' TO ' . $key . $facets['date']['gap'] . ']');
            $url .= '&' . http_build_query(array('f' => $f));
            $startDate = date('Y-m', strtotime($key));
            $jsonArray[] = "['" . $startDate . "', " . $value . "]";
        }

        $uniqid = 'chart_' . uniqid();
        echo '<h3>' . $k . ' <small>gap : ' . urldecode($facets['date']['gap']) . '</small></h3>';
        echo '<pre>';
        echo '<div id="' . $uniqid . '" style="width: 100%; height:500px;"></div></pre>';       
 
        ?>
        <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart", "bar"]});
        google.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([<?php echo implode(', ', $jsonArray); ?>]);

            var options = {
title: '<?php echo $facetTitle; ?>',
       legend: { position: 'none' },
            };

            var chart = new google.charts.Bar(document.getElementById('<?php echo $uniqid; ?>'));
            chart.draw(data, options);
        }
        </script>
            <?php
    }
}

if (!empty($facetsRange)) {
    foreach ($facetsRange as $k => $facetRange) {
        if (count($facetRange['counts']) <= 1) { 
            continue;
        }

        echo '<h3>' . $k . ' <small>gap : ' . (urldecode($facets['range']['gap']) / 1024) . ' ko</small></h3>';
        $gap = $facetRange['gap'];
        unset($facetRange['gap']);
        unset($facetRange['end']);
        echo '<pre style="max-height:500px; overflow-y:scroll;"><table class="table table-hover table-condensed">';
        $class = 'active';
        krsort($facetRange); 

        $sum = array_sum($facetRange['counts']);
        foreach ($facetRange['counts'] as $key => $value) {
            $class = ($class == 'info') ? 'active' : 'info';
            $url = htmlentities('?q=' . $k . ':[' . $key . ' TO ' . ($key + $gap) . ']');
            echo '<tr class="' . $class . '"><td style="width:600px;"><a href="' . $url . '">';
            $endKey = ($key + $gap) / 1024;
            echo ($key / 1024) . ' ko -> ' . $endKey . ' ko</a></td><td style="width:50px;"><b>' . $value . '</b></td>';
            $perc = number_format(($value / $sum * 100), 1);
            echo '<td style="width:50px;">' . $perc . '%</td>';
            echo '<td>';
            echo '<div style="background-color:#4285F4;width:' . $perc . '%;height:20px;"></div>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table></pre>'; 
    }
}
