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
//***********************************************************************************************

/**
 * @link http://www.inra.fr/
 * @copyright Copyright © INRA - 2018
 * @license https://www.gnu.org/licenses/agpl-3.0.fr.html AGPL-3.0
 */

namespace openSILEX\opencpuClientPHP;

/**
 * Guzzle client for HTTP resquest
 */
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
/**
 * Use for promise response
 */
use Psr\Http\Message\ResponseInterface;
/**
 * Get execution time and other informations
 */
use GuzzleHttp\TransferStats;
/**
 * Personal librairies
 */
use openSILEX\opencpuClientPHP\classes\CallStatus;
use openSILEX\opencpuClientPHP\classes\OCPUSession;
use openSILEX\opencpuClientPHP\classes\ConstantClassDefinition;

/**
 * OpenCPUServer class that represents an access to the openCPU server
 * @author Arnaud Charleroy <arnaud.charleroy@inra.fr>
 * @since 1.0
 */
class OpenCPUServer {

    /**
     *
     * @var string openCPU Server url
     */
    private $openCPUWebServerUrl = null;

    /**
     *
     * @var GuzzleHttp\Client openCPU Server client
     */
    private $openCPUWebServerClient = null;

    /**
     *
     * @var boolean connection validity
     */
    private $connectionState = false;

    /**
     *
     * @var \openSILEX\opencpuClientPHP\classes\CallStatus represents openCPU  server connection errors
     */
    private $serverCallStatus = null;

    /**
     *
     * @var boolean if true print each request call time
     */
    public static $ENABLE_CALL_STATS = false;

    /**
     * Url parameter must be set
     * @param string $OCPUWebServerUrl openCPU Server client
     * @throws Exception can't connect to the server
     */
    public function __construct($OCPUWebServerUrl) {
        if (!isset($OCPUWebServerUrl)) {
            throw new \Exception('Server url not set');
        }
        $this->openCPUWebServerUrl = $OCPUWebServerUrl;
        // try to connect the openCPU server
        $this->setOCPUWebServerUrl();
    }

