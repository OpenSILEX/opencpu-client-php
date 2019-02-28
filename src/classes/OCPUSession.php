<?php

//**********************************************************************************************
//                                        OpenCPUServer.php
//
// Author(s): Arnaud CHARLEROY
// OCPU for PHIS
// Copyright © - INRA - MISTEA - 2018
// Creation date: novembre 2015
// Contact:arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date: Feb. 08, 2018
// Subject: A class that represents an access to the openCPU server
//******************************************************************************

/**
 * @link http://www.inra.fr/
 * @copyright Copyright © INRA - 2018
 * @license https://www.gnu.org/licenses/agpl-3.0.fr.html AGPL-3.0
 */

namespace openSILEX\opencpuClientPHP\classes;

/**
 * Guzzle client for HTTP resquest
 */
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
// Local librairy
use openSILEX\opencpuClientPHP\classes\CallStatus;
use openSILEX\opencpuClientPHP\OpenCPUServer;

/**
 * OpenCPUServer class that represents a opencpu session
 * @author Arnaud Charleroy <arnaud.charleroy@inra.fr>
 * @since 1.0
 */
class OCPUSession {

    /**
     * application/json jsonlite::toJSON format namespace
     */
    const OPENCPU_SESSION_JSON_FORMAT = 'json';

    /**
     * text/plain base::print format namespace
     */
    const OPENCPU_SESSION_PRINT_FORMAT = 'print';

    /**
     * text/csv	utils::write.csv format namespace
     */
    const OPENCPU_SESSION_CSV_FORMAT = 'csv';

    /**
     * application/ndjson jsonlite::stream_out format namespace
     */
    const OPENCPU_SESSION_NDJSON_FORMAT = 'ndjson';

    /**
     * text/markdown pander::pander format namespace
     */
    const OPENCPU_SESSION_MD_FORMAT = 'md';

    /**
     * text/plain utils::write.table format namespace
     */
    const OPENCPU_SESSION_TAB_FORMAT = 'tab';

    /**
     * application/octet-stream base::save format namespace
     */
    const OPENCPU_SESSION_RDA_FORMAT = 'rda';

    /**
     * application/octet-stream base::saveRDS format namespace
     */
    const OPENCPU_SESSION_RDS_FORMAT = 'rds';

    /**
     * application/x-protobuf protolite::serialize_pb  format namespace
     */
    const OPENCPU_SESSION_PB_FORMAT = 'pb';

    /**
     * application/feather feather::write_feather format namespace
     */
    const OPENCPU_SESSION_FEATHER_FORMAT = 'feather';

    /**
     * image/png grDevices::png format namespace
     */
    const OPENCPU_SESSION_PNG_FORMAT = 'png';

    /**
     * application/pdf grDevices::pdf format namespace
     */
    const OPENCPU_SESSION_PDF_FORMAT = 'pdf';

    /**
     * image/svg+xml grDevices::svg format namespace
     */
    const OPENCPU_SESSION_SVG_FORMAT = 'svg';
    
    /**
     * raw text format namespace
     */
    const OPENCPU_SESSION_FILE_TEXT_FORMAT = 'textFile';
    
    /**
     * application/json json format namespace
     */
    const OPENCPU_SESSION_FILE_JSON_FORMAT = 'jsonFile';
    
    /**
     *
     * @var string OpenCPU Session ID
     */
    public $sessionId = null;

    /**
     *
     * @var array
     */
    private $sessionObjects = array();
    
     /**
     *
     * @var array
     */
    private $sessionFiles = array();

    /**
     *
     * @var array
     */
    private $sessionValues = null;

    /**
     *
     * @var boolean
     */
    private $exist = true;

    /**
     *
     * @var string
     */
    private $url = null;

    /**
     *
     * @var \openSILEX\opencpuClientPHP\classes\HttpErrorStatus represents openCPU call errors
     */
    private $callStatus = null;

