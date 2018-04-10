<?php
/**
 * Gdn_Condition
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Class Gdn_Condition
 */
class Gdn_Condition {

    const PERMISSION = 'permission';

    const REQUEST = 'request';

    const ROLE = 'role';

    const COMPARE_AND = 'and';

    const COMPARE_OR = 'or';

    public static $compareType = self::COMPARE_OR;

    /**
     *
     *
     * @return array
     */
    public static function allTypes() {
        return [self::PERMISSION => self::PERMISSION, self::ROLE => self::ROLE];
    }

    /**
     *
     *
     * @return array
     */
    public static function blank() {
        return ['', '', ''];
    }

    /**
     * Convert the condition values in a given string to a conditions array.
     *
     * This method is the opposite as Gdn_Condition::toString().
     *
     * @param string $string
     * @return array A conditions array suitable to be passed to Gdn_Condition::test().
     * @see Gdn_Condition::toString().
     */
    public static function fromString($string) {
        $result = [];

        // Each condition is delimited by a newline.
        $conditions = explode("\n", $string);
        foreach ($conditions as $conditionString) {
            // Each part of the condition is delimited by a comma.
            $condition = explode(',', $conditionString, 3);
            $result[] = array_map('trim', $condition);
        }
        return $result;
    }

    /**
     * Test an array of conditions. This method only returns if every condition in the array is true.
     *
     * @param array $conditions And array of conditions where each condition is itself an array with the following items:
     *  - 0: The type of condition. See the constants in Gdn_Condition for more information.
     *  - 1: The field to look at.
     *  - 2: The expression to test against (optional).
     * @return bool
     */
    public static function test($conditions) {
        if (!is_array($conditions)) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue;
            }

            $expr = isset($condition[2]) ? $condition[2] : null;

            $test = Gdn_Condition::testOne($condition[0], $condition[1], $expr);
            if (!$test && self::$compareType == self::COMPARE_AND) {
                return false;
            }
            if ($test && self::$compareType == self::COMPARE_OR) {
                return true;
            }
        }
        if (self::$compareType == self::COMPARE_AND) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Test an individual condition.
     *
     * @param string $type One of the types in this condition.
     * @param string $field The field to test against.
     * @param string $expr The expression to test with.
     * @return bool
     */
    public static function testOne($type, $field, $expr = null) {
        switch (strtolower($type)) {
            case PERMISSION:
                // Check to see if the user has the given permission.
                $result = Gdn::session()->checkPermission($field);
                if ($expr === false) {
                    return !$result;
                }
                return $result;
            case REQUEST:
                // See if the field is a specific value.
                switch (strtolower($field)) {
                    case 'path':
                        $value = Gdn::request()->path();
                        break;
                    default:
                        // See if the field is targetting a specific part of the request.
                        $fields = explode('.', $field, 2);
                        if (count($fields) >= 2) {
                            $value = Gdn::request()->getValueFrom($fields[0], $fields[1], null);
                        } else {
                            $value = Gdn::request()->getValue($field, null);
                        }
                        break;
                }

                $result = Gdn_Condition::testValue($value, $expr);
                return $result;
            case ROLE:
                // See if the user is in the given role.
                $roleModel = new RoleModel();
                $roles = $roleModel->getByUserID(Gdn::session()->UserID)->resultArray();
                foreach ($roles as $role) {
                    if (is_numeric($expr)) {
                        $result = $expr == val('RoleID', $role);
                    } else {
                        $result = Gdn_Condition::testValue(val('Name', $role), $expr);
                    }
                    if ($result) {
                        return true;
                    }
                }
                return false;
        }
        return false;
    }

    /**
     * Test a value against an expression.
     *
     * @param mixed $value The value to test.
     * @param string $expr The expression to test against. The expression can have the following properties.
     *  - <b>Enclosed in backticks (`..`): A preg_match() is performed.
     *  - <b>Otherwise</b>: A simple $value == $expr is tested.
     */
    public static function testValue($value, $expr) {
        if (!is_string($expr)) {
            return false;
        }

        if (stelen($expr) > 1 && $expr[0] === '`' && $expr[strlen($expr) - 1] == '`') {
            $result = preg_match($expr, $value);
        } else {
            $result = $value == $expr;
        }
        return $result;
    }

    /**
     * Convert an array of conditions to a string.
     *
     * @param array $conditions An array of conditions. Each condition is itself an array.
     * @return string
     * @see Gdn_Condition::test()
     */
    public static function toString($conditions) {
        $result = '';

        foreach ($conditions as $condition) {
            if (!is_array($condition) || count($condition) < 2) {
                continue; // skip ill-formatted conditions.
            }

            if (strlen($result) > 0) {
                $result .= "\n";
            }

            $result .= "{$condition[0]},{$condition[1]}";
            if (count($condition) >= 3) {
                $result .= $condition[2];
            }
        }
        return $result;
    }
}
