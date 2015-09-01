<?php
/**
 * File:        Client.php
 * @author      Al-Fallouji Bashar
 */
namespace tikr\solr;

/**
 * A lightweight class that allows to index, deindex and search via SolR
 */
class Client
{
    /**
     * host 
     * 
     * @var string Hostname of Solr (e.g. http://localhost:8080/solr)
     */
    public $host;

    /**
     * Constructor
     * 
     * @param string $host Hostname to Solr
     */
    public function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * Index a object in Solr
     * 
     * @param object $object 
     *
     * @return string Solr response
     */
    public function indexObject($object)
    {
        $xml  = '<add commitWithin="1000"><doc>';

        // Object must implement a method named getIndexableProperties
        foreach($object as $key => $value)
        {
            if (is_array($value) || is_object($value)) 
            {
                foreach ($value as $subKey => $subValue) 
                {
                    $xml .= '<field name="' . $key . '"><![CDATA[' .  $subValue . ']]></field>';
                }
            } 
            else 
            {
                $xml .= '<field name="' . $key . '"><![CDATA[' .  $value . ']]></field>';
            }
        }

        $xml .= '</doc></add>';

      	return $this->index($xml);
    }

    /**
     * Remove a object from Solr
     * 
     * @param string $className Classname of object to deindex 
     * @param int $id Id of the object to deindex
     * @return boolean True on success, False on failure
     */
    public function deindexObject($className, $id)
    {
        // A uniqId is built based on the className and the id
        // E.g. article_12, photo_3, etc...
      	return $this->deindex($className . '_' . $id);
    }

    /**
     * Index multiple objects at once 
     * 
     * @param array $objects Array of objects 
     *
     * @return boolean True on success, else false
     */
    public function indexObjects(array $objects)
    {
        $xml  = '<add>';

        foreach($objects as $object)
        {
            $xml .= '<doc>';
            $xml .= '<field name="id"><![CDATA['. $object->id .']]></field>';

            foreach($object->getIndexableProperties() as $key => $value)
            {
                $xml .= '<field name="' . $key . '"><![CDATA[' .  $value . ']]></field>';                
            }

            $xml .= '</doc>';

            $response[] = $object->id;
        }

        $xml .= '</add>';

        if($this->index($xml))
        {
            return $response;
        }

        return false;
    }

    /**
     * Index a document in Solr 
     * 
     * @param string $xml String of the xml document to index
     *
     * @return string Solr response
     */
    public function index($xml)
    {
        $url = $this->host . DIRECTORY_SEPARATOR . 'update';
        $params = array('stream.body' => $xml, 'allowDups' => "false", 'commit' => true);

        return $this->sendPostData($url, $params);
    }

    /**
     * Remove a document from Solr
     * 
     * @param int $id Id of the document to remove
     * @param string $qry Lucene based syntax query
     *
     * @return string Solr response
     */
    public function deindex($id = null, $qry = null)
    {
        $url = $this->host . DIRECTORY_SEPARATOR . 'update';
        $streamBody = '<delete>';
        if(null !== $id)
        {
            $streamBody .= '<id>'. $id . '</id>';
        }

        if(null !== $qry)
        {
            $streamBody .= '<query>' . $qry . '</query>';
        }

        $streamBody .= '</delete>';
        $params = array('stream.body' => $streamBody);
       
        if ($this->sendPostData($url, $params)) 
        {
            return $this->commit();
        }

        return false;
    }

    /**
     * Performs a commit on Solr
     * 
     * @return string Solr response
     */
    public function commit()
    {
        $url = $this->host . DIRECTORY_SEPARATOR . 'update';
        $params = array('stream.body' => '<commit/>');

        $result = $this->sendPostData($url, $params);

        return $result;
    }

    /** 
     * Count document of a certain class
     * 
     * @param string $className
     *
     * @return int Count
     */
    public function classCount($className)
    {
        $response = $this->search('type:' . $className, 0, 0);
        return $response['response']['numFound'];
    }

    /**
     * Send data to a ressource via a GET request 
     * 
     * @param string $url Url to send data to
     * @param mixed $params Array of parameters to be sent by POST
     *
     * @return string Result of the GET request 
     */
    private function sendGetData($url, $params, $extraParams = null)
    {
        $qry = $url . DIRECTORY_SEPARATOR . '?' . http_build_query($params, null, '&') . $extraParams;
       
        // On production, put error_level to ~ E_WARNING, so it can intercept warning message in case of Solr is down 
        // file_get_contents will return false if Solr is down
        return file_get_contents($qry);
    }