    /**
     *
     * @var GuzzleHttp\Client session client
     */
    private $ocpuSessionclient = null;

    public function setClient($client) {
        $this->ocpuSessionclient = $client;
        $response = $this->openCPUSessionCall($this->url);
        if ($response === null) {
            $this->sessionId = null;
            $this->exist = false;
        } else {
            $body = (string) $response->getBody();
            $values = explode("\n", $body);
            $valuesClean = array_filter($values);
            $sessionObjects = preg_grep("/R\//", $valuesClean);
            $this->sessionObjects = array_values($sessionObjects);
            $sessionFiles = preg_grep("/files\//", $valuesClean);
            $this->sessionFiles = array_values($sessionFiles);
            $sessionValues = preg_grep("/(R\/|files\/)/", $valuesClean, 1);
            $this->sessionValues = array_values($sessionValues);
            
        }
    }

    /**
     * Construct a session from OpenCPU Server instance
     * @param string $ocpuSessionId OpenCPU Server session ID
     * @param \GuzzleHttp\Client $serverClient OpenCPU Server session ID
     */
    public function __construct($ocpuSessionId = null, $serverClient = null) {
        $this->sessionId = $ocpuSessionId;
        $this->ocpuSessionclient = $serverClient;
        if ($this->sessionId !== null) {
            // Complétez $url avec l'url cible
            $this->url = "tmp/" . $this->sessionId . "/";
            if ($serverClient != null) {
                $this->setClient($serverClient);
            } else {
                $this->exist = false;
            }
        } else {
            $this->url = null;
            $this->exist = false;
        }
    }

