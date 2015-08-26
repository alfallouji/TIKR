<?php
/** 
 * Scan/Index filesystem implementation
 */
$help = <<<EOT

Tikr - Parse folder or file, extract metadata and index into solr

Usage: {$_SERVER['_']} {$_SERVER['argv'][0]} OPTIONS

    --generateManifest          Generate the manifest file(s)
    --allowAll                  Allow all formats
    --help                      Display this help

    --source=FOLDER1|FILE1      Folder or file to scan
    --solrUrl=URL               URL to the solr core (used by Tikr)
    --manifestFolder=FOLDER2    Folder where manifest(s) will be stored
    --customTags                Comma separated tags to be added to the customTags index in Tikr
    --cacheFolder=FOLDER3       Cache folder -- not implemented yet

Example: {$_SERVER['_']} {$_SERVER['argv'][0]} --allowAll --source=/media/share1 --customTags=architecture,sydney


EOT;

require __DIR__ . '/../common/Tikr/Parser/Base.php';
require __DIR__ . '/../common/Tikr/Parser/Filesystem.php';
require __DIR__ . '/../common/Tikr/Solr/Client.php';

// Script options 
$opts = array('help', 'allowAll', 'generateManifest', 'source::', 'cacheFolder::', 'solrUrl::', 'customTags::', 'manifestFolder::',);
$options = getopt('', $opts);
$allowAll = isset($options['allowAll']) ? true : false;
$generateManifest = isset($options['generateManifest']) ? true : false;
$source = isset($options['source']) ? $options['source'] : __DIR__ . '/../../documents/';
$cacheFolder = isset($options['cacheFolder']) ? $options['cacheFolder'] : __DIR__ . '/../../cache/';
$manifestFolder = isset($options['manifestFolder']) ? $options['manifestFolder'] : __DIR__ . '/../../documents/manifest/';
$customTags = isset($options['customTags']) ? explode(',', $options['customTags']) : array();
$solrUrl = isset($options['solrUrl']) ? $options['solrUrl'] : 'http://localhost:8983/solr/origin';
$displayHelp = isset($options['help']) ? true : false;

if ($displayHelp) {
    die($help);
}

$fileFormats = array('doc', 'docx', 'pdf');
$tikaPath = __DIR__ . '/../../tika/tika-app.jar';

$source = realpath($source);
$cacheFolder = realpath($cacheFolder);
$manifestFolder = realpath($manifestFolder);

if (empty($source)) {
    echo 'Missing source parameter' . PHP_EOL;
}

echo 'Tikr - Filesystem parser' . PHP_EOL;
echo str_pad('', 90, '-') . PHP_EOL;

$isFile = is_file($source) && !is_dir($source);

if ($isFile) {
    echo 'Source Filename: ' . $source . PHP_EOL;
} else {
    echo 'Source Folder: ' . $source . PHP_EOL;
}

echo 'Cache Folder: ' . $cacheFolder . PHP_EOL;
echo 'Formats: ';
if ($allowAll) {
    echo 'All';
} else {
    echo implode(',', $fileFormats);
}

if (!empty($customTags)) {
    echo PHP_EOL . 'Custom tags: ' . $options['customTags'];
}
echo PHP_EOL . str_pad('', 90, '-') . PHP_EOL;

// Start parsing
$solr = new Tikr\Solr\Client($solrUrl);
$parser = new Tikr\Parser\Filesystem($solr, $fileFormats, $cacheFolder, $manifestFolder, $tikaPath);
if ($generateManifest) {
    $parser->generateManifest($source, $allowAll);
} elseif ($isFile) {
    $parser->processFile($source, $customTags);
} else { 
    $parser->processFolder($source, $allowAll, $customTags);
}