    /**
     * Try the first time to connect to OpenCPU Server
     */
    protected function setOCPUWebServerUrl() {
        try {
            $openCPUWebServerClient = new Client([
                // Base URI is used with relative requests
                'base_uri' => $this->openCPUWebServerUrl,
                // You can set any number of default request options.
                'timeout' => 60,
            ]);
            // Send a request to OpenCPU Server, example : http://cloud.opencpu.org/ocpu/
            $response = $openCPUWebServerClient->request(ConstantClassDefinition::OPENCPU_SERVER_GET_METHOD, '');
            $this->connectionState = true;
            $this->serverCallStatus = new CallStatus($response->getReasonPhrase(), $response->getStatusCode());
            $this->openCPUWebServerClient = $openCPUWebServerClient;
            // RequestException is thrown the event of a networking error (connection timeout, DNS errors, etc.)
        } catch (RequestException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $e->getResponse()->getStatusCode(), $e);
            // ClientException is thrown for 400 level errors
        } catch (ClientException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $e->getResponse()->getStatusCode(), $e);
            // is thrown for 500 level errors
        } catch (ServerException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $e->getResponse()->getStatusCode(), $e);
        }
    }

    /**
     * Get the server connection status
     * @param boolean $msg if true  retreive http status
     * @return boolean|string return a http message status of the connexion or a boolean which show the status connexion
     */
    public function status($msg = true) {
        if ($msg) {
            return $this->serverCallStatus->getMessage();
        } else {
            return $this->connectionState;
        }
    }

    public function getOpenCPUWebServerUrl() {
        return $this->openCPUWebServerUrl;
    }

    /**
     *
     * @return GuzzleHttp\Client connection client
     */
    public function getOpenCPUWebServerClient() {
        return $this->openCPUWebServerClient;
    }

    /**
     *
     * @return \openSILEX\opencpuClientPHP\classes\CallStatus status class which represents serverlast call status
     */
    public function getServerCallStatus() {
        return $this->serverCallStatus;
    }

    /**
     * Execute an asynchronous call to a R function
     * @param string $library the library which contains the function
     * @param string $function a R function, for example "rnom"
     * @param array $parameters if the function takes x parameter, you can write it like ["x" => 50]
     * @param \Closure $promise defines a \Closure object which can be chained after retreiving of the session
     *
     * @return \GuzzleHttp\Promise\Promise represents a way to call an opencpu session
     */
    public function makeAsyncRCall($library, $function, $parameters = [], $promise = null) {
        $url = "library/" . $library . "/R/" . $function;
        return $this->asyncOpenCPUServerCall($url, ConstantClassDefinition::OPENCPU_SERVER_POST_METHOD, $parameters, $promise);
    }

    /**
     * Execute a synchronous call to a R function
     * @param string $library the library which contains the function
     * @param string $function a R function, for example "rnom"
     * @param array $parameters if the function takes x parameter, you can write it like ["x" => 50]
     *
     * @return OCPUSession represents a way to call an opencpu session
     */
    public function makeRCall($library, $function, $parameters = []) {
        $url = "library/" . $library . "/R/" . $function;
        return $this->openCPUServerCall($url, ConstantClassDefinition::OPENCPU_SERVER_POST_METHOD, $parameters);
    }

    /**
     * Execute user R code
     * @param string $snippet R code text
     *
     * @return OCPUSession|null represents a way to call an opencpu session
     */
    public function makeRSnippetCall($snippet = null) {
        if ($snippet !== null) {
            $parameters = ["x" => $snippet];
            $url = "library/base/R/identity";
            return $this->openCPUServerCall($url, ConstantClassDefinition::OPENCPU_SERVER_POST_METHOD, $parameters);
        }
        return null;
    }

    /**
     * Retreive a OpenCPU session by this ID
     * @param string $sessionId an OpenCPU session ID , exemple : "x0dfc7f30fc"
     * @return OCPUSession an OCPUSession instance
     */
    public function getSessionById($sessionId) {
        $sessionServerClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->openCPUWebServerUrl,
            // You can set any number of default request options.
            'timeout' => 120,
        ]);
        $newSession = new OCPUSession($sessionId, $sessionServerClient);
        return $newSession;
    }

    /**
     * Execute a synchronous call to a R function
     * @param string $openCPUUrlRessource end point of the ressource
     * @param string $httpMethod HTTP Method example : POST ,GET
     * @param array $parameters if the function takes x parameter, you can write it like ["x" => 50]
     *
     * @return OCPUSession|null represents a way to call an opencpu session
     */
    protected function openCPUServerCall($openCPUUrlRessource, $httpMethod = ConstantClassDefinition::OPENCPU_SERVER_GET_METHOD, $parameters = []) {
        // request options
        $requests_options = [
            'form_params' => $parameters,
        ];
        // get transfer time, if call statistics are enable OpenCPUServer::$ENABLE_CALL_STATS = true
        if (self::$ENABLE_CALL_STATS) {
            $requests_options['on_stats'] = function (TransferStats $stats) {
                echo $stats->getEffectiveUri() . PHP_EOL;
                echo $stats->getTransferTime() . " seconds" . PHP_EOL;
            };
        }
        try {
            // call R function
            $response = $this->openCPUWebServerClient->request($httpMethod, $openCPUUrlRessource, $requests_options);
            // If call succeed
            $body = $response->getBody();
            // retrevies body as a string
            $stringBody = (string) $body;
            $sessionValuesResults = explode("\n", $stringBody);
            // retreives the differents OpenCPU api ressources
            $sessionValuesResultsClean = array_filter($sessionValuesResults);
            preg_match("/^\/ocpu\/tmp\/([A-Za-z0-9]+)\/.*/", $sessionValuesResultsClean[0], $sessionIdMatch);
            // retreives OpenCPU session identifier
            $sessionId = $sessionIdMatch[1];
            $this->callStatus = new CallStatus($response->getReasonPhrase(), $response->getStatusCode());
            $sessionServerClient = new Client([
                // Base URI is used with relative requests
                'base_uri' => $this->openCPUWebServerUrl,
                // You can set any number of default request options.
                'timeout' => 120,
            ]);
            $newSession = new OCPUSession($sessionId, $sessionServerClient);
            return $newSession;
        } catch (RequestException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            $statusCode = null;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
            // ClientException is thrown for 400 level errors
        } catch (ClientException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            $statusCode = 400;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
            // is thrown for 500 level errors
        } catch (ServerException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            $statusCode = 500;
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $errorMessage .= '--' . Psr7\str($e->getResponse());
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
        } catch (BadResponseException $e) {
            $errorMessage = Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                $errorMessage .= '--' . Psr7\str($e->getResponse());
                $statusCode = $e->getResponse()->getStatusCode();
            } else {
                $errorMessage = $e->getMessage();
                $statusCode = null;
            }
            $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
            return null;
        }
        return null;
    }

    /**
     * Execute an asynchronous call to a R function
     * @param string $openCPUUrlRessource end point of the ressource
     * @param string $httpMethod HTTP Method example : POST ,GET
     * @param array $parameters if the function takes x parameter, you can write it like ["x" => 50]
     * @param \Closure $promiseFunction defines a \Closure object which can be chained after retreiving of the session
     *
     * @return \GuzzleHttp\Promise\Promise|null return a OCPUSession or null if a problem has occured
     */
    protected function asyncOpenCPUServerCall($openCPUUrlRessource, $httpMethod = ConstantClassDefinition::OPENCPU_SERVER_GET_METHOD, $parameters = [], $promiseFunction = null) {
        // request options
        $requests_options = [
            'form_params' => $parameters,
        ];
        // if call statistics are enable OpenCPUServer::$ENABLE_CALL_STATS = true
        if (self::$ENABLE_CALL_STATS) {
            $requests_options['on_stats'] = function (TransferStats $stats) {
                echo $stats->getEffectiveUri() . PHP_EOL;
                echo $stats->getTransferTime() . " seconds" . PHP_EOL;
            };
        }
        // retreive promise object
        $promiseResponse = $this->openCPUWebServerClient->requestAsync($httpMethod, $openCPUUrlRessource, $requests_options);

        // If no error occured
        $success = function (ResponseInterface $res) {
            $body = $res->getBody();
            // retrevies body as a string
            $stringBody = (string) $body;
            $sessionValuesResults = explode("\n", $stringBody);
            // retreives the differents OpenCPU api ressources
            $sessionValuesResultsClean = array_filter($sessionValuesResults);
            preg_match("/^\/ocpu\/tmp\/([A-Za-z0-9]+)\/.*/", $sessionValuesResultsClean[0], $sessionIdMatch);
            // retreives OpenCPU session identifier
            $sessionId = $sessionIdMatch[1];
            $this->callStatus = new CallStatus($res->getReasonPhrase(), $res->getStatusCode());
            $sessionServerClient = new Client([
                // Base URI is used with relative requests
                'base_uri' => $this->openCPUWebServerUrl,
                // You can set any number of default request options.
                'timeout' => 120,
            ]);
            $newSession = new OCPUSession($sessionId, $sessionServerClient);
            return $newSession;
            // If an error occured during the request execution
        };

        $error = function (\Exception $e) {
            $this->serverCallStatus = new CallStatus($e->getMessage(), null, $e);
            if ($e instanceof RequestException) {
                $statusCode = null;
                $errorMessage = Psr7\str($e->getRequest());
                if ($e->hasResponse()) {
                    $errorMessage .= '--' . Psr7\str($e->getResponse());
                    $statusCode = $e->getResponse()->getStatusCode();
                }
                $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
                // ClientException is thrown for 400 level errors
            }
            if ($e instanceof ClientException) {
                $errorMessage = Psr7\str($e->getRequest());
                $statusCode = 400;
                if ($e->hasResponse()) {
                    $errorMessage .= '--' . Psr7\str($e->getResponse());
                    $statusCode = $e->getResponse()->getStatusCode();
                }
                $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
                // is thrown for 500 level errors
            }
            if ($e instanceof ServerException) {
                $errorMessage = Psr7\str($e->getRequest());
                $statusCode = 500;
                if ($e->hasResponse()) {
                    $errorMessage .= '--' . Psr7\str($e->getResponse());
                    $statusCode = $e->getResponse()->getStatusCode();
                }
                $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
            }
            if ($e instanceof BadResponseException) {
                if ($e->hasResponse()) {
                    $errorMessage = '--' . Psr7\str($e->getResponse());
                    $statusCode = $e->getResponse()->getStatusCode();
                } else {
                    $errorMessage = $e->getMessage();
                    $statusCode = null;
                }
                $this->serverCallStatus = new CallStatus($errorMessage, $statusCode, $e);
                return null;
            }
        };
        
        // if a promise function set
        if (isset($promiseFunction) && $promiseFunction !== null && $promiseFunction instanceof \Closure) {
            return $promiseResponse->then($success, $error)->then($promiseFunction);
        } else {
            return $promiseResponse->then($success, $error);
        }
    }
}
