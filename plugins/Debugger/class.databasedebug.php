<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
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
    protected $_Queries = [];

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
     * @param $args
     * @return string
     */
    private static function formatArgs($args) {
        if (!is_array($args)) {
            return '';
        }

        $result = '';
        foreach ($args as $i => $expr) {
            if (strlen($result) > 0) {
                $result .= ', ';
            }
            $result .= self::formatExpr($expr);
        }
        return $result;
    }

    /**
     *
     *
     * @param $expr
     * @return string
     */
    private static function formatExpr($expr) {
        if (is_array($expr)) {
            if (count($expr) > 3) {
                $result = count($expr);
            } else {
                $result = '';
                foreach ($expr as $key => $value) {
                    if (strlen($result) > 0) {
                        $result .= ', ';
                    }
                    $result .= '\''.str_replace('\'', '\\\'', $key).'\' => '.self::formatExpr($value);
                }
            }
            return 'array('.$result.')';
        } elseif (is_null($expr)) {
            return 'NULL';
        } elseif (is_string($expr)) {
            return '\''.str_replace('\'', '\\\'', $expr).'\'';
        } elseif (is_object($expr)) {
            return 'Object:'.get_class($expr);
        } else {
            return $expr;
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
     * @param string $sql
     * @param null $inputParameters
     * @param array $options
     * @return Gdn_DataSet|object|string
     */
    public function query($sql, $inputParameters = null, $options = []) {
        $trace = debug_backtrace();
        $method = '';
        foreach ($trace as $info) {
            $class = val('class', $info, '');
            if ($class === '' || stringEndsWith($class, 'Model', true) || stringEndsWith($class, 'Plugin', true)) {
                $type = val('type', $info, '');

                $method = $class.$type.$info['function'].'('.self::formatArgs($info['args']).')';
                break;
            }
        }

        // Save the query for debugging
        // echo '<br />adding to queries: '.$Sql;
        $query = ['Sql' => $sql, 'Parameters' => $inputParameters, 'Method' => $method];
        $saveQuery = true;
        if (isset($options['Cache'])) {
            $cacheKeys = (array)$options['Cache'];
            $cache = [];

            $allSet = true;
            foreach ($cacheKeys as $cacheKey) {
                $value = Gdn::cache()->get($cacheKey);
                $cacheValue = $value !== Gdn_Cache::CACHEOP_FAILURE;
                $allSet &= $cacheValue;
                $cache[$cacheKey] = $cacheValue;
            }
            $saveQuery = !$allSet;
            $query['Cache'] = $cache;
        }

        // Start the Query Timer
        $timeStart = now();

        $result = parent::query($sql, $inputParameters, $options);
        $query = array_merge($this->LastInfo, $query);

        // Aggregate the query times
        $timeEnd = now();
        $this->_ExecutionTime += ($timeEnd - $timeStart);

        if ($saveQuery && !stringBeginsWith($sql, 'set names')) {
            $query['Time'] = ($timeEnd - $timeStart);
            $this->_Queries[] = $query;
        }

        return $result;
    }

    /**
     *
     *
     * @return array
     */
    public function queryTimes() {
        return [];
    }
}
