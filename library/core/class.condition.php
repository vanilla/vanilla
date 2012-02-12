<?php if (!defined('APPLICATION')) exit();

/**
 * DOCUMENT ME
 * 
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Condition {
   const PERMISSION = 'permission';
   const REQUEST = 'request';
   const ROLE = 'role';

   const COMPARE_AND = 'and';
   const COMPARE_OR = 'or';

   public $CompareType = self::COMPARE_OR;

   public static function AllTypes() {
      return array(self::PERMISSION => self::PERMISSION, self::ROLE => self::ROLE);
   }

   public static function Blank() {
      return array('', '', '');
   }


   /** Convert the condition values in a given string to a conditions array.
    *  This method is the opposite as Gdn_Condition::ToString().
    * @param string $String
    * @return array A conditions array suitable to be passed to Gdn_Condition::Test().
    * @see Gdn_Condition::ToString().
    */
   public static function FromString($String) {
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

   /** Test an array of conditions. This method only returns if every condition in the array is true.
    *
    * @param array $Conditions And array of conditons where each condition is itself an array with the following items:
    *  - 0: The type of condition. See the constants in Gdn_Condition for more information.
    *  - 1: The field to look at.
    *  - 2: The expression to test against (optional).
    * @return bool
    */
   public static function Test($Conditions) {
      if (!is_array($Conditions))
         return FALSE;

      foreach ($Conditions as $Condition) {
         if (!is_array($Condition) || count($Condition) < 2)
            continue;
         
         $Expr = isset($Condition[2]) ? $Condition[2] : NULL;

         $Test = Gdn_Condition::TestOne($Condition[0], $Condition[1], $Expr);
         if (!$Test && $this->CompareType == self::COMPARE_AND)
            return FALSE;
         if ($Test && $this->CompareType == self::COMPARE_OR)
            return TRUE;
      }
      if ($this->CompareType == self::COMPARE_AND)
         return TRUE;
      else
         return FALSE;
   }

   /** Test an individual condition.
    *
    * @param string $Type One of the types in this condition.
    * @param string $Field The field to test against.
    * @param string $Expr The expression to test with.
    * @return bool
    */
   public static function TestOne($Type, $Field, $Expr = NULL) {
      switch (strtolower($Type)) {
         case PERMISSION:
            // Check to see if the user has the given permission.
            $Result = Gdn::Session()->CheckPermission($Field);
            if ($Value === FALSE)
               return !$Result;
            return $Result;
         case REQUEST:
            // See if the field is a specific value.
            switch (strtolower($Field)) {
               case 'path':
                  $Value = Gdn::Request()->Path();
                  break;
               default:
                  // See if the field is targetting a specific part of the request.
                  $Fields = explode('.', $Field, 2);
                  if (count($Fields) >= 2) {
                     $Value = Gdn::Request()->GetValueFrom($Fields[0], $Fields[1], NULL);
                  } else {
                     $Value = Gdn::Request()->GetValue($Field, NULL);
                  }
               break;
            }

            $Result = Gdn_Condition::TestValue($Value, $Expr);
            return $Result;
         case ROLE:
            // See if the user is in the given role.
            $RoleModel = new RoleModel();
            $Roles = $RoleModel->GetByUserID(Gdn::Session()->UserID)->ResultArray();
            foreach ($Roles as $Role) {
               if (is_numeric($Expr)) {
                  $Result = $Expr == GetValue('RoleID', $Role);
               } else {
                  $Result = Gdn_Condition::TestValue(GetValue('Name', $Role), $Expr);
               }
               if ($Result)
                  return TRUE;
            }
            return FALSE;
      }
      return FALSE;
   }

   /** Test a value against an expression.
    *
    * @param mixed $Value The value to test.
    * @param string $Expr The expression to test against. The expression can have the following properties.
    *  - <b>Enclosed in backticks (`..`): A preg_match() is performed.
    *  - <b>Otherwise</b>: A simple $Value == $Expr is tested.
    */
   public static function TestValue($Value, $Expr) {
      if (!is_string($Expr))
         return FALSE;

      if (stelen($Expr) > 1 && $Expr[0] === '`' && $Expr[strlen($Expr) - 1] == '`') {
         $Result = preg_match($Expr, $Value);
      } else {
         $Result = $Value == $Expr;
      }
      return $Result;
   }

   /** Convert an array of conditions to a string.
    *
    * @param array $Conditions An array of conditions. Each condition is itself an array.
    * @return string
    * @see Gdn_Condition::Test()
    */
   public static function ToString($Conditions) {
      $Result = '';

      foreach ($Conditions as $Condition) {
         if (!is_array($Condition) || count($Condition) < 2)
            continue; // skip ill-formatted conditions.

         if (strlen($Result) > 0)
            $Result .= "\n";

         $Result .= "{$Condition[0]},{$Condition[1]}";
         if (count($Condition) >= 3) {
            $Result .= $Condition[2];
         }
      }
      return $Result;
   }
}