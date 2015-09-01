<?php
/**
 * Filesystem Parser using tika/solr
 */
namespace Tikr\Parser;

/**
 * Filesystem parser
 */
class Filesystem extends Base {
   
    /**
     * Process all folders and generate manifests
     * 
     * @param string $folder Folder to parse
     * @param boolean $allowAll Allow all formats or not
     * 
     * @return void
     */
    public function generateManifest($folder, $allowAll = false) { 
        $result = array();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($iterator as $info) {
            if (!$allowAll && !in_array(strtolower($info->getExtension()), $this->_fileFormats)) {
                echo '[SKIP] ' . $info->__toString(). PHP_EOL;
            } elseif ($info->isFile ()) {
                $baseName = basename($info->__toString());
                $dirName = realpath(dirname($info->__toString()));
                $result[$dirName][$baseName] = $this->generateFingerprint($info->__toString());
            }
        }

        foreach ($result as $subdir => $files) {
            $folder = $this->_manifestFolder . DIRECTORY_SEPARATOR . $subdir;
            if (!is_dir($folder)) {
                mkdir($folder, 0775, true);
            }
            $content = '<?php return ' . var_export($files, true) . ';';
            file_put_contents($folder . DIRECTORY_SEPARATOR . 'manifest.php', $content);
        }
    }

    public function processFolder($folder, $allowAll = false, $customTags = array(), $mineText = false, $ignoreManifest = false) {
        $result = array();
        $deindexed = $ignored = $notChanged = $error = $processed = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($iterator as $info) {
            try {
                $filename = realpath($info->__toString());
                if (!$info->isFile()) {
                    continue;
                } elseif (!$allowAll && !in_array(strtolower($info->getExtension()), $this->_fileFormats)) {
                    echo '[FORMAT-IGNORED] ' . $filename . PHP_EOL;
                    ++$ignored;
                } elseif (!$ignoreManifest && $info->isFile() && $this->hasBeenProcessed($filename)) {
                    echo '[NO-CHANGE] ' . $filename . PHP_EOL;
                    $dirName = dirname($filename);
                    $baseName = basename($filename);
                    $result[$dirName][$baseName] = $this->generateFingerprint($filename);
                    ++$notChanged;
                } elseif ($info->isFile()) {
                    if ($this->processFile($filename, $customTags, $mineText)) {
                        $dirName = dirname($filename);
                        $baseName = basename($filename);
                        $result[$dirName][$baseName] = $this->generateFingerprint($filename);
                        ++$processed;
                    } else { 
                        echo '[ERROR] ' . $filename . PHP_EOL;
                        ++$error;
                    }
                }
            } catch (\Exception $e) {
                echo '[EXCEPTION] ' . $e->getMessage() . PHP_EOL;
            }
        }

        // Remove file that are not in the manifest file
        foreach ($result as $subdir => $currentFiles) {
            $previousFiles = $this->loadManifest($subdir);
            $diffFiles = array_diff(array_keys($previousFiles), array_keys($currentFiles));
            foreach ($diffFiles as $file) {
                $filename = $subdir . DIRECTORY_SEPARATOR . $file;
                $md5 = md5($filename);
                echo '[DE-INDEX] ' . $filename . ' (' . $md5 . ')' . PHP_EOL;
                $this->_solr->deindex($md5);
                ++$deindexed;
            }
        }

        echo PHP_EOL . 'Generating manifests' . PHP_EOL;
        echo str_pad('', 90, '-') . PHP_EOL;

        // Generate all manifest files
        foreach ($result as $subdir => $files) {
            $folder = $this->_manifestFolder . $subdir;
            if (!is_dir($folder)) {
                mkdir($folder, 0775, true);
            }
            $content = '<?php ' . PHP_EOL . ' // Generated at ' . date('Y-m-d H:i:s') . PHP_EOL . 'return ' . var_export($files, true) . ';';
            file_put_contents($folder . DIRECTORY_SEPARATOR . 'manifest.php', $content);
            echo 'Manifest generated/updated : ' . $folder . DIRECTORY_SEPARATOR . 'manifest.php' . PHP_EOL;
        }

        echo PHP_EOL . 'Summary' . PHP_EOL; 
        echo str_pad('', 90, '-');
        echo PHP_EOL . 'Error : ' . $error . ' files(s)' . PHP_EOL;
        echo 'Format Ignored : ' . $ignored . ' file(s)' . PHP_EOL;
        echo 'Not changed : ' . $notChanged . ' file(s)' . PHP_EOL;
        echo 'De-indexed : ' . $deindexed . ' file(s)' . PHP_EOL;
        echo 'Added/Updated : ' . $processed . ' file(s)' . PHP_EOL;
    }

