<?php
include_once 'EventStreamController.php';

App::process();

class App
{
    public static function process(  )
    {
        $esc = new EventStreamController();

        $action = isset( $_GET['action']) ? $_GET['action'] : null;

        $esc::sendMessage('DBG', "Treat action $action");

        switch ($action)
        {

            case 'fetch':
                if (isset($_GET['target'])) {
                    $target = $_GET['target'];
                    $esc->getUrlInfo( $target );
                }
                break;

            case 'dl':

                if (isset($_GET['target'])) {
                    $target = $_GET['target'];
                }

                if (isset($_GET['targets'])) {
                    $targets = $_GET['targets'];
                }

                $esc->download($target, $targets);
                break;

            case 'clean':

                exec("rm -R buffer/*");
                break;

            case 'dbg':
                var_export( $_SERVER );

                echo PHP_EOL;

                var_export( scandir('../buffer/5925d133e9a20/') );

                break;

            default:
                $esc::sendMessage('DBG', 'Unknown action');

        }
    }
}