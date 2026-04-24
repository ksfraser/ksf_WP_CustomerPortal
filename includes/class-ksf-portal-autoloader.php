<?php

class KSF_Portal_Autoloader
{
    public static function autoload($class)
    {
        $prefix = 'KSF_';
        $base_dir = KSF_PORTAL_PATH . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . $relative_class . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
}

spl_autoload_register(['KSF_Portal_Autoloader', 'autoload']);