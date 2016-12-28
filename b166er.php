<?php

namespace {

    define('MODE', 'debug');
    //define('FORCE SHARED TOOLKIT', 'true');

    /*
     *
     */
    
    
    $_toolkit_name = basename(__FILE__, '.php');

    $_local = __DIR__ . '/' . $_toolkit_name;

    $_shared = $_SERVER['DOCUMENT_ROOT'] . '/' . $_toolkit_name;

    if (defined('FORCE SHARED TOOLKIT')) {

        $_path = $_shared;

    } else {

        $_path = is_dir($_local) ? $_local : $_shared;
        
    }

    try {

        if (!(is_dir($_path))) {
            throw new \Exception("Sorry. No $_toolkit_name toolkit found in $_path", 999);
        }

        /*
         * REQUIRE CORE FILES
         */

        $coreFiles = array(
            'global.php',
            'interface.php',
            'iterator.php',
            'composite.php',
            'classes.php',
            'dao.php',
            'application.php'
        );

        foreach ($coreFiles as $coreFile) {

            $tmp = "$_path/$coreFile";

            if (is_file($tmp)) {
                require_once ($tmp);
            } else {
                throw new \Exception("The core file '$coreFile' was not found");
            }

        }

    } catch (\Exception $e) {

        echo('
                <!DOCTYPE html>
                    <html>
                        <body>
                            <h1>   "' . $_toolkit_name . '" toolkit error</h1>
                                <p><b>' . $e->getMessage() . '</b></p>
                                <ul>
                                    <li>Error occurred in ' . $e->getFile() . '</li>
                                    <li>line: ' . $e->getLine() . '</li>
                                    <li>error code: ' . $e->getCode() . '</li>
                                    <li>exception class: ' . get_class($e) . '</li>
                                </ul>
                        </body>
                    </html>
                ');
        die();
    }
}
namespace b166er {

    /*
     * ERROR STUFF
     */

    function uncaught_exception_trigger($code, $message, $file, $line, $context)
    {
        throw new \Exception($code, $message, $file, $line, $context);
    }

    set_exception_handler('uncaught_exception_trigger');

    /*
     * SHORTCUTS
     */

    function __app()
    {
        return Application::Instance();
    }

    function Me()
    {
        return Application::Instance();
    }
}


