<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Validate date period for Sphinx usage
 */
class DateFilterSphinxSchema extends DateFilterSchema {
    /**
     * If the parameter value is a valid date filter value,
     * return a structure: startDate, endDate, exclude flag.
     * Useful for Sphinx setFilterRange
     *
     * @param mixed $dateData The decoded and validated date data.
     *
     * @throws \InvalidArgumentException If dateData structure is wrong throw exception.
     *
     * @return array Structure: DateTime startDate, DateTime endDate, Boolean inclusive
     */
    public static function dateFilterRange(array $dateData): array {
        $validOperators = ['=', '>', '<', '>=', '<=', '[]', '()'];
        $result = [
            'startDate' => null,
            'endDate' => null,
            'exclude' => false
        ];

        if (array_key_exists('operator', $dateData)
            && array_key_exists('date', $dateData)
            && is_array($dateData['date'])
        ) {
            $op = $dateData['operator'];
            $dates = $dateData['date'];

            if (in_array($op, $validOperators)) {
                switch ($op) {
                    case '>':
                    case '>=':
                        $result['startDate'] = $dates[0];
                        if ($op === '>') {
                            $result['exclude'] = true;
                        }
                        break;
                    case '<':
                    case '<=':
                        $result['endDate'] = $dates[0];
                        if ($op === '<') {
                            $result['exclude'] = true;
                        }
                        break;
                    case '[]':
                    case '()':
                        $result['startDate'] = $dates[0];
                        $result['endDate'] = $dates[1];
                        if ($op === '()') {
                            $result['exclude'] = true;
                        }
                        break;
                    case '=':
                        $result['startDate'] = $dates[0];
                        $result['endDate'] = $dates[1] ?? $dates[0];
                        break;
                }
            }
        } else {
            throw new \InvalidArgumentException('Invalid data supplied to dateFilterRange');
        }

        return $result;
    }
}