    /**
     * Invoke an URL and returns the result 
     * 
     * @param string $url Url to query
     * @param array $params Array of parameters to be sent by POST
     *
     * @return string Result of the query
     */
    private function sendPostData($url, array $params = array())
    {
        $contextOpts = array('http' => array('method' => 'POST'));

        $stream = stream_context_create($contextOpts);            
        stream_context_set_option($stream, 'http', 'header', 'Content-Type: text/xml; charset=UTF-8');
        if(isset($params['stream.body']) && $params['stream.body'] !== null) 
        {
            stream_context_set_option($stream, 'http', 'content', $params['stream.body']);
        }

        // On production, put error_level to ~ E_WARNING, so it can intercept warning message in case of Solr is down 
        // file_get_contents will return false if Solr is down
        $result =  file_get_contents($url, false, $stream);

    	return $result;
    }

    /**
     * Performs a search on Solr 
     * 
     * @param string $qry Lucene based syntax query to be sent to Solr
     * @param int $start Start at that specific row
     * @param int $rows Number of rows to fetch
     * @param string $indent 
     * @param string $version 
     *
     * @return array Returns an array containing the Solr response
     */
    public function search($qry, $start = 0, $rows = 10, $sort = '', $indent = 'on', $hl = null, $facets = null, $fq = null, $version = '2.2')
    {
        $url = $this->host . DIRECTORY_SEPARATOR . 'select';
        $params['q'] = $qry;
        $params['start'] = $start;
        $params['rows'] = $rows;
        $params['indent'] = $indent;
        $params['version'] = $version;
        $params['sort'] = $sort;
        $params['fq'] = null;

        if ($fq) 
        {
            $params['fq'] .= $fq;
        }

        $params['fl'] = '*,score';

        $params = array_filter($params);

        if(is_array($hl))
        {
            foreach($hl as $key => $fragSize)
            {
                $params['f.'.$key.'.hl.fragsize'] = $fragSize;
            }
            $params['hl'] = 'true';
            $params['hl.fl'] = implode(',', array_keys($hl));
            $params['hl.tag.pre'] = '<span class="hl">'; 
            $params['hl.tag.post'] = '</span>';
        }

        // Computing parameters for facetted search
        $extraParams = null;
        if(is_array($facets))
        {
            $params['facet'] = 'true';

            // Retrieve number of facet items wanted
            $params['facet.limit'] = isset($facets['params']['limit']) ? $facets['params']['limit'] : 10;        

            // Retrieve sorting 
            $params['facet.sort'] = isset($facets['params']['sort']) ? $facets['params']['sort'] : true;

            // Retrieve minimum counts for facet fields that should be included in the response
            $params['facet.mincount'] = isset($facets['params']['mincount']) ? $facets['params']['mincount'] : 1;

            foreach($facets['fields'] as $facet)
            {
                $extraParams .= '&facet.field=' . $facet['field'];
                if (isset($facet['minCount'])) 
                {
                    $extraParams .= '&f.' . $facet['field'] . '.facet.mincount=' . $facet['minCount'];
                }
            }

            $facetDate = isset($facets['date']) ? $facets['date'] : array();
            if (!empty($facetDate)) 
            {
                foreach ($facetDate['fields'] as $date) 
                {
                    $extraParams .= '&facet.date=' . $date;
                }
                $extraParams .= '&facet.date.start=' . $facetDate['start'];
                $extraParams .= '&facet.date.end=' . $facetDate['end'];
                $extraParams .= '&facet.date.gap=' . $facetDate['gap'];
            }

            $facetRange = isset($facets['range']) ? $facets['range'] : array();
            if (!empty($facetRange)) 
            {
                foreach ($facetRange['fields'] as $range) 
                {
                    $extraParams .= '&facet.range=' . $range;
                }
                $extraParams .= '&facet.range.start=' . $facetRange['start'];
                $extraParams .= '&facet.range.end=' . $facetRange['end'];
                $extraParams .= '&facet.range.gap=' . $facetRange['gap'];
            }

            if (!empty($facets['stats'])) 
            {
                $extraParams .= '&stats=true&stats.field=' . $facets['stats']['field'];
            }
        }

        // With this parameter, Solr will return a PHP serialized array
        $params['wt'] = 'phps';

        $res = $this->sendGetData($url, $params, $extraParams);

        // If $res is false, then Solr didn't response 
        if(false === $res)
        {
            $this->searchResult['numFound'] = 0;
            $this->searchResult['docs'] = array();            
            return false;
        }

        return unserialize($res);
    }
}

