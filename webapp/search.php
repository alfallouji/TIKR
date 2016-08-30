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

    if ($start >= 0 && $rows < $result['response']['numFound']) {
        $newParams = $params;
        $newParams['start']++;
        $pagination = '<a href="?' . http_build_query($newParams) . '#docs">Next</a>';
    }

    $exportParams = $params;
    $exportParams['rows'] = 100;
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <style>
        #body .table { margin-bottom:0px; }
        .module { } 
        .top { padding-left:20px; border-bottom:solid thin #DDD; float:left; width:100%; padding-bottom:20px; position:fixed; background-color:white; z-index:999999; }
        .left { float:left; width:150px; height:100%; margin-top:180px; position:fixed; clear:both;}
        .middle { border-left:solid thin #DDD; padding-left:20px; float:left; margin-left:150px; margin-top:150px; }
        a.anchor { display: block; position: relative; top: -170px; visibility: hidden; }
        pre { max-width:1200px; }
        .facet_block_menu { padding-left:20px; border-bottom:thin solid #EEE; margin-bottom:20px; padding-bottom:10px; }
    </style>
  </head>
  <body id="body">
   <div class="top">
        <h1><a href="?q=*:*">Tikr::Content Discovery</a></h1>
        <form class="form-inline" action="?" method="get">
            <input class="form-control" style="width:80%;min-width:300px;" type="text" name="q" id="q" value="<?php echo htmlentities($q); ?>" />
            <input class="btn btn-default" type="submit" />
        </form>
    <?php
        if ($q && empty($docs)) { 
            echo 'No result found</div>';
        } elseif ($q && !empty($docs)) {
            echo 'Found: ' . $result['response']['numFound'] . ' doc(s) | ';
            echo '<a target="_blank" href="export-xml.php?' . http_build_query($exportParams) . '">XML Export (opentext)</a>';
           
            if (!empty($f)) { 
                echo '<br/>Facet filters: ';
            }
            foreach ($f as $k => $v) { 
                $newParams = $params;
                unset($newParams['f'][$k]);
                $link = '<a href="?' . http_build_query($newParams) . '">[x]</a> ';
                echo $link . $k . '=' . $v . ' | ';
            }
            echo '</div>';
            echo '<div><div class="left">';
            require(__DIR__ . '/../views/modules/left_menu.php');
            echo '</div>';
            echo '<div class="middle">';

            // Render general statistics module
            require(__DIR__ . '/../views/modules/general_statistics.php');

            // Render facets module
            require(__DIR__ . '/../views/modules/facets.php');

            // Render date facets module
            require(__DIR__ . '/../views/modules/facets_date.php');

            // Render documents listing
            require(__DIR__ . '/../views/modules/docs.php');

            echo '</div></div>';
        }
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  </body>
</html>
