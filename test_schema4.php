<?php
header('Content-Type: text/plain');

try {
    echo "PHP Version: " . phpversion() . "\n";

    if (function_exists('str_contains')) {
        echo "str_contains exists\n";
    } else {
        echo "str_contains DOES NOT EXIST -> FATAL ERROR in PHP < 8.0\n";
    }

} catch (Throwable $e) {
    echo "\nFATAL ERROR REVEALED: " . $e->getMessage() . "\n";
}
