<?php
/**
 * Condition module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

class ConditionModule extends Gdn_Module {

    /** @var null  */
    protected $_Conditions = null;

    /** @var string  */
    public $Prefix = 'Cond';

    /**
     *
     *
     * @param null $Value
     * @return array|null
     */
    public function Conditions($Value = null) {
        if (is_array($Value)) {
            $this->_Conditions = $Value;
        } elseif ($this->_Conditions === null) {
            if ($this->_Sender->Form->AuthenticatedPostBack()) {
                $this->_Conditions = $this->_FromForm();
            } else {
                $this->_Conditions = array();
            }
        }

        if ($Value === true) {
            // Remove blank conditions from the array. This is used for saving.
            $Result = array();
            foreach ($this->_Conditions as $Condition) {
                if (count($Condition) < 2 || !$Condition[0]) {
                    continue;
                }
                $Result[] = $Condition;
            }
            return $Result;
        }
        return $this->_Conditions;
    }

    public function ToString() {
        $Form = $this->_Sender->Form;
        $this->_Sender->AddJsFile('condition.js');

        if ($Form->AuthenticatedPostBack()) {
            // Grab the conditions from the form and convert them to the conditions array.
            $this->Conditions($this->_FromForm());
        } else {
        }

        $this->Types = array_merge(array('' => '('.sprintf(T('Select a %s'), T('Condition Type', 'Type')).')'), Gdn_Condition::AllTypes());
        //die(print_r($this->Types));

        // Get all of the permissions that are valid for the permissions dropdown.
        $PermissionModel = new PermissionModel();
        $Permissions = $PermissionModel->GetGlobalPermissions(0);
        $Permissions = array_keys($Permissions);
        sort($Permissions);
        $Permissions = array_combine($Permissions, $Permissions);
        $Permissions = array_merge(array('' => '('.sprintf(T('Select a %s'), T('Permission')).')'), $Permissions);
        $this->Permissions = $Permissions;

        // Get all of the roles.
        $RoleModel = new RoleModel();
        $Roles = $RoleModel->GetArray();
        $Roles = array_merge(array('-' => '('.sprintf(T('Select a %s'), T('Role')).')'), $Roles);
        $this->Roles = $Roles;

        $this->Form = $Form;
        return parent::ToString();
    }

    /** Grab the values from the form into the conditions array. */
    protected function _FromForm() {
        $Form = new Gdn_Form();
        $Px = $this->Prefix;

        $Types = (array)$Form->GetFormValue($Px.'Type', array());
        $PermissionFields = (array)$Form->GetFormValue($Px.'PermissionField', array());
        $RoleFields = (array)$Form->GetFormValue($Px.'RoleField', array());
        $Fields = (array)$Form->GetFormValue($Px.'Field', array());
        $Expressions = (array)$Form->GetFormValue($Px.'Expr', array());

        $Conditions = array();
        for ($i = 0; $i < count($Types) - 1; $i++) {
            $Condition = array($Types[$i]);
            switch ($Types[$i]) {
                case Gdn_Condition::PERMISSION:
                    $Condition[1] = GetValue($i, $PermissionFields, '');
                    break;
                case Gdn_Condition::REQUEST:
                    $Condition[1] = GetValue($i, $Fields, '');
                    $Condition[2] = GetValue($i, $Expressions, '');
                    break;
                case Gdn_Condition::ROLE:
                    $Condition[1] = GetValue($i, $RoleFields);
                    break;
                case '':
                    $Condition[1] = '';
                    break;
                default:
                    continue;
            }
            $Conditions[] = $Condition;
        }
        return $Conditions;
    }
}
