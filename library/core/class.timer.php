<?php
/**
 * Gdn_Timer.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    public function ElapsedTime() {
        if (is_null($this->FinishTime))
            $Result = microtime(TRUE) - $this->StartTime;
        else
            $Result = $this->FinishTime - $this->StartTime;
        return $Result;
    }

    /**
     *
     *
     * @param string $Message
     */
    public function Finish($Message = '') {
        $this->FinishTime = microtime(TRUE);
        if ($Message)
            $this->Write($Message, $this->FinishTime, $this->StartTime);
    }

    /**
     *
     *
     * @param $Span
     * @return string
     */
    public static function FormatElapsed($Span) {
        $m = floor($Span / 60);
        $s = $Span - $m * 60;
        return sprintf('%d:%05.2f', $m, $s);
    }

    /**
     *
     *
     * @param string $Message
     */
    public function Start($Message = '') {
        $this->StartTime = microtime(TRUE);
        $this->SplitTime = $this->StartTime;
        $this->FinishTime = NULL;

        if ($Message)
            $this->Write($Message, $this->StartTime);
    }

    /**
     *
     *
     * @param string $Message
     */
    public function Split($Message = '') {
        $PrevSplit = $this->SplitTime;
        $this->SplitTime = microtime(TRUE);
        if ($Message) ;
        $this->Write($Message, $this->SplitTime, $PrevSplit);
    }

    /**
     *
     *
     * @param $Message
     * @param null $Time
     * @param null $PrevTime
     */
    public function Write($Message, $Time = NULL, $PrevTime = NULL) {
        if ($Message)
            echo $Message;
        if (!is_null($Time)) {
            if ($Message)
                echo ': ';
            echo date('Y-m-d H:i:s', $Time);
            if (!is_null($PrevTime)) {
                $Span = $Time - $PrevTime;
                $m = floor($Span / 60);
                $s = $Span - $m * 60;
                echo sprintf(' (%d:%05.2f)', $m, $s);
            }
        }
        echo "\n";
    }

}
