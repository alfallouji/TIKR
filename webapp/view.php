<?php
/**
 * View a specific file
 */
$config = require __DIR__ . '/../conf/webapp/configuration.php';

require __DIR__ . '/../common/Tikr/Solr/Client.php';
$solr = new \Tikr\Solr\Client($config['solrUrl']);
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

if ($id) {
    $q = 'solrDocumentId:' . $id;
    $result = $solr->search($q);

    if (isset($result['response']['docs'][0])) {
        $file = $result['response']['docs'][0]['filename']; 
        header('Content-Type: ' . $result['response']['docs'][0]['contentType']);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        echo readfile($file);
    }
}
