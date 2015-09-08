<?php
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
