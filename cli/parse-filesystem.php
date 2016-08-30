<?php
/** 
 * Scan/Index filesystem implementation
 */
$start = microtime(true);

$help = <<<EOT

Tikr - Parse folder or file, extract metadata and index into solr

Usage: {$_SERVER['_']} {$_SERVER['argv'][0]} OPTIONS

    --generateManifest          Generate the manifest file(s)
    --ignoreManifest            Perform a full scan/re-index (ignores manifest files)
    --allowAll                  Allow all formats
    --mineText                  Do text mining on content
    --help                      Display this help

    --source=FOLDER1|FILE1      Folder or file to scan
    --solrUrl=URL               URL to the solr core webservice (used by Tikr)
    --tikaUrl=URL               URL to the Tika webservice
    --manifestFolder=FOLDER2    Folder where manifest(s) will be stored
    --customTags                Comma separated tags to be added to the customTags index in Tikr
    --cacheFolder=FOLDER3       Cache folder -- not implemented yet

Example: {$_SERVER['_']} {$_SERVER['argv'][0]} --allowAll --source=/media/share1 --customTags=architecture,sydney


EOT;

require __DIR__ . '/../common/Tikr/TextMining/Alchemy.php';
require __DIR__ . '/../common/Tikr/Parser/Base.php';
require __DIR__ . '/../common/Tikr/Parser/Filesystem.php';
require __DIR__ . '/../common/Tikr/Solr/Client.php';
require __DIR__ . '/../common/Tikr/Tika/Client/Rest.php';
require __DIR__ . '/../common/Tikr/Helper/Type.php';
require __DIR__ . '/../vendor/PHP-OpenCalais-master/opencalais.php';

$config = require __DIR__ . '/../conf/cli/configuration.php';

// Script options 
$opts = array(
    'help', 
    'allowAll', 
    'mineText', 
    'generateManifest', 
    'ignoreManifest', 
    'source::', 
    'cacheFolder::', 
    'solrUrl::', 
    'customTags::', 
    'manifestFolder::',
);

$options = getopt('', $opts);
$allowAll = isset($options['allowAll']);
$generateManifest = isset($options['generateManifest']);
$ignoreManifest = isset($options['ignoreManifest']);
$mineText = isset($options['mineText']);
$source = isset($options['source']) ? $options['source'] : __DIR__ . '/../../documents/';
$cacheFolder = isset($options['cacheFolder']) ? $options['cacheFolder'] : __DIR__ . '/../../cache/';
$manifestFolder = isset($options['manifestFolder']) ? $options['manifestFolder'] : __DIR__ . '/../../documents/manifest/';
$customTags = isset($options['customTags']) ? explode(',', $options['customTags']) : array();
$solrUrl = isset($options['solrUrl']) ? $options['solrUrl'] : $config['solrUrl'];
$tikaUrl = isset($options['tikaUrl']) ? $options['tikaUrl'] : $config['tikaUrl'];
$displayHelp = isset($options['help']);

$fileFormats = array('doc', 'docx', 'pdf');
$tikaPath = __DIR__ . '/../../tika/tika-app.jar';

if ($displayHelp) {
    die($help);
}

echo PHP_EOL . PHP_EOL . str_pad('', 90, '-') . PHP_EOL;
echo 'Tikr - Filesystem parser' . PHP_EOL . PHP_EOL;

$source = realpath($source);
$cacheFolder = realpath($cacheFolder);
$manifestFolder = realpath($manifestFolder);

if (empty($source)) {
    echo 'Missing source parameter' . PHP_EOL;
}

echo 'Options' . PHP_EOL . str_pad('', 90, '-') . PHP_EOL;
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

echo PHP_EOL . 'Ignore manifest: ';
echo $ignoreManifest ? 'Yes' : 'No';

echo PHP_EOL . PHP_EOL . 'Processing' . PHP_EOL . str_pad('', 90, '-') . PHP_EOL;

// Start parsing
$solr = new Tikr\Solr\Client($solrUrl);
$tme = new OpenCalais($config['tme']['openCalais']);
$metadataExtractor = new Tikr\Tika\Client\Rest($tikaUrl);

$parser = new Tikr\Parser\Filesystem($solr, $tme, $metadataExtractor, $fileFormats, $cacheFolder, $manifestFolder, $tikaPath);
if ($generateManifest) {
    $parser->generateManifest($source, $allowAll);
} elseif ($isFile) {
    $parser->processFile($source, $customTags, $mineText);
} else { 
    $parser->processFolder($source, $allowAll, $customTags, $mineText, $ignoreManifest);
}

echo PHP_EOL . 'Execution time : ' . number_format((microtime(true) - $start), 2) . ' seconds' . PHP_EOL . PHP_EOL;
