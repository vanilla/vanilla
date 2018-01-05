<?php
/**
 * Class Gdn_ConfigurationModel
 *
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Represents and manages configuration data
 *
 * This generic model can be instantiated (with the configuration array
 * name it is intended to represent) and used directly, or it can be extended
 * and overridden for more complicated procedures related to different
 * configuration arrays.
 */
class Gdn_ConfigurationModel {

    /**
     * @var string The name of the configuration array that this model is intended to
     * represent. The default value assigned to $this->Name will be the name
     * that the model was instantiated with (defined in $this->__construct()).
     */
    public $Name;

    /** @var object An object that is used to manage and execute data integrity rules on this object. */
    public $Validation;

    /** @var object The actual array of data being worked on. */
    public $Data;

    /**
     * @var string A collection of Field => Values that will NOT be validated and WILL be
     * saved as long as validation succeeds. You can add to this collection with
     * $this->forceSetting();
     */
    private $_ForceSettings = [];

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $ConfigurationArrayName The name of the configuration array that is being manipulated.
     * @param object $validation
     */
    public function __construct($validation) {
        $this->Name = 'Configuration';
        $this->Data = [];
        $this->Validation = $validation;
    }

    /**
     * Allows the user to declare which values are being manipulated in the
     * $this->Name configuration array.
     *
     * @param mixed $fieldName The name of the field (or array of field names) to ensure.
     */
    public function setField($fieldName) {
        $config = Gdn::factory(Gdn::AliasConfig);
        if (is_array($fieldName) === false) {
            $fieldName = [$fieldName];
        }

        foreach ($fieldName as $index => $value) {
            if (is_numeric($index)) {
                $nameKey = $value;
                $default = '';
            } else {
                $nameKey = $index;
                $default = $value;
            }
            /*
            if ($this->Name != 'Configuration')
               $Name = $NameKey;
            else
               $Name = $this->Name.'.'.$NameKey;
            */

            $this->Data[$nameKey] = $config->get($nameKey, $default);
        }
    }

    /**
     * Adds a new Setting => Value pair that will NOT be validated and WILL be
     * saved to the configuration array.
     *
     * @param mixed $fieldName The name of the field (or array of field names) to save.
     * @param mixed $fieldValue The value of FieldName to be saved.
     */
    public function forceSetting($fieldName, $fieldValue) {
        $this->_ForceSettings[$fieldName] = $fieldValue;
    }

    /**
     * Takes an associative array and munges it's keys together with a dot
     * delimiter. For example:
     *  $array['Database']['Host'] = 'dbhost';
     *  ... becomes ...
     *  $array['Database.Host'] = 'dbhost';
     *
     * @param array $array The array to be normalized.
     */
    private function normalizeArray($array) {
        $return = [];
        foreach ($array as $key => $value) {
            if (is_array($value) === true && array_key_exists(0, $value) === false) {
                foreach ($value as $k => $v) {
                    $return[$key.'.'.$k] = $v;
                }
            } else {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    /**
     * Takes a set of form data ($Form->_PostValues), validates them, and
     * inserts or updates them to the configuration file.
     *
     * @param array $formPostValues An associative array of $Field => $Value pairs that represent data posted
     * from the form in the $_POST or $_GET collection.
     */
    public function save($formPostValues, $live = false) {
        // Fudge your way through the schema application. This will allow me to
        // force the validation object to expect the fieldnames contained in
        // $this->Data.
        $this->Validation->setSchema($this->Data);
        // Validate the form posted values
        if ($this->Validation->validate($formPostValues)) {
            // Merge the validation fields and the forced settings into a single array
            $settings = $this->Validation->validationFields();
            if (is_array($this->_ForceSettings)) {
                $settings = mergeArrays($settings, $this->_ForceSettings);
            }

            $saveResults = saveToConfig($settings);

            // If the Live flag is true, set these in memory too
            if ($saveResults && $live) {
                Gdn::config()->set($settings, true);
            }

            return $saveResults;
        } else {
            return false;
        }
    }

    /**
     * A convenience method to check that the form-posted data is valid; just
     * in case you don't want to jump directly to the save if the data *is* valid.
     *
     * @param string $formPostValues
     * @return bool
     */
    public function validate($formPostValues) {
        $this->Validation->setSchema($this->Data);
        // Validate the form posted values
        return $this->Validation->validate($formPostValues);
    }

    /**
     * Returns the $this->Validation->validationResults() array.
     */
    public function validationResults() {
        return $this->Validation->results();
    }
}
