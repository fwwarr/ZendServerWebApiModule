<?php
namespace ZendServerWebApi\Model;

use Zend\Log\Logger;
use ZendServerWebApi\Model\Response\ApiResponse;
use ZendServerWebApi\Model\Request;
use ZendServerWebApi\Model\Exception\ApiException;
use ZendServerWebApi\Model\Http\Client;

/**
 * API Manager
 *
 * Class that manage Zend server API Request and Response.
 * Request can be send as an internal method of this class :
 * $this->getNotifications() will call "getNotications" API Method
 */
class ApiManager
{
    /**
     * Service manager
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * The expected output format
     * @var string
     */
    protected $outputFormat = "xml";

    /**
     * 
     * @var Logger
     */
    protected $log;

    /**
     * 
     * @var ApiKey
     */
    protected $defaultApiKey;

    /**
     * 
     * @var ZendServer
     */
    protected $targetZendServer;

    /**
     * 
     * @var Client
     */
    protected $zendServerClient;

    /**
     * 
     * @var array
     */
    protected $config;

    public function __construct(
        Logger $log,
        ApiKey $defaultApiKey,
        ZendServer $targetZendServer,
        Client $zendserverclient,
        $config
    ) {
        $this->log = $log;
        $this->defaultApiKey = $defaultApiKey;
        $this->targetZendServer = $targetZendServer;
        $this->zendServerClient = $zendserverclient;
        $this->config = $config;
    }

    /**
     * Returns list of supported API versions
     * @return array 
     *         - Zend Server Version 
     *         - PHP version
     *         - array List of supported versions. The top are the best match to use.
     */
    public function getSupportedVersions() 
    {
        $apiRequest = new Request($this->getTargetServer(), 'getSystemInfo', $this->getApiKey());
        $apiRequest->setMethod(Request::METHOD_GET);
        $apiRequest->setOutputType('xml');
        $apiRequest->prepareRequest();
        
        $httpResponse = $this->getZendServerClient()->send($apiRequest);
        $response = ApiResponse::factory($httpResponse);
        if($response->isError()) {
            throw new \Exception($response->getErrorMessage(), $response->getHttpResponse()->getStatusCode());
        }
        
        $zendServerVersion = sprintf($response->responseData->systemInfo->zendServerVersion);
        $phpVersion        = sprintf($response->responseData->systemInfo->phpVersion);
        $supportedVersions = explode("\n",str_replace('application/vnd.zend.serverapi;version=','',
                                                      sprintf($response->responseData->systemInfo->supportedApiVersions)
                                                      ));
        foreach ($supportedVersions as $key => $value) {
            $value=trim($value);
            if(!$value) {
                unset($supportedVersions[$key]);
                continue;
            }
            
            $supportedVersions[$key] = $value;
        }
        return array(
            $zendServerVersion,
            $phpVersion,
            array_reverse($supportedVersions)
        );
    }

    /**
     * Magical function to use API method has API Manager method.
     *
     * @param string $action            
     * @param array $args            
     * @return ApiResponse;
     */
    public function __call ($action, $args)
    { 
        $methodConf = 'get';
        $apiConfig  = $this->getApiConfig();
        $actionOptions = $apiConfig[$action]['options'];
        if (isset($actionOptions['defaults']['apiMethod'])) {
            $methodConf = $actionOptions['defaults']['apiMethod'];
        }
        $apiRequest = new Request($this->getTargetServer(), $action, $this->getApiKey());
        if (isset($args[0])) {
            if ($methodConf == 'post') {
                $files = array();
                if(isset($actionOptions['files'])) {
                    foreach($actionOptions['files'] as $fileParam) {
                        if (isset($args[0][$fileParam])) {
                            $filePath = $args[0][$fileParam];

                            if (!is_file($filePath)) {
                                throw new \Exception('File not readable or non existent: '.$filePath);
                            }

                            $files[$filePath] = array(
                                'formname' => $fileParam,
                                'filename' => basename($filePath),
                                'data'     => null,
                                'ctype'    => null,
                            );
                            unset($args[0][$fileParam]);
                        }
                    }
                    unset($args[0]['files']);
                }

                if(count($files)) {
                    $apiRequest->setFiles(new \Zend\Stdlib\Parameters($files));
                }
            }

            $apiRequest->setParameters($args[0]);
        }
        
        $outputFormat = $this->outputFormat; 
        if(isset($args[0]['zsoutput'])) {
            $outputFormat = $args[0]['zsoutput'];	
        }	
        $apiRequest->setOutputType($outputFormat);

        if ($methodConf == 'post') {
            $apiRequest->setMethod(Request::METHOD_POST);
        }
        $apiRequest->prepareRequest();
        $log = $this->log;
        $log->info($apiRequest->getUriString());
        $httpResponse = $this->getZendServerClient()->send($apiRequest);
        $response = ApiResponse::factory($httpResponse);
        
        if ($response->isError()) {
            $error = '';
            if (!getenv('RAW_ZS_OUTPUT')) {
                $error .= $response->getErrorMessage();
            }
            else {
                $writers = new \Zend\StdLib\SplPriorityQueue();;
                foreach ($log->getWriters() as $writer) {
                    $writer->setFormatter(new \Zend\Log\Formatter\Simple('%message%'));
                    $writers->insert($writer, 1);
                }
                $log->setWriters($writers);
            }
            $error .= $response->getHttpResponse()->getBody();
            $log->err($error);
            throw new ApiException($response);
        }

        return $response;
    }

    /**
     *
     * @return the $apiKey
     */
    public function getApiKey ()
    {
        return $this->defaultApiKey;
    }

    /**
     *
     * @return the $targetServer
     */
    public function getTargetServer ()
    {
        return $this->targetZendServer;
    }

    /**
     *
     * @return the $zendServerClient
     */
    public function getZendServerClient ()
    {
        return $this->zendserverclient;
    }

    /**
     *
     * @return the $apiConfig
     */
    public function getApiConfig ()
    {
        $apiConfig = $this->config;
        $apiConfig = $apiConfig['console']['router']['routes'];
        return $apiConfig;
    }
    
    public function setOutputFormat($outputFormat)
    {
        $allowedFormats = array('xml', 'json');
        $outputFormat = strtolower($outputFormat);
        if(!in_array($outputFormat, $allowedFormats)) {
            throw new \Exception('Invalid output format. Supported formats are:'.
                            implode(',', $allowedFormats));   
        }
        
        $this->outputFormat = $outputFormat;
    }
    
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }
}