    public function processFile($filename, $customTags = array(), $mineText = false) {
        echo '[PROCESSING] ' . $filename;
        $data = $this->getTikaMetadata($filename);

        if (empty($data['tika'])) {
            echo ' -> error' . PHP_EOL;
            return;
        }
        $object = new \StdClass();
        foreach ($this->_fieldsMapping as $k1 => $k2) {
            if (isset($data['tika'][$k2])) {
                $object->$k1 = $data['tika'][$k2];
            } 
        }
        $object->filename = $data['filename'];
        $object->solrDocumentId = md5($data['filename']);
        $object->customTags = $customTags;
        $object->fingerprint = $this->generateFingerprint($data['filename']);

        if ($mineText) {
            $text = $this->getText($filename);
    /**
     * disambiguate -> disambiguate entities (i.e. Apple the company vs. apple the fruit). 0: disabled, 1: enabled (default)
     * linkedData -> include linked data on disambiguated entities. 0: disabled, 1: enabled (default) 
     * coreference -> resolve coreferences (i.e. the pronouns that correspond to named entities). 0: disabled, 1: enabled (default)
     * quotations -> extract quotations by entities. 0: disabled (default), 1: enabled.
     * sentiment -> analyze sentiment for each entity. 0: disabled (default), 1: enabled. Requires 1 additional API transction if enabled.
     * showSourceText -> 0: disabled (default), 1: enabled 
     * maxRetrieve -> the maximum number of entities to retrieve (default: 50)
     */
            $entities = $this->_tme->entities('text', $text, array('maxRetrieve' => 50));
            var_dump($entities); die();
        }

        $result = $this->_solr->indexObject($object);
        echo ($result) ? ' [OK]' : ' [FAILED]';
        echo PHP_EOL;

        return $result;
    }

    protected function hasBeenProcessed($filename) {
        $manifest = $this->getManifest($filename);
        $baseName = basename($filename);
        return isset($manifest[$baseName]) && $manifest[$baseName] == $this->generateFingerprint($filename); 
    }

    protected function getManifest($filename) {
        $folder = dirname($filename);
        $this->loadManifest($folder);
        return isset($this->_manifests[$folder]) ? $this->_manifests[$folder] : array();
    }

    protected function loadManifest($folder) {
        $manifestFilename = $this->_manifestFolder . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'manifest.php';
        if (!isset($this->_manifests[$folder]) && file_exists($manifestFilename)) {
            $this->_manifests[$folder] = require($manifestFilename);
        }

        return isset($this->_manifests[$folder]) ? $this->_manifests[$folder] : array();
    }

    protected function generateFingerprint($filename) {
        return md5_file($filename);
    }

    protected function getText($filename) {
        $metadata = null;
        $cmd = 'java -jar ' . $this->_tikaPath . ' -t "' . $filename . '"';
        exec($cmd, $output);
    
        return implode('', $output);
    }

    protected function getTikaMetadata($filename) {
        $metadata = null;
        $cmd = 'java -jar ' . $this->_tikaPath . ' --json "' . $filename . '"';
        exec($cmd, $output);
        $tika = implode('', $output);
        $tika = json_decode($tika, true);

        // create cache
        $result = array(
            'filename' => $filename, 
            'metadata' => $metadata,
            'tika' => $tika
        );

        return $result;
    }

    protected function mineText($filename, StdClass $object) {
        $metadata = $this->_tme->mineText($content);
    }
}
