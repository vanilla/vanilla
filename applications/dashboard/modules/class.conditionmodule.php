<?php
/**
 * Condition module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param null $value
     * @return array|null
     */
    public function conditions($value = null) {
        if (is_array($value)) {
            $this->_Conditions = $value;
        } elseif ($this->_Conditions === null) {
            if ($this->_Sender->Form->authenticatedPostBack()) {
                $this->_Conditions = $this->_FromForm();
            } else {
                $this->_Conditions = [];
            }
        }

        if ($value === true) {
            // Remove blank conditions from the array. This is used for saving.
            $result = [];
            foreach ($this->_Conditions as $condition) {
                if (count($condition) < 2 || !$condition[0]) {
                    continue;
                }
                $result[] = $condition;
            }
            return $result;
        }
        return $this->_Conditions;
    }

    public function toString() {
        $form = $this->_Sender->Form;
        $this->_Sender->addJsFile('condition.js');

        if ($form->authenticatedPostBack()) {
            // Grab the conditions from the form and convert them to the conditions array.
            $this->conditions($this->_FromForm());
        } else {
        }

        $this->Types = array_merge(['' => '('.sprintf(t('Select a %s'), t('Condition Type', 'Type')).')'], Gdn_Condition::allTypes());
        //die(print_r($this->Types));

        // Get all of the permissions that are valid for the permissions dropdown.
        $permissionModel = new PermissionModel();
        $permissions = $permissionModel->getGlobalPermissions(0);
        $permissions = array_keys($permissions);
        sort($permissions);
        $permissions = array_combine($permissions, $permissions);
        $permissions = array_merge(['' => '('.sprintf(t('Select a %s'), t('Permission')).')'], $permissions);
        $this->Permissions = $permissions;

        // Get all of the roles.
        $roleModel = new RoleModel();
        $roles = $roleModel->getArray();
        $roles = array_merge(['-' => '('.sprintf(t('Select a %s'), t('Role')).')'], $roles);
        $this->Roles = $roles;

        $this->Form = $form;
        return parent::toString();
    }

    /** Grab the values from the form into the conditions array. */
    protected function _FromForm() {
        $form = new Gdn_Form();
        $px = $this->Prefix;

        $types = (array)$form->getFormValue($px.'Type', []);
        $permissionFields = (array)$form->getFormValue($px.'PermissionField', []);
        $roleFields = (array)$form->getFormValue($px.'RoleField', []);
        $fields = (array)$form->getFormValue($px.'Field', []);
        $expressions = (array)$form->getFormValue($px.'Expr', []);

        $conditions = [];
        for ($i = 0; $i < count($types) - 1; $i++) {
            $condition = [$types[$i]];
            switch ($types[$i]) {
                case Gdn_Condition::PERMISSION:
                    $condition[1] = val($i, $permissionFields, '');
                    break;
                case Gdn_Condition::REQUEST:
                    $condition[1] = val($i, $fields, '');
                    $condition[2] = val($i, $expressions, '');
                    break;
                case Gdn_Condition::ROLE:
                    $condition[1] = val($i, $roleFields);
                    break;
                case '':
                    $condition[1] = '';
                    break;
                default:
                    continue;
            }
            $conditions[] = $condition;
        }
        return $conditions;
    }
}
