openCPU-client-php
=============

openCPU-client-php is a PHP library that works as a client for the **OpenCPU** Server (https://www.opencpu.org/) and it was built with flexibility and maintainability in mind.


Setup
-----

The recommended way to install openCPU-client-php is through  [`Composer`](http://getcomposer.org). Just create a ``composer.json`` file and run the ``php composer.phar install`` command to install it:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/openSILEX/opencpu-client-php"
        }
    ],
    "require": {
        "openSILEX/opencpu-client-php": "dev-master"
    }
}
```

Usage
-----

Configure examples : 

After installing openCPU-client-php (through ``"Composer"``), put the examples directory in a web server accessible path.
Set the right vendor path in ``examples/config/configLibrairy.php``

After you will be able to use example, try this :

```php
// Require librairy 
require_once '../vendor/autoload.php';

// Require classes 
use openSILEX\opencpuClientPHP\OpenCPUServer;
use openSILEX\opencpuClientPHP\classes\OCPUSession;

// connexion to the opencpu server
$ocall = new OpenCPUServer("https://cloud.opencpu.org/ocpu/");
// connection status
print_r("Server status : " . $ocall->status(true));

// array parameters
$parameters1 = array("x" => "500000");

// call R function
$sessionInstance1 = $ocall->makeRCall("base", "identity", $parameters1);
print_r("Source Session $sessionInstance1->sessionId:   " . $sessionInstance1->getSource());
print_r("Session $sessionInstance1->sessionId:   " . $sessionInstance1->getObjects());



```