<?php

declare(strict_types=1);

// Fix default_charset for FU userpage (htmlentities())
// https://stackoverflow.com/questions/8229696/do-i-need-to-set-ini-set-default-charset-utf-8
if (! ini_set('default_charset', 'utf-8')) {
    die("Could not set default_charset to utf-8.");
}

// Register autoloader
spl_autoload_register(function ($typeName) {
    $fileName = str_replace("\\", "/", $typeName).".php";

    if (file_exists($fileName)) {
        include $fileName;
    }
});

// Register exception handler
$developmentMode = false;

set_exception_handler(function ($throwable) {
    // Parse error in Application.php would cause $developmentMode to not be properly set
    error_log("Unhandled exception in ".$throwable->getFile()." on line ".$throwable->getLine().": ".$throwable->getMessage());

    if ($GLOBALS["developmentMode"]) {
        echo "<b>Unhandled exception: </b>".$throwable->getMessage()."<br><br>Stack trace:<br><pre>".$throwable->getTraceAsString()."</pre>thrown in <b>".$throwable->getFile()."</b> on line <b>".$throwable->getLine()."</b>.";

        while ($throwable = $throwable->getPrevious()) {
            echo "<br><br><hr><br><b>Caused by: </b>".$throwable->getMessage()."<br><br>Stack trace:<br><pre>".$throwable->getTraceAsString()."</pre>thrown in <b>".$throwable->getFile()."</b> on line <b>".$throwable->getLine()."</b>.";
        }
    } else {
        echo "Unhandled exception.";
    }
});

// Initialize and run Application
use \DBConstructor\Application;
Application::$instance = new Application();
Application::$instance->run();
