<?php
include_once 'EventStreamController.php';

set_time_limit(0);

define('YOUTUBE_DL_PATH', "youtube-dl");
define('FETCH_ARGS', '--skip-download -s -e --get-thumbnail');
define('DL_ARGS', '-x --audio-format mp3');
define('AUDIO_QUALITY', '--audio-quality ');
define ('CACHE_DIR', ' --cache-dir cache/');

define('THUMB_MSG', 'Thumbnail');
define('TITLE_MSG', 'Title');
define('OVER_MSG', 'END');

define('DL_STEP', 'Step');

define('SPACE', ' ');

class YoutubeDl
{
    protected $_processId;

    function __construct()
    {
        $this->_processId = uniqid();
    }

    function fetch($target)
    {
        $count = 1;
        $step1 = false;
        $step2 = false;

        $cmd = $this->buildFetchCmd( $target );

        $handle = popen($cmd, "r");

        if (ob_get_level() == 0)
            ob_start();

        while (!feof($handle)) {

            if ($buffer = fgets($handle)) {

                if (0 === strpos($buffer, 'http')) {
                    // It starts with 'http'
                    EventStreamController::sendMessage(THUMB_MSG, $buffer, $count);
                    $step1 = true;
                } else {
                    EventStreamController::sendMessage(TITLE_MSG, $buffer, $count);
                    $step2 = true;
                }

                if ($step1 && $step2) {
                    $count++;
                    $step1 = $step2 = false;
                }
            }
        }

        pclose($handle);

        EventStreamController::sendMessage(OVER_MSG);

        ob_end_flush();
    }

    function download( $target = '', $targets = '' )
    {
        // -- Destination
        $this->prepareFolder();
        $cmd = $this->buildDownloadCmd( $target, $targets );
        $path = $this->getPath();

        $arr_targets = explode(',', $targets);

        $handle = popen($cmd, "r");

        if (ob_get_level() == 0)
            ob_start();

        while (!feof($handle)) {

            if ($buffer = fgets($handle)) {
                EventStreamController::sendMessage(DL_STEP, $buffer);
            }
        }

        pclose($handle);

        // -- Compress
        if (count($arr_targets) > 1) {
            exec("zip -j " . $path . "download.zip " . $path . "*");
            $link = $this->generateDownloadLink( 'download.zip');
        }
        else {
            $files = array_filter(scandir( $path ), array($this, 'folder_filter') );
            if (count($files ) === 1) {
                $file = $this->getKeyedFirstElem($files);
                $link = $this->generateDownloadLink($file);
            }
        }

        // -- End process
        EventStreamController::sendMessage(OVER_MSG, $link);

        ob_end_flush();
    }

    protected function buildFetchCmd($target)
    {
        return YOUTUBE_DL_PATH . SPACE . FETCH_ARGS . SPACE . CACHE_DIR . SPACE . '"' . $target . '"';
    }

    protected function buildDownloadCmd($target, $targets)
    {
        $dest = "-o \"" . $this->getPath() . "%(title)s.%(ext)s\"";
        $cmd = YOUTUBE_DL_PATH . SPACE . DL_ARGS . CACHE_DIR . SPACE . AUDIO_QUALITY . '2' . SPACE;
        if ($targets != "") {
            $cmd .= '--playlist-items' . SPACE . $targets . SPACE;
        }

        $cmd .= $dest . SPACE;
        $cmd .= '"' . $target . '"';

        return $cmd;
    }

    protected function getPath()
    {
        return $_SERVER['DOCUMENT_ROOT']. "/buffer/" . $this->_processId . "/";
    }

    protected function generateDownloadLink($filename)
    {
        return  '/buffer/' . $this->_processId . '/' . $filename;
    }

    protected function prepareFolder()
    {
        exec("mkdir " . $this->getPath());
    }

    private function folder_filter($var)
    {
        return $var !== '.' && $var !== '..';
    }

    private function getKeyedFirstElem($arr)
    {
        foreach ($arr as $key => $val)
        {
            return $val;
        }
    }
}