<?php

//**********************************************************************************************
//                                        OCPUPHPAPI.php
//
// Author(s): Arnaud CHARLEROY
// OpenCPU for SILEX
// Copyright © - INRA - MISTEA - 2018
// Creation date: novembre 2015
// Contact:arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date: Feb. 08, 2018
// Subject: PHPCall Test API for OCPU
//***********************************************************************************************
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require openCPUClientPHP librairy
require_once '../vendor/autoload.php';

// Require classes
use openSILEX\opencpuClientPHP\OpenCPUServer;
use openSILEX\opencpuClientPHP\classes\OCPUSession;

try {
    // connection to the opencpu server
    $ocall = new OpenCPUServer("http://cloud.opencpu.org/ocpu/");

    // add stats informations
    $ocall::$ENABLE_CALL_STATS = true;

    // connection status with message status(true) or boolean status(false)
    print_r(PHP_EOL . "Server status : " . $ocall->status(true));

    // array parameters send to opencpu server
    $parameters1 = array("x" => "500000");

    // call R function
    $sessionInstance1 = $ocall->makeRCall("base", "identity", $parameters1);

    // call another function with a session name as parameter (.val result)
    $parameters2 = array("n" => $sessionInstance1->sessionId);

    // call another function
    $sessionInstance2 = $ocall->makeRCall("stats", "rnorm", $parameters2);

    // Synchronous results
    // Session 1
    print_r(PHP_EOL . "Source Session $sessionInstance1->sessionId:   " . $sessionInstance1->getSource());
    print_r(PHP_EOL . "Session $sessionInstance1->sessionId:   " . $sessionInstance1->getObjects());
    // sSession 2
    print_r(PHP_EOL . "Source Session $sessionInstance2->sessionId:   " . $sessionInstance2->getSource());
    // text
    print_r(PHP_EOL . "Session  value $sessionInstance2->sessionId: ");
    $text = $sessionInstance2->getVal();
    // text format
    echo PHP_EOL . $text;

    print_r(PHP_EOL . "Session  value $sessionInstance2->sessionId:");
    $json = $sessionInstance2->getVal(openSILEX\opencpuClientPHP\classes\ConstantClassDefinition::OPENCPU_SESSION_JSON_FORMAT);
    // Json format
//    var_dump($json);
    // async call R function
    print_r(PHP_EOL . "Promise session");
    $promiseSession = $ocall->makeAsyncRCall(
            "base",
        "identity",
        $parameters1,
            // asynchronous results
            function ($session) {
                if ($session !== null) {
                    print_r(PHP_EOL . "Promise session value : ");
                    echo PHP_EOL . $session->sessionId . PHP_EOL;
                }
            }
    );

    // if you want to wait result
    // print_r(PHP_EOL . "Promise session waited results");
    // $waitedPromiseSession = $promiseSession->wait();
    // $waitedPromiseSession->sessionId;
    
    // call R Snippet
    $sessionInstance3 = $ocall->makeRSnippetCall("x <- 1; y = 2*x");
    print_r(PHP_EOL . "Session  value $sessionInstance3->sessionId: ");
    $text2 = $sessionInstance3->getVal();

    // text format
    echo PHP_EOL . $text2;
    // test of Unknown session
    // set unknown session
    print_r(PHP_EOL . "Unknown session : ");
    $unknownSession = $ocall->getSessionById("x0592ea8178");
    print_r(PHP_EOL . "Unknown session objects ");
    // var_dump($unknownSession->getVal());
} catch (Exception $ex) {
    echo $ex->getMessage();
}
