<?php
/**
 * Config file used by the webapp
 */

// Facets configuration used by the search app
$facets['params']['limit'] = 99999;

// List of fields to use for facetted search
$facets['fields'][] = array('field' => 'contentType');
$facets['fields'][] = array('field' => 'applicationName');
$facets['fields'][] = array('field' => 'company');
$facets['fields'][] = array('field' => 'author');
$facets['fields'][] = array('field' => 'customTags');
$facets['fields'][] = array('field' => 'fingerprint', 'minCount' => 2);

// Facets for Date related fields 
$facets['date'] = array('start' => '2000-01-01T01:01:01Z', 'end' => 'NOW', 'gap' => '%2B6MONTH');
$facets['date']['fields'] = array('creationDate');

// Facets for integer Range related fields
$facets['range'] = array('start' => 0, 'end' => 1024 * 1024 * 16, 'gap' => 1024 * 256);
$facets['range']['fields'] = array('contentLength');
$facets['stats']['field'] = 'contentLength';

// Return config settings
return array(
    'solrUrl' => 'http://localhost:8983/solr/origin',
    'facets' => $facets,
);