    /**
     * Return session value in text (limited to 1000 line as R console) or json format
     * @param string $format decide of the return   format OCPUSession::JSON_FORMAT,OCPUSession::PRINT_FORMAT
     * @param boolean $stats if set true return json format OCPUSession::JSON_FORMAT,OCPUSession::PRINT_FORMAT
     *
     * @return string|array Depends of user choice, by default it's a string
     */
    public function getVal($format = self::OPENCPU_SESSION_PRINT_FORMAT, $stats = false) {
        if ($this->exist) {
            $url = $this->url . "R/.val/" . $format;
            $requests_options = [];
            if ($stats === true) {
                $requests_options['on_stats'] = function (TransferStats $stats) {
                    echo $stats->getEffectiveUri() . PHP_EOL;
                    echo $stats->getTransferTime() . " seconds" . PHP_EOL;
                };
            }
            $response = $this->openCPUSessionCall($url, OpenCPUServer::OPENCPU_SERVER_GET_METHOD, $requests_options);
            // valid response
            if ($response !== null) {
                $body = $response->getBody();
                // Read the remaining contents of the body as a string
                try {
                    $contents = $body->getContents();
                    if ($contents === '') {
                        return null;
                    }
                    if ($format === self::OPENCPU_SESSION_JSON_FORMAT) {
                        return json_decode($contents, true); // json PHP array
                    } else {
                        return $contents; // string
                    }
                } catch (\RuntimeException $ex) {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     *
     * @return string le code lancé par l'utilisateur
     * @throws Exception problème d'accès au code source de la session
     */
    public function getSource() {
        if ($this->exist) {
            $url = $this->url . "source";
            $response = $this->openCPUSessionCall($url);
            // valid response
            if ($response !== null) {
                $body = (string) $response->getBody();
                return $body;
            }
        }
        return null;
    }

    /**
     * Permits to retreive R session object
     * @param string $objectName R object required
     * @param string $format decide of the return   format OCPUSession::JSON_FORMAT,OCPUSession::PRINT_FORMAT
     * @return mixed Dépends du format demandé et du serveur OpenCPU json true renvoie un tableau;json false renvoie une chaine de caractère
     */
    public function getObjects($objectName = null, $format = self::OPENCPU_SESSION_PRINT_FORMAT) {
        if ($objectName !== null) {
            if (in_array($objectName, $this->sessionObjects)) {
                $url = $this->url . "R/$objectName/$format";
                $response = $this->openCPUSessionCall($url);
                // valid response
                if ($response !== null) {
                    $body = $response->getBody();
                    // Read the remaining contents of the body as a string
                    try {
                        $contents = $body->getContents();
                        if ($contents === '') {
                            return null;
                        }
                        if ($format === self::OPENCPU_SESSION_JSON_FORMAT) {
                            return json_decode($contents, true); // json PHP array
                        } else {
                            return $contents; // string
                        }
                    } catch (\RuntimeException $ex) {
                        return null;
                    }
                }
            }
        } else {
            return $this->getVal($format);
        }
        return null;
    }

    public function getFileData($fileName = null, $format = self::OPENCPU_SESSION_FILE_TEXT_FORMAT) {
        if ($fileName !== null) {
            if (in_array("files/$fileName", $this->sessionFiles)) {
                $url = $this->url . "files/$fileName";
                $response = $this->openCPUSessionCall($url);
                // valid response
                if ($response !== null) {
                    $body = $response->getBody();
//                    
                    // Read the remaining contents of the body as a string
                    try {
                        $contents = $body->getContents();
                        if ($contents === '') {
                            return null;
                        }
                        if ($format === self::OPENCPU_SESSION_FILE_JSON_FORMAT) {
                            return json_decode($contents, true); // json PHP array
                        } else {
                            return $contents; // string
                        }
                    } catch (\RuntimeException $ex) {
                        return null;
                    }
                }
            }
        } 
        return null;
    }
    
    
    public function getBaseUri() {
        return $this->ocpuSessionclient->getConfig("base_uri");
    }
    /**
     * 
     * @param type $fileName
     * @return string
     */
    public function getExistingFileUrl($fileName = null) {
        if ($fileName !== null) {
            if (in_array("files/$fileName", $this->sessionFiles)) {
                $url = $this->getBaseUri() . $this->url . "files/$fileName";
               return $url;
            }
        } 
        return null;
    }
    
    public function getUrl() {
        return $this->url;
    }

    /**
     *
     * @param string $openCPUUrlRessource opencpu server url
     * @param string $httpMethod GET, POST method
     * @param string $requests_options additionnal parameters
     * @return \GuzzleHttp\Psr7\Response|null response from the server
     */
    public function openCPUSessionCall($openCPUUrlRessource, $httpMethod = OpenCPUServer::OPENCPU_SERVER_GET_METHOD, $requests_options = []) {
        try {
            // call session ressource
            $response = $this->ocpuSessionclient->request($httpMethod, $openCPUUrlRessource, $requests_options);
            if ($response->getStatusCode() > 400) {
                $this->callStatus = new CallStatus($response->getReasonPhrase(), $response->getStatusCode());
                return null;
            }
            return $response;
        } catch (RequestException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            $statusCode = null;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->sessionId = null;
            $this->exist = false;
            $this->callStatus = new CallStatus($errorMessage, $statusCode, $e);
            // ClientException is thrown for 400 level errors
        } catch (ClientException $e) {
            $statusCode = 400;
            $errorMessage = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->sessionId = null;
            $this->exist = false;
            $this->callStatus = new CallStatus($errorMessage, $statusCode, $e);
            // is thrown for 500 level errors
        } catch (ServerException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            $statusCode = 500;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->sessionId = null;
            $this->exist = false;
            $this->callStatus = new CallStatus($errorMessage, $statusCode, $e);
        } catch (BadResponseException $e) {
            if ($e->hasResponse()) {
                $errorMessage = '--' . Psr7\str($e->getResponse());
                $statusCode = $e->getResponse()->getStatusCode();
            } else {
                $errorMessage = $e->getMessage();
                $statusCode = null;
            }
            $this->sessionId = null;
            $this->exist = false;
            $this->callStatus = new CallStatus($errorMessage, $statusCode, $e);
        }
        return null;
    }
}
