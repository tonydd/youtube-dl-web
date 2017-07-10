<?php
include_once 'YoutubeDl.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

class EventStreamController
{
    protected $_youtubeHelper;

    function __construct()
    {
        $this->_youtubeHelper = new YoutubeDl();
    }

    public function getUrlInfo( $url )
    {
        $this->_youtubeHelper->fetch( $url );
    }

    public function download($target, $targets)
    {
        $this->_youtubeHelper->download( $target, $targets );
    }

    public static function sendMessage($type, $message = '', $count = null)
    {
        $d = array(
            'type' => $type,
        );

        if (!is_null($message)) $d['message'] = $message;
        if(!is_null($count)) $d['item'] = $count;

        echo "data: " . json_encode($d) . PHP_EOL;
        echo PHP_EOL;

        //PUSH THE data out by all FORCE POSSIBLE
        ob_flush();
        flush();
    }

}