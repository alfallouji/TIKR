<?php
/**
 * Filesystem Parser using tika/solr
 */
namespace Tikr\Parser;

class Filesystem extends Base {
    public function generateManifest($folder, $allowAll = false) { 

        $result = array();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($iterator as $info) {
            if (!$allowAll && !in_array(strtolower($info->getExtension()), $this->_fileFormats)) {
                echo '[SKIP] ' . $info->__toString(). PHP_EOL;
            } elseif ($info->isFile ()) {
                $baseName = basename($info->__toString());
                $dirName = realpath(dirname($info->__toString()));
                $result[$dirName][$baseName] = md5_file($info->__toString());
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

    public function processFolder($folder, $allowAll = false, $customTags = array()) {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        foreach ($iterator as $info) {
            if (!$allowAll && !in_array(strtolower($info->getExtension()), $this->_fileFormats)) {
                echo '[SKIP] ' . $info->__toString(). PHP_EOL;
            } elseif ($info->isFile ()) {
                $this->processFile($info->__toString(), $customTags);
            } 
        }
    }

    public function processFile($filename, $customTags = array()) {
        echo '[PROCESSING] ' . $filename;
        $data = $this->callTika($filename);

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
        $object->fingerprint = md5_file($data['filename']);
        $result = $this->_solr->indexObject($object);
        echo ($result) ? ' [OK]' : ' [FAILED]';
        echo PHP_EOL;
    }

    protected function callTika($filename) {
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
}
