<?php
/**
 * Filesystem Parser using tika/solr
 */
namespace Tikr\Parser;

abstract class Base {
    protected $_tikaPath = '/var/www/tika-app.jar';
    protected $_solr = null;
    protected $_tme = null;
    protected $_cacheFolder = null;
    protected $_fileFormats = array();
    protected $_manifestFolder = null;
    protected $_manifests = array();
    
    /** 
     * Mapping between tika and solr indexes
     */
    protected $_fieldsMapping = array(
        'applicationName' => 'Application-Name',
        'author' => 'Author',
        'characterCount' => 'Character Count',
        'comments' => 'Comments',
        'company' => 'Company',
        'contentLength' => 'Content-Length',
        'contentType' => 'Content-Type',
        'creationDate' => 'Creation-Date',
        'editTime' => 'Edit-Time',
        'keywords' => 'Keywords',
        'lastModified' => 'Last-Modified',
        'lastPrinted' => 'Last-Printed',
        'lastSaveDate' => 'Last-Save-Date',
        'pageCount' => 'Page-Count',
        'revisionNumber' => 'Revision-Number',
        'template' => 'Template',
        'wordCount' => 'Word-Count',
        'comment' => 'comment',
        'cpRevision' => 'cp:revision',
        'cpSubject' => 'cp:subject',
        'creator' => 'creator',
        'date' => 'date',
        'dcCreator' => 'dc:creator',
        'dcSubject' => 'dc:subject',
        'dcTitle' => 'dc:title',
        'dctermsCreated' => 'dcterms:created',
        'dctermsModified' => 'dcterms:modified',
        'extendedPropertiesApplication' => 'extended-properties:Application',
        'extendedPropertiesCompany' => 'extended-properties:Company',
        'extendedPropertiesTemplate' => 'extended-properties:Template',
        'metaAuthor' => 'meta:author',
        'metaCharacterCount' => 'meta:character-count',
        'metaCreationDate' => 'meta:creation-date',
        'metaKeyword' => 'meta:keyword',
        'metaLastAuthor' => 'meta:last-author',
        'metaPageCount' => 'meta:page-count',
        'metaPrintDate' => 'meta:print-date',
        'metaSaveDate' => 'meta:save-date',
        'metaWordCount' => 'meta:word-count',
        'modified' => 'modified',
        'resourceName' => 'resourceName',
        'subject' => 'subject',
        'title' => 'title',
        'wComments' => 'w:comments',
        'xmpTPgNPages' => 'xmpTPg:NPages',
    );

    public function __construct(\Tikr\Solr\Client $solr, array $fileFormats, $cacheFolder, $manifestFolder, $tikaPath, $tme) {
        $this->_solr = $solr;
        $this->_fileFormats = $fileFormats;
        $this->_cacheFolder = $cacheFolder;
        $this->_manifestFolder = $manifestFolder;
        $this->_tikaPath = $tikaPath;
        $this->_tme = $tme;
    }
}
