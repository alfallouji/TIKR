<?php
namespace Tikr\Tika\Client;

class Rest
{
    protected $_tikaUrl = null;
    protected $_tikaPath = null;

    public function __construct($tikaUrl)
    {
        $this->_tikaUrl = $tikaUrl;
        $this->_tikaPath = '/var/www/apps/tika/tika-app.jar';
    }

    public function getMetadata($filename) 
    {
        $metadata = null;
        $cmd = 'curl -H "Accept: application/json" -T "' . $filename . '" ' . $this->_tikaUrl . ' -s';
        exec($cmd, $output);
        $tika = implode('', $output);
        $tika = json_decode($tika, true);

        // create cache
        $result = array(
            'filename' => $filename, 
            'metadata' => $tika
        );

        return $result;
    }

    public function getText($filename) {
        // @todo
        $metadata = null;
        $cmd = 'java -jar ' . $this->_tikaPath . ' -t "' . $filename . '"';
        exec($cmd, $output);
    
        return implode('', $output);
    }
}
