<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Debugger
 */

/**
 * Class Gdn_DatabaseDebug
 */
class Gdn_DatabaseDebug extends Gdn_Database {

    /** @var int  */
    protected $_ExecutionTime = 0;

    /** @var array  */
    protected $_Queries = array();

    /**
     *
     *
     * @return int
     */
    public function executionTime() {
        return $this->_ExecutionTime;
    }

    /**
     *
     *
     * @param $Args
     * @return string
     */
    private static function formatArgs($Args) {
        if (!is_array($Args)) {
            return '';
        }

        $Result = '';
        foreach ($Args as $i => $Expr) {
            if (strlen($Result) > 0) {
                $Result .= ', ';
            }
            $Result .= self::formatExpr($Expr);
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Expr
     * @return string
     */
    private static function formatExpr($Expr) {
        if (is_array($Expr)) {
            if (count($Expr) > 3) {
                $Result = count($Expr);
            } else {
                $Result = '';
                foreach ($Expr as $Key => $Value) {
                    if (strlen($Result) > 0) {
                        $Result .= ', ';
                    }
                    $Result .= '\''.str_replace('\'', '\\\'', $Key).'\' => '.self::formatExpr($Value);
                }
            }
            return 'array('.$Result.')';
        } elseif (is_null($Expr)) {
            return 'NULL';
        } elseif (is_string($Expr)) {
            return '\''.str_replace('\'', '\\\'', $Expr).'\'';
        } elseif (is_object($Expr)) {
            return 'Object:'.get_class($Expr);
        } else {
            return $Expr;
        }
    }

    /**
     *
     *
     * @return array
     */
    public function queries() {
        return $this->_Queries;
    }

    /**
     *
     *
     * @param string $Sql
     * @param null $InputParameters
     * @param array $Options
     * @return Gdn_DataSet|object|string
     */
    public function query($Sql, $InputParameters = null, $Options = array()) {
        $Trace = debug_backtrace();
        $Method = '';
        foreach ($Trace as $Info) {
            $Class = val('class', $Info, '');
            if ($Class === '' || stringEndsWith($Class, 'Model', true) || stringEndsWith($Class, 'Plugin', true)) {
                $Type = arrayValue('type', $Info, '');

                $Method = $Class.$Type.$Info['function'].'('.self::formatArgs($Info['args']).')';
                break;
            }
        }

        // Save the query for debugging
        // echo '<br />adding to queries: '.$Sql;
        $Query = array('Sql' => $Sql, 'Parameters' => $InputParameters, 'Method' => $Method);
        $SaveQuery = true;
        if (isset($Options['Cache'])) {
            $CacheKeys = (array)$Options['Cache'];
            $Cache = array();

            $AllSet = true;
            foreach ($CacheKeys as $CacheKey) {
                $Value = Gdn::cache()->get($CacheKey);
                $CacheValue = $Value !== Gdn_Cache::CACHEOP_FAILURE;
                $AllSet &= $CacheValue;
                $Cache[$CacheKey] = $CacheValue;
            }
            $SaveQuery = !$AllSet;
            $Query['Cache'] = $Cache;
        }

        // Start the Query Timer
        $TimeStart = now();

        $Result = parent::query($Sql, $InputParameters, $Options);
        $Query = array_merge($this->LastInfo, $Query);

        // Aggregate the query times
        $TimeEnd = now();
        $this->_ExecutionTime += ($TimeEnd - $TimeStart);

        if ($SaveQuery && !stringBeginsWith($Sql, 'set names')) {
            $Query['Time'] = ($TimeEnd - $TimeStart);
            $this->_Queries[] = $Query;
        }

        return $Result;
    }

    /**
     *
     *
     * @return array
     */
    public function queryTimes() {
        return array();
    }
}
