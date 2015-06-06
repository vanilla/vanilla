<?php
/**
 * Gdn_Condition
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        return array(self::PERMISSION => self::PERMISSION, self::ROLE => self::ROLE);
    }

    /**
     *
     *
     * @return array
     */
    public static function blank() {
        return array('', '', '');
    }

    /**
     * Convert the condition values in a given string to a conditions array.
     *
     * This method is the opposite as Gdn_Condition::ToString().
     *
     * @param string $String
     * @return array A conditions array suitable to be passed to Gdn_Condition::Test().
     * @see Gdn_Condition::toString().
     */
    public static function fromString($String) {
        $Result = array();

        // Each condition is delimited by a newline.
        $Conditions = explode("\n", $String);
        foreach ($Conditions as $ConditionString) {
            // Each part of the condition is delimited by a comma.
            $Condition = explode(',', $ConditionString, 3);
            $Result[] = array_map('trim', $Condition);
        }
        return $Result;
    }

    /**
     * Test an array of conditions. This method only returns if every condition in the array is true.
     *
     * @param array $Conditions And array of conditions where each condition is itself an array with the following items:
     *  - 0: The type of condition. See the constants in Gdn_Condition for more information.
     *  - 1: The field to look at.
     *  - 2: The expression to test against (optional).
     * @return bool
     */
    public static function test($Conditions) {
        if (!is_array($Conditions)) {
            return false;
        }

        foreach ($Conditions as $Condition) {
            if (!is_array($Condition) || count($Condition) < 2) {
                continue;
            }

            $Expr = isset($Condition[2]) ? $Condition[2] : null;

            $Test = Gdn_Condition::testOne($Condition[0], $Condition[1], $Expr);
            if (!$Test && self::$compareType == self::COMPARE_AND) {
                return false;
            }
            if ($Test && self::$compareType == self::COMPARE_OR) {
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
     * @param string $Type One of the types in this condition.
     * @param string $Field The field to test against.
     * @param string $Expr The expression to test with.
     * @return bool
     */
    public static function testOne($Type, $Field, $Expr = null) {
        switch (strtolower($Type)) {
            case PERMISSION:
                // Check to see if the user has the given permission.
                $Result = Gdn::session()->checkPermission($Field);
                if ($Expr === false) {
                    return !$Result;
                }
                return $Result;
            case REQUEST:
                // See if the field is a specific value.
                switch (strtolower($Field)) {
                    case 'path':
                        $Value = Gdn::request()->path();
                        break;
                    default:
                        // See if the field is targetting a specific part of the request.
                        $Fields = explode('.', $Field, 2);
                        if (count($Fields) >= 2) {
                            $Value = Gdn::request()->getValueFrom($Fields[0], $Fields[1], null);
                        } else {
                            $Value = Gdn::request()->getValue($Field, null);
                        }
                        break;
                }

                $Result = Gdn_Condition::testValue($Value, $Expr);
                return $Result;
            case ROLE:
                // See if the user is in the given role.
                $RoleModel = new RoleModel();
                $Roles = $RoleModel->getByUserID(Gdn::session()->UserID)->resultArray();
                foreach ($Roles as $Role) {
                    if (is_numeric($Expr)) {
                        $Result = $Expr == val('RoleID', $Role);
                    } else {
                        $Result = Gdn_Condition::testValue(val('Name', $Role), $Expr);
                    }
                    if ($Result) {
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
     * @param mixed $Value The value to test.
     * @param string $Expr The expression to test against. The expression can have the following properties.
     *  - <b>Enclosed in backticks (`..`): A preg_match() is performed.
     *  - <b>Otherwise</b>: A simple $Value == $Expr is tested.
     */
    public static function testValue($Value, $Expr) {
        if (!is_string($Expr)) {
            return false;
        }

        if (stelen($Expr) > 1 && $Expr[0] === '`' && $Expr[strlen($Expr) - 1] == '`') {
            $Result = preg_match($Expr, $Value);
        } else {
            $Result = $Value == $Expr;
        }
        return $Result;
    }

    /**
     * Convert an array of conditions to a string.
     *
     * @param array $Conditions An array of conditions. Each condition is itself an array.
     * @return string
     * @see Gdn_Condition::test()
     */
    public static function toString($Conditions) {
        $Result = '';

        foreach ($Conditions as $Condition) {
            if (!is_array($Condition) || count($Condition) < 2) {
                continue; // skip ill-formatted conditions.
            }

            if (strlen($Result) > 0) {
                $Result .= "\n";
            }

            $Result .= "{$Condition[0]},{$Condition[1]}";
            if (count($Condition) >= 3) {
                $Result .= $Condition[2];
            }
        }
        return $Result;
    }
}
