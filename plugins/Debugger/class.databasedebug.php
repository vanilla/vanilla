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
    /// PROPERTIES ///

    protected $_ExecutionTime = 0;

    protected $_Queries = array();

    /// METHODS ///

    public function ExecutionTime() {
        return $this->_ExecutionTime;
    }

    private static function FormatArgs($Args) {
        if (!is_array($Args))
            return '';

        $Result = '';

        foreach ($Args as $i => $Expr) {
            if (strlen($Result) > 0)
                $Result .= ', ';
            $Result .= self::FormatExpr($Expr);
        }
        return $Result;
    }

    private static function FormatExpr($Expr) {
        if (is_array($Expr)) {
            if (count($Expr) > 3) {
                $Result = count($Expr);
            } else {
                $Result = '';
                foreach ($Expr as $Key => $Value) {
                    if (strlen($Result) > 0)
                        $Result .= ', ';
                    $Result .= '\''.str_replace('\'', '\\\'', $Key).'\' => '.self::FormatExpr($Value);
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

    public function Queries() {
        return $this->_Queries;
    }

    public function Query($Sql, $InputParameters = NULL, $Options = array()) {
        $Trace = debug_backtrace();
        $Method = '';
        foreach ($Trace as $Info) {
            $Class = GetValue('class', $Info, '');
            if ($Class === '' || StringEndsWith($Class, 'Model', TRUE) || StringEndsWith($Class, 'Plugin', TRUE)) {
                $Type = ArrayValue('type', $Info, '');

                $Method = $Class.$Type.$Info['function'].'('.self::FormatArgs($Info['args']).')';
                break;
            }
        }

        // Save the query for debugging
        // echo '<br />adding to queries: '.$Sql;
        $Query = array('Sql' => $Sql, 'Parameters' => $InputParameters, 'Method' => $Method);
        $SaveQuery = TRUE;
        if (isset($Options['Cache'])) {
            $CacheKeys = (array)$Options['Cache'];
            $Cache = array();

            $AllSet = TRUE;
            foreach ($CacheKeys as $CacheKey) {
                $Value = Gdn::Cache()->Get($CacheKey);
                $CacheValue = $Value !== Gdn_Cache::CACHEOP_FAILURE;
                $AllSet &= $CacheValue;
                $Cache[$CacheKey] = $CacheValue;
            }
            $SaveQuery = !$AllSet;
            $Query['Cache'] = $Cache;
        }

        // Start the Query Timer
        $TimeStart = Now();

        $Result = parent::Query($Sql, $InputParameters, $Options);
        $Query = array_merge($this->LastInfo, $Query);

        // Aggregate the query times
        $TimeEnd = Now();
        $this->_ExecutionTime += ($TimeEnd - $TimeStart);

        if ($SaveQuery && !StringBeginsWith($Sql, 'set names')) {
            $Query['Time'] = ($TimeEnd - $TimeStart);
            $this->_Queries[] = $Query;
        }

        return $Result;
    }

    public function QueryTimes() {
        return array();
    }
}
