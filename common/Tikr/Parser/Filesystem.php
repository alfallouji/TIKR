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
     * Process folder
     * 
     * @param string $folder Folder to process
     * @param boolean $allowAll Allow all formats
     * @param array $customTags Custom tags to use when indexing to Solr
     * @param boolean $mineText Perform text mining or not
     * @param boolean $ignoreManifest Ignore manifest or not (do full re-index)
     *
     * @return void
     */
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
                    $result[$dirName][$baseName] = $this->generateWeakFingerprint($filename);
                    ++$notChanged;
                } elseif ($info->isFile()) { 
                    if ($this->processFile($filename, $customTags, $mineText)) {
                        $dirName = dirname($filename);
                        $baseName = basename($filename);
                        $result[$dirName][$baseName] = $this->generateWeakFingerprint($filename);
                        ++$processed;
                        $this->generateManifest(array($dirName => array_merge(array($filename), $this->_manifests[$dirName])));
                    } else { 
                        echo '[ERROR] ' . $filename . PHP_EOL;
                        $this->removeCacheFile($filename);
                        ++$error;
                    }
                }
            } catch (\Exception $e) {
                $this->removeCacheFile($filename);
                echo '[EXCEPTION] ' . $e->getMessage() . PHP_EOL;
            }
        }

        $deindexed += $this->removeDocuments($result, $filename);

        echo PHP_EOL . 'Summary' . PHP_EOL; 
        echo str_pad('', 90, '-');
        echo PHP_EOL . 'Error : ' . $error . ' files(s)' . PHP_EOL;
        echo 'Format Ignored : ' . $ignored . ' file(s)' . PHP_EOL;
        echo 'Not changed : ' . $notChanged . ' file(s)' . PHP_EOL;
        echo 'De-indexed : ' . $deindexed . ' file(s)' . PHP_EOL;
        echo 'Added/Updated : ' . $processed . ' file(s)' . PHP_EOL;
    }

    /**
     * Process a file
     * 
     * @param string $filename File to process
     * @param array $customTags Custom tags to use when indexing to Solr
     * @param boolean $mineText Perform text mining or not
     *
     * @return void
     */
    public function processFile($filename, $customTags = array(), $mineText = false) {
        $start = microtime(true);
        echo '[PROCESSING] ' . $filename;
        $tmpFilename = $this->createCacheFile($filename);
        echo ' .';
        $data = $this->_metadataExtractor->getMetadata($tmpFilename);
        echo empty($data['metadata']) ? 'X' : '.';

        $object = new \StdClass();
        foreach ($this->_fieldsMapping as $k1 => $k2) {
            if (isset($data['metadata'][$k2])) {
                if (is_array($data['metadata'][$k2])) {
                    $object->$k1 = array_unique($data['metadata'][$k2]);
                } else {
                    $object->$k1 = $data['metadata'][$k2];
                }
            } 
        }

        $object->filename = $filename;
        $object->solrDocumentId = md5($filename);
        $object->customTags = $customTags;
        $object->fingerprint = $this->generateStrongFingerprint($tmpFilename);
        $object->contentLength = filesize($tmpFilename);

        if ($mineText) {
            $text = $this->getText($tmpFilename);
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
            // @todo : complete this 
        }

        $result = $this->_solr->indexObject($object);
        echo '. [' . number_format($object->contentLength / (1024 * 1024), 2) . ' | ';
        echo number_format(microtime(true) - $start, 2) . ' | '; 
        echo number_format(memory_get_peak_usage() / (1024 * 1024), 0) . 'ko';
        echo '] ';
        echo ($result) ? ' [OK]' : ' [FAILED]';
        echo PHP_EOL;
       
        unlink($tmpFilename);
        
        return $result;
    }

    /**
     * Generate manifest file
     * 
     * @param array $result Contains the folders and files for the manifest
     * 
     * @return void
     */
    public function generateManifest($result) {
        foreach ($result as $subdir => $files) {
            $folder = $this->_manifestFolder . $subdir;
            if (!is_dir($folder)) {
                mkdir($folder, 0775, true);
            }
            $content = '<?php ' . PHP_EOL . ' // Generated at ' . date('Y-m-d H:i:s') . PHP_EOL . 'return ' . var_export($files, true) . ';';
            file_put_contents($folder . DIRECTORY_SEPARATOR . 'manifest.php', $content);
            echo 'Manifest generated/updated : ' . $folder . DIRECTORY_SEPARATOR . 'manifest.php' . PHP_EOL;
        }
    }

    /**
     * Identify and remove deleted files
     * 
     * @param array $result Contains the folders and files that got processed
     * @param string $filename Name of the file
     * 
     * @return int Number of files that got removed
     */
    public function removeDocuments($result, $filename) {
        $deindexed = 0;
        // Remove file that are not in the manifest file
        foreach ($result as $subdir => $currentFiles) {
            $previousFiles = $this->getManifest($subdir);
            $diffFiles = array_diff(array_keys($previousFiles), array_keys($currentFiles));
            foreach ($diffFiles as $file) {
                $filename = $subdir . DIRECTORY_SEPARATOR . $file;
                $md5 = md5($filename);
                echo '[DE-INDEX] ' . $filename . ' (' . $md5 . ')' . PHP_EOL;
                $this->_solr->deindex($md5);
                ++$deindexed;
            }
        }

        return $deindexed;
    }

    /**
     * Has the file already been processed (look at manifest)
     * 
     * @param string $filenae Name of the file
     * 
     * @return boolean True if that's the case, false otherwise
     */
    protected function hasBeenProcessed($filename) {
        $manifest = $this->getManifest(dirname($filename));
        $baseName = basename($filename);
 
        return isset($manifest[$baseName]) && $manifest[$baseName] == $this->generateWeakFingerprint($filename); 
    }

    /**
     * Get manifest for a particular folder
     * 
     * @param string $folder Name of the folder
     * 
     * @return array Assoc of file => weak fingerprint
     */
    protected function getManifest($folder) {
        $manifestFilename = $this->_manifestFolder . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . 'manifest.php';
        if (!array_key_exists($folder, $this->_manifests)) {
            if (file_exists($manifestFilename)) {
                $this->_manifests[$folder] = require($manifestFilename);
            } else { 
                $this->_manifests[$folder] = array();
            }
        }

        return $this->_manifests[$folder];
    }

    /**
     * Generates a weak fingerprint based on some attributes of the file (does not require to read the whole file)
     * 
     * @param string $filename File name
     * 
     * @return string Weak fingerprint
     */
    protected function generateWeakFingerprint($filename) {
        $parts = array(filesize($filename), filectime($filename), fileowner($filename));

        return sha1(implode('_', $parts));
    }
    /**
     * Generate a strong fingerpring based on the content of the file
     * 
     * @param string $filename File name
     * 
     * @return string Strong fingerprint
     */
    protected function generateStrongFingerprint($filename) {
        return sha1_file($filename);
    }
    /**
     * Perform text mining
     * 
     * @param string $filename File name
     * @param \StdClass $object Object to update
     *
     * @return boolean True upon success, false otherwise
     */
    protected function mineText($filename, \StdClass $object) {
        $metadata = $this->_tme->mineText($content);
        // @todo
    }

    /**
     * Get cache filename
     * 
     * @param string $filename File name
     * 
     * @return string Cache filename
     */
    protected function getCacheFilename($filename) {
        return sys_get_temp_dir() .  DIRECTORY_SEPARATOR . 'cache.' . md5($filename);
    }

    /**
     * Create cache file
     * 
     * @param string $filename File name
     * 
     * @return string Temporary filename
     */
    protected function createCacheFile($filename) {
        $tmpFilename = $this->getCacheFilename($filename);
        file_put_contents($tmpFilename, file_get_contents($filename));

        return $tmpFilename;
    }

    /**
     * Remove cache file
     * 
     * @param string $filename File name
     * 
     * @return boolean True upon success, false otherwise
     */
    protected function removeCacheFile($filename) {
        $tmpFilename = $this->getCacheFilename($filename);
       
        return file_exists($tmpFilename) && unlink($tmpFilename);
    }
}
