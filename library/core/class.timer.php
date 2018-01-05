<?php
/**
 * Gdn_Timer.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * A simple timer class that can be used to time longer running processes.
 */
class Gdn_Timer {

    /** @var int Seconds. */
    public $StartTime;

    /** @var int Seconds. */
    public $FinishTime;

    /** @var int Seconds. */
    public $SplitTime;

    /**
     *
     *
     * @return mixed
     */
    public function elapsedTime() {
        if (is_null($this->FinishTime)) {
            $result = microtime(true) - $this->StartTime;
        } else {
            $result = $this->FinishTime - $this->StartTime;
        }
        return $result;
    }

    /**
     *
     *
     * @param string $message
     */
    public function finish($message = '') {
        $this->FinishTime = microtime(true);
        if ($message) {
            $this->write($message, $this->FinishTime, $this->StartTime);
        }
    }

    /**
     *
     *
     * @param $span
     * @return string
     */
    public static function formatElapsed($span) {
        $m = floor($span / 60);
        $s = $span - $m * 60;
        return sprintf('%d:%05.2f', $m, $s);
    }

    /**
     *
     *
     * @param string $message
     */
    public function start($message = '') {
        $this->StartTime = microtime(true);
        $this->SplitTime = $this->StartTime;
        $this->FinishTime = null;

        if ($message) {
            $this->write($message, $this->StartTime);
        }
    }

    /**
     *
     *
     * @param string $message
     */
    public function split($message = '') {
        $prevSplit = $this->SplitTime;
        $this->SplitTime = microtime(true);
        if ($message) {
        }
        $this->write($message, $this->SplitTime, $prevSplit);
    }

    /**
     *
     *
     * @param $message
     * @param null $time
     * @param null $prevTime
     */
    public function write($message, $time = null, $prevTime = null) {
        if ($message) {
            echo $message;
        }
        if (!is_null($time)) {
            if ($message) {
                echo ': ';
            }
            echo date('Y-m-d H:i:s', $time);
            if (!is_null($prevTime)) {
                $span = $time - $prevTime;
                $m = floor($span / 60);
                $s = $span - $m * 60;
                echo sprintf(' (%d:%05.2f)', $m, $s);
            }
        }
        echo "\n";
    }
}
