<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Custom error handler
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    $message = "[ERROR] $errstr in $errfile on line $errline";
    error_log($message);
    echo "<pre>$message</pre>";
    return true;
});

// Custom exception handler
set_exception_handler(function ($exception) {
    $message = "[EXCEPTION] " . $exception->getMessage() . " in " . 
               $exception->getFile() . " on line " . $exception->getLine();
    error_log($message);
    echo "<pre>$message</pre>";
});

// Shutdown handler for fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        $message = "[FATAL] {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($message);
        echo "<pre>$message</pre>";
    }
});

echo "✅ Debugging enabled. Visit your main site to capture errors.";
