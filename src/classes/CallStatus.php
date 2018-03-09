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
 * Description of CallStatus
 *
 * @author Arnaud CHARLEROY <arnaud.charleroy@inra.fr>
 */
class CallStatus {
    private $exception;
    private $message;
    private $status;

    public function __construct($message, $status = null, $exception = null) {
        $this->exception = $exception;
        $this->message = $message;
        $this->status = $status;
    }
    
    public function getException() {
        return $this->exception;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setException($exception) {
        $this->exception = $exception;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    /**
     *
     * @return string complete definition of the stats
     */
    public function __toString() {
        $message =  "<pre> Message : " . $this->message . "<br> Status : " . $this->status . "<br>";
        if (isset($this->exception)) {
            $message .= " Exception : " . $this->exception;
        }
        $message .= '</pre>';
        return ;
    }
}
