<?php
/**
 * Simple poc / demo of tika & solr search
 */
$config = require __DIR__ . '/../conf/webapp/configuration.php';

// Request params
$f = isset($_REQUEST['f']) ? $_REQUEST['f'] : array();
$q = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
$rows = isset($_REQUEST['rows']) ? $_REQUEST['rows'] : 10;

// Default values
$sort = '';
$indent = 'on';
$hl = null;
$docs = array();
$params = array('q' => $q, 'start' => $start, 'rows' => $rows, 'f' => $f);
$pagination = null;

// Facets definition (refer to conf/webapp/configuration.php)
$facets = $config['facets'];

// Execute search if a query is passed
if ($q) {
    require __DIR__ . '/../common/Tikr/Solr/Client.php';
    $solr = new \Tikr\Solr\Client($config['solrUrl']);
    $query = $q;
    foreach ($f as $k => $v) {
        $query .= ' AND ' . $k . ':"' . $v . '"';
    }
    $result = $solr->search($query, $start, $rows, $sort, $indent, $hl, $facets);
    $docs = isset($result['response']['docs']) ? $result['response']['docs'] : array();
    $facetsResult = isset($result['facet_counts']['facet_fields']) ? $result['facet_counts']['facet_fields'] : array();
    $facetsDate = isset($result['facet_counts']['facet_dates']) ? $result['facet_counts']['facet_dates'] : array();
    $facetsRange = isset($result['facet_counts']['facet_ranges']) ? $result['facet_counts']['facet_ranges'] : array();

    if ($start == 0 && $rows < $result['response']['numFound']) {
        $newParams = $params;
        $newParams['start']++;
        $pagination = '<a href="?' . http_build_query($newParams) . '">Next</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tikr::Content Discovery</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        #body .table { margin-bottom:0px; }
    </style>
  </head>
  <body style="padding:20px;" id="body">
    <h1><a href="?q=*:*">Tikr::Content Discovery</a></h1>
    <form class="form-inline" action="?" method="get">
        <input class="form-control" style="width:80%;min-width:300px;" type="text" name="q" id="q" value="<?php echo htmlentities($q); ?>" />
        <input class="btn btn-default" type="submit" />
    </form>
    <br/>
    <?php
        if ($q && empty($docs)) { 
            echo 'No result found';
        } elseif ($q && !empty($docs)) {
            echo 'Found : ' . $result['response']['numFound'] . ' doc(s)';
            if (!empty($f)) { 
                echo '<br/>Facet filters : ';
            }
            foreach ($f as $k => $v) { 
                $newParams = $params;
                unset($newParams['f'][$k]);
                $link = '<a href="?' . http_build_query($newParams) . '">[x]</a> ';
                echo '<br/>' . $link . $k . '=' . $v;
            }
            echo '<hr/>';

            echo '<h2>General stats</h2>';
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
                    
                echo '<h3>' . $k . '</h3>';
                echo '<pre><table class="table table-hover table-condensed">';
                $class = 'active';
                foreach ($values as $key => $value) {
                    $class = ($class == 'info') ? 'active' : 'info';
                    $facetParams = $f;
                    $facetParams[$k] = $key;
                    echo '<tr class="' . $class . '"><td style="width:600px;">';
                    echo $key . '</td><td><b>' . $value . '</b></td></tr>';
                }
                echo '</table></pre>';
            }

            echo '<h2>Facets/Filters</h2>';
            foreach ($facetsResult as $k => $facetResult) {
                if (empty($facetResult)) {
                    continue;
                }
                echo '<h3>' . $k . '<h3>';
                arsort($facetResult);
                $sum = $result['response']['numFound'];
                echo '<pre><table class="table table-hover table-condensed">';
                $class = 'active';
                foreach ($facetResult as $key => $value) {
                    $class = ($class == 'info') ? 'active' : 'info';
                    $facetParams = $f;
                    $facetParams[$k] = $key;
                    $url = '?q=' . $q . '&' . http_build_query(array('f' => $facetParams));
                    $perc = number_format(($value / $sum * 100), 1);
                    echo '<tr class="' . $class . '"><td style="width:600px;"><a href="' . $url . '">';
                    echo $key . '</a></td><td style="width:100px;"><b>' . $value . '</b></td><td>' . $perc . '%</td></tr>';
                }
                echo '</table></pre>';
            }
            
            if (!empty($facetsDate)) {
                foreach ($facetsDate as $k => $facetDate) {
                    unset($facetDate['gap']);
                    unset($facetDate['end']);
                    unset($facetDate['start']);
                    if (empty($facetDate)) { 
                        continue;
                    }
                    echo '<h3>' . $k . ' <small>gap : ' . urldecode($facets['date']['gap']) . '</small></h3>';
                    echo '<pre><table class="table table-hover table-condensed">';
                    $class = 'active';
                    krsort($facetDate);
                    $endDate = ' till now'; 
                    foreach ($facetDate as $key => $value) {
                        $class = ($class == 'info') ? 'active' : 'info';
                        $url = htmlentities('?q=' . $k . ':[' . $key . ' TO ' . $key . $facets['date']['gap'] . ']');
                        $url .= '&' . http_build_query(array('f' => $f));
                        echo '<tr class="' . $class . '"><td style="width:600px;"><a href="' . $url . '">';
                        $startDate = date('Y-m-d', strtotime($key));
                        echo $startDate . ' -> ' . $endDate . '</a></td><td><b>' . $value . '</b></td></tr>';
                        $endDate = $startDate;
                    }
                    echo '</table></pre>'; 
                }
            }

            if (!empty($facetsRange)) {
                foreach ($facetsRange as $k => $facetRange) {
                    echo '<h3>' . $k . ' <small>gap : ' . (urldecode($facets['range']['gap']) / 1024) . ' ko</small></h3>';
                    $gap = $facetRange['gap'];
                    unset($facetRange['gap']);
                    unset($facetRange['end']);
                    echo '<pre><table class="table table-hover table-condensed">';
                    $class = 'active';
                    krsort($facetRange); 
                    foreach ($facetRange['counts'] as $key => $value) {
                        $class = ($class == 'info') ? 'active' : 'info';
                        $url = htmlentities('?q=' . $k . ':[' . $key . ' TO ' . ($key + $gap) . ']');
                        echo '<tr class="' . $class . '"><td style="width:600px;"><a href="' . $url . '">';
                        $endKey = ($key + $gap) / 1024;
                        echo ($key / 1024) . ' ko -> ' . $endKey . ' ko</a></td><td><b>' . $value . '</b></td></tr>';
                    }
                    echo '</table></pre>'; 
                }
            }

            echo '<hr/><h2>Docs</h2>' , $pagination;
            foreach ($docs as $doc) {
                echo '<pre>';
                echo '<a class="btn btn-primary" value="view document" href="view.php?id=' . $doc['solrDocumentId'] . '">View Document</a><br/><br/>';
                foreach ($doc as $k => $v) {
                    if (is_array($v)) { 
                        $v = implode(', ', $v);
                    }
                    echo $k . ' => <b>' . $v . '</b><br/>';
                }
                echo '</pre>';
            }
            echo $pagination;
        }
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  </body>
</html>
