<?php

/**
 * Include i classi e models dalle rispettive cartelle
 */

foreach ($GLOBALS['include'] as $folder) {
    if (is_dir($folder)) {
        set_include_path($folder);
        spl_autoload_register(function ($class) use ($folder) {
            $file = explode('/', str_replace('\\', '/', $class . '.php'));
            $file = end($file);
            if (file_exists($folder . '/' . $file)) {
                print_r($folder . '/' . $file . "\n");
                if (!in_array($class, get_declared_classes())) {
                    require_once $folder . '/' . $file;
                }
            }
        });
        /*
        $handle = opendir($folder);
        while ($file = readdir($handle)) {
            if ($file != "." && $file != "..") {
                $tmp = explode('.', $file);
                $tmp = end($tmp);
                if ($tmp === "php") {
                    #include(__DIR__ . "/" . $classi . "/" . $file);
                    if (is_file($folder . '/' . $file)) {
                        require_once($folder . "/" . $file);
                    }
                }
            }
        }
        */
    } else {
        require_once $folder;
    }
}
/*
$handle = opendir(_CLASSI_);
while ($file = readdir($handle)) {
    if ($file != "." && $file != "..") {
        if (stristr($file, ".php")) {
            #include(__DIR__ . "/" . $classi . "/" . $file);
            require_once(_CLASSI_ . "/" . $file);
        }
    }
}
$handle = opendir(_MODELS_);
while ($file = readdir($handle)) {
    if ($file != "." && $file != "..") {
        if (stristr($file, ".php")) {
            #include(__DIR__ . "/" . $classi . "/" . $file);
            require_once(_MODELS_ . "/" . $file);
        }
    }
}
*/
