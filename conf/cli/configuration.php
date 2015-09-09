<?php
/**
 * Config file used by the command line import script
 */

// Return config settings
return array(
    // URL to the Solr core webservice 
    'solrUrl' => 'http://127.0.0.1:8983/solr/origin',
   
    // URL to the Tika webservice
    'tikaUrl' => 'http://127.0.0.1:9998/meta',

    // TME related settings
    'tme' => array(
        'alchemyApi' => '',
        'mimeTypes' => array(
        ),
    ),
);
