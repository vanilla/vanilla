<?php
/**
 * Configuration module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.2
 */

/**
 * This class gives a simple way to load/save configuration settings.
 *
 * To use this module you must:
 *  1. Call schema() to set the config fields you are using.
 *  2. Call initialize() within the controller to load/save the data.
 *  3. Do one of the following:
 *   a) Call the controller's render() method and call render() somewhere inside of the view.
 *   b) Call this object's renderAll() method within the view if you don't want to customize the view any further.
 */
class ConfigurationModule extends Gdn_Module {

    /** @var bool Whether or not the view is rendering the entire page. */
    public $RenderAll = false;

    /** @var array A definition of the data that this will manage. */
    protected $_Schema;

    /** @var ConfigurationModule */
    public $ConfigurationModule = null;

    /**
     *
     *
     * @param Gdn_Controller $Controller The controller using this model.
     */
    public function __construct($sender = null) {
        parent::__construct($sender);

        if (property_exists($sender, 'Form')) {
            $this->form($sender->Form);
        }

        $this->ConfigurationModule = $this;
    }

    /**
     *
     *
     * @return Gdn_Controller
     */
    public function controller() {
        return $this->_Sender;
    }

    /**
     *
     *
     * @param Gdn_Form $newValue
     * @return Gdn_Form
     */
    public function form($newValue = null) {
        static $form = null;

        if ($newValue !== null) {
            $form = $newValue;
        } elseif ($form === null)
            $form = new Gdn_Form('', 'bootstrap');

        return $form;
    }

    /**
     *
     *
     * @return bool
     */
    public function hasFiles() {
        static $hasFiles = null;

        if ($hasFiles === null) {
            $hasFiles = false;
            foreach ($this->schema() as $k => $row) {
                if (strtolower(val('Control', $row)) == 'imageupload') {
                    $hasFiles = true;
                    break;
                }
            }
        }
        return $hasFiles;
    }

    /**
     *
     *
     * @param null $schema
     * @throws Exception
     */
    public function initialize($schema = null) {
        if ($schema !== null) {
            $this->schema($schema);
        }

        /** @var Gdn_Form $Form */
        $form = $this->form();

        if ($form->authenticatedPostBack()) {
            // Grab the data from the form.
            $data = [];
            $post = $form->formValues();

            foreach ($this->_Schema as $row) {
                $name = $row['Name'];
                $config = $row['Config'];

                // For API calls make this a sparse save.
                if ($this->controller()->deliveryType() === DELIVERY_TYPE_DATA && !array_key_exists($name, $post)) {
                    continue;
                }

                if (strtolower(val('Control', $row)) == 'imageupload') {
                    $options = arrayTranslate($row, ['Prefix', 'Size']);
                    if (val('OutputType', $row, false)) {
                        $options['OutputType'] = val('OutputType', $row);
                    }
                    if (val('Crop', $row, false)) {
                        $options['Crop'] = val('Crop', $row);
                    }

                    // Old image to clean!
                    $options['CurrentImage'] = c($name, false);

                    // Save the new image and clean up the old one.
                    $form->saveImage($name, $options);
                }

                $value = $form->getFormValue($name);

                // Trim all incoming values by default.
                if (val('Trim', $row, true)) {
                    $value = trim($value);
                }

                if ($value == val('Default', $value, '')) {
                    $value = '';
                }

                $data[$config] = $value;
                $this->controller()->setData($name, $value);
            }

            // Halt the save if we've had errors assigned.
            if ($form->errorCount() == 0) {
                // Save it to the config.
                saveToConfig($data, ['RemoveEmpty' => true]);
                $this->_Sender->informMessage(t('Saved'));
            }
        } else {
            // Load the form data from the config.
            $data = [];
            foreach ($this->_Schema as $row) {
                $data[$row['Name']] = c($row['Config'], val('Default', $row, ''));
            }
            $form->setData($data);
            $this->controller()->Data = array_merge($this->controller()->Data, $data);
        }
    }

    /**
     *
     *
     * @param $schemaRow
     * @return bool|mixed|string
     */
    public function labelCode($schemaRow) {
        if (isset($schemaRow['LabelCode'])) {
            return $schemaRow['LabelCode'];
        }

        if (strpos($schemaRow['Name'], '.') !== false) {
            $labelCode = trim(strrchr($schemaRow['Name'], '.'), '.');
        } else {
            $labelCode = $schemaRow['Name'];
        }

        // Split camel case labels into seperate words.
        $labelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $labelCode);
        $labelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $labelCode);
        $labelCode = trim($labelCode);

        $labelCode = stringEndsWith($labelCode, " ID", true, true);

        return $labelCode;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function renderAll() {
        $this->RenderAll = true;
        $controller = $this->controller();
        $controller->ConfigurationModule = $this;

        $controller->render($this->fetchViewLocation());
        $this->RenderAll = false;
    }

    /**
     * Set the data definition to load/save from the config.
     *
     * @param array $def A list of fields from the config that this form will use.
     */
    public function schema($def = null) {
        if ($def !== null) {
            $schema = [];

            foreach ($def as $key => $value) {
                $row = ['Name' => '', 'Type' => 'string', 'Control' => 'TextBox', 'Options' => []];

                if (is_numeric($key)) {
                    $row['Name'] = $value;
                } elseif (is_string($value)) {
                    $row['Name'] = $key;
                    $row['Type'] = $value;
                } elseif (is_array($value)) {
                    $row['Name'] = $key;
                    $row = array_merge($row, $value);
                } else {
                    $row['Name'] = $key;
                }
                touchValue('Config', $row, $row['Name']);
                $schema[] = $row;
            }
            $this->_Schema = $schema;
        }
        return $this->_Schema;
    }
}
