<?php
/**
 * Simple poc / demo of tika & solr search
 */
$config = require __DIR__ . '/../conf/webapp/configuration.php';

// Request params
$f = isset($_REQUEST['f']) ? $_REQUEST['f'] : array();
$q = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : 0;
$rows = isset($_REQUEST['rows']) ? $_REQUEST['rows'] : 99;

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
}


header('Content-type: text/xml');

require __DIR__ . '/../conf/opentext/schema-import.xml';
