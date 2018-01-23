<?php
/**
 * Gdn_Form.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Form validation layer
 *
 * Helps with the rendering of form controls that link directly to a data model.
 */
class Gdn_Form extends Gdn_Pluggable {
    /**
     * @var array
     */
    private $styles = [];

    /**
     * @var array All of the available styles.
     */
    private $allStyles = [
        'legacy' => [
            'bodybox' => 'TextBox BodyBox js-bodybox',
            'button' => 'Button',
            'button-element' => 'input',
            'checkbox' => 'CheckBoxLabel',
            'dropdown' => '',
            'file' => '',
            'radio' => 'RadioLabel',
            'textarea' => 'TextBox',
            'textbox' => 'InputBox',
            'input-wrap' => 'TextBoxWrapper',
            'form-group' => '',
            'form-footer' => 'Buttons'
        ],
        'bootstrap' => [
            'default' => 'form-control',
            'bodybox' => 'form-control js-bodybox',
            'button' => 'btn btn-primary',
            'button-element' => 'button',
            'checkbox' => '',
            'checkbox-container' => 'checkbox',
            'checkbox-inline' => 'checkbox-inline',
            'file' => 'form-control-file',
            'inputbox' => 'form-control',
            'textbox' => 'form-control',
            'popup' => 'js-popup',
            'primary' => 'btn-primary',
            'radio' => '',
            'radio-container' => 'radio',
            'smallbutton' => 'btn btn-sm',
            'textarea' => 'form-control',
            'dropdown' => 'form-control',
            'input-wrap' => 'input-wrap',
            'form-group' => 'form-group',
            'form-footer' => 'js-modal-footer form-footer'
        ]
    ];

    /** @var string Action with which the form should be sent. */
    public $Action = '';

    /** @var string Class name to assign to form elements with errors when InlineErrors is enabled. */
    public $ErrorClass = 'Error';

    /** @var array Associative array of hidden inputs with their "Name" attribute as the key. */
    public $HiddenInputs;

    /**
     * @var string All form-related elements (form, input, select, textarea, [etc] will have
     *    this value prefixed on their ID attribute. Default is "Form_". If the
     *    id value is overridden with the Attribute collection for an element, this
     *    value will not be used.
     */
    public $IDPrefix = 'Form_';

    /** @var string Form submit method. Options are 'post' or 'get'. */
    public $Method = 'post';

    /**
     * @var array Associative array containing the key => value pairs being placed in the
     *    controls returned by this object. Assigned by $this->open() or $this->setData().
     */
    protected $_DataArray;

    /** @var bool Whether to display inline errors with form elements. Set with showErrors() and hideErrors(). */
    protected $_InlineErrors = false;

    /** @var object Model that enforces data rules on $this->_DataArray. */
    protected $_Model;

    /**
     * @var array Associative array of $FieldName => $ValidationFunctionName arrays that
     *    describe how each field specified failed validation.
     */
    protected $_ValidationResults = [];

    /**
     * @var array $Field => $Value pairs from the form in the $_POST or $_GET collection
     *    (depending on which method was specified for sending form data in $this->Method).
     *    Populated & accessed by $this->formValues().
     *    Values can be retrieved with $this->getFormValue($FieldName).
     */
    public $_FormValues;

    /**
     * @var array Collection of IDs that have been created for form elements. This
     *    private property is used to record all IDs so that duplicate IDs are not
     *    added to the screen.
     */
    private $_IDCollection = [];

    /**
     * @var array An array of ID counters so that we don't have ID clashes.
     */
    private static $idCounters = [];

    /**
     * Constructor
     *
     * @param string $tableName
     * @param string $style The style key to use.
     */
    public function __construct($tableName = '', $style = '') {
        if ($tableName != '') {
            $tableModel = new Gdn_Model($tableName);
            $this->setModel($tableModel);
        }

        if ($style === '') {
            $themeInfo = Gdn::themeManager()->getThemeInfo(Gdn::themeManager()->currentTheme());
            $style = val('ControlStyle', $themeInfo);
        }

        $this->setStyles($style);

        // Get custom error class
        $this->ErrorClass = c('Garden.Forms.InlineErrorClass', 'Error');

        parent::__construct();
    }

    /**
     * Backwards compatibility getter.
     *
     * @param strig $name The property to get.
     * @return mixed Returns the value of the property.
     */
    public function __get($name) {
        if ($name === 'InputPrefix') {
            trigger_error("Gdn_Form->InputPrefix is deprecated", E_USER_DEPRECATED);
        }
        return null;
    }

    /**
     * Backwards compatibility setter.
     *
     * @param string $name The name of the property to set.
     * @param mixed $value The new value of the property.
     */
    public function __set($name, $value) {
        if ($name === 'InputPrefix') {
            trigger_error("Gdn_Form->InputPrefix is deprecated", E_USER_DEPRECATED);
        }
        $this->$name = $value;
    }


    /// =========================================================================
    /// UI Components: Methods that return XHTML form elements.
    /// =========================================================================

    /**
     * Add ErrorClass to Attributes['class'].
     *
     * @since 2.0.18
     * @access public
     *
     * @param array $attributes Field attributes passed by reference (property => value).
     */
    public function addErrorClass(&$attributes) {
        if (isset($attributes['class'])) {
            $attributes['class'] .= ' '.$this->ErrorClass;
        } else {
            $attributes['class'] = $this->ErrorClass;
        }
    }


    /**
     * Set the styles to use when outputting controls.
     *
     * @param string $name The name of the style. Currently this should be **legacy** or **bootstrap**.
     * @return bool Returns **true** if the styles were set or **false** otherwise.
     */
    public function setStyles($name) {
        if (isset($this->allStyles[$name])) {
            $this->styles = $this->allStyles[$name];
            return true;
        } else {
            $this->styles = $this->allStyles['legacy'];
            return false;
        }
    }

    /**
     * Get a style element for the form.
     *
     * @param string $item The item, such as the element name or whatnot.
     * @param null $default The default. If this isn't supplied then the "default" class will be returned.
     * @return string Returns the element.
     */
    public function getStyle($item, $default = null) {
        $item = strtolower($item);

        if (isset($this->styles[$item])) {
            return $this->styles[$item];
        } elseif ($default !== null) {
            return $default;
        } elseif (isset($this->styles['default'])) {
            return $this->styles['default'];
        } else {
            return '';
        }
    }

    /**
     * Translate old CSS classes using the style array.
     *
     * @param string|string[] $classes The classes to translate.
     * @return string Returns the translated class string.
     */
    private function translateClasses($classes) {
        if (is_string($classes)) {
            $parts = explode(' ', trim($classes));
        } elseif (is_array($classes)) {
            $parts = $classes;
        } else {
            return '';
        }
        $classes = [];
        foreach ($parts as $part) {
            if (!empty($part)) {
                $classes[] = $this->getStyle($part, $part);
            }
        }

        return implode(' ', $classes);
    }

    /**
     * A special text box for formattable text.
     *
     * Formatting plugins like ButtonBar will auto-attach to this element.
     *
     * @param string $column
     * @param array $attributes
     * @since 2.1
     * @return string HTML element.
     */
    public function bodyBox($column = 'Body', $attributes = []) {
        touchValue('MultiLine', $attributes, true);
        touchValue('Wrap', $attributes, true);
        touchValue('class', $attributes, '');
        $attributes['class'] .= ' '.$this->getStyle('bodybox');

        $this->setValue('Format', val('Format', $attributes, $this->getValue('Format', Gdn_Format::defaultFormat())));

        $result = '<div class="bodybox-wrap">';

        // BeforeBodyBox
        $this->EventArguments['Table'] = val('Table', $attributes);
        $this->EventArguments['Column'] = $column;
        $this->EventArguments['Attributes'] = $attributes;
        $this->EventArguments['BodyBox'] =& $result;
        $this->fireEvent('BeforeBodyBox');
        ?>

        <div class="richEditor" aria-label="<?php echo t('Type your message');?>" aria-describedby="richEditor-description" role="textbox" aria-multiline="true">
            <p id="richEditor-description" class="sr-only">
                <?php echo t('Insert instructions for editor here'); ?>
            </p>
            <div class="richEditor-frame InputBox">
                <div class="richEditor-text" contenteditable="true">

                </div>

                <div class="richEditor-menu richEditorParagraphMenu">
                    <button class="richEditor-button richEditorParagraphMenu-handle" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title>¶</title>
                            <path fill="currentColor" fill-rule="evenodd" d="M15,6 L17,6 L17,18 L15,18 L15,6 Z M11,6 L13.0338983,6 L13.0338983,18 L11,18 L11,6 Z M11,13.8666667 C8.790861,13.8666667 7,12.1056533 7,9.93333333 C7,7.76101332 8.790861,6 11,6 C11,7.68571429 11,11.6190476 11,13.8666667 Z"/>
                        </svg>
                    </button>
                </div>

                <div class="richEditor-menu richEditorEmbedMenu">
                    <ul class="richEditor-menuItems" role="menubar" aria-label="<?php echo t('Inline Level Formatting Menu'); ?>">
                        <li class="richEditor-menuItem" role="menuitem">
                            <button class="richEditor-button" type="button">
                                <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                                    <title><?php echo t('Emoji'); ?></title>
                                    <path fill="currentColor" d="M12,4 C7.58168889,4 4,7.58168889 4,12 C4,16.4181333 7.58168889,20 12,20 C16.4183111,20 20,16.4181333 20,12 C20,7.58168889 16.4183111,4 12,4 Z M12,18.6444444 C8.33631816,18.6444444 5.35555556,15.6636818 5.35555556,12 C5.35555556,8.33631816 8.33631816,5.35555556 12,5.35555556 C15.6636818,5.35555556 18.6444444,8.33631816 18.6444444,12 C18.6444444,15.6636818 15.6636818,18.6444444 12,18.6444444 Z M10.7059556,10.2024889 C10.7059556,9.51253333 10.1466667,8.95324444 9.45671111,8.95324444 C8.76675556,8.95324444 8.20746667,9.51253333 8.20746667,10.2024889 C8.20746667,10.8924444 8.76675556,11.4517333 9.45671111,11.4517333 C10.1466667,11.4517333 10.7059556,10.8924444 10.7059556,10.2024889 Z M14.5432889,8.95306667 C13.8533333,8.95306667 13.2940444,9.51235556 13.2940444,10.2023111 C13.2940444,10.8922667 13.8533333,11.4515556 14.5432889,11.4515556 C15.2332444,11.4515556 15.7925333,10.8922667 15.7925333,10.2023111 C15.7925333,9.51235556 15.2332444,8.95306667 14.5432889,8.95306667 Z M14.7397333,14.1898667 C14.5767111,14.0812444 14.3564444,14.1256889 14.2471111,14.2883556 C14.2165333,14.3336889 13.4823111,15.4012444 11.9998222,15.4012444 C10.5198222,15.4012444 9.7856,14.3374222 9.75271111,14.2885333 C9.64444444,14.1256889 9.42471111,14.0803556 9.2608,14.1884444 C9.09688889,14.2963556 9.05155556,14.5169778 9.15964444,14.6810667 C9.19804444,14.7393778 10.1242667,16.1125333 11.9998222,16.1125333 C13.8752,16.1125333 14.8014222,14.7395556 14.84,14.6810667 C14.9477333,14.5173333 14.9027556,14.2983111 14.7397333,14.1898667 Z"/>
                                </svg>
                            </button>
                        </li>
                        <li class="richEditor-menuItem" role="menuitem">
                            <button class="richEditor-button" type="button">
                                <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                                    <title><?php echo t('Image'); ?></title>
                                    <path fill="currentColor" fill-rule="nonzero" d="M3,5 L3,19 L21,19 L21,5 L3,5 Z M3,4 L21,4 C21.5522847,4 22,4.44771525 22,5 L22,19 C22,19.5522847 21.5522847,20 21,20 L3,20 C2.44771525,20 2,19.5522847 2,19 L2,5 C2,4.44771525 2.44771525,4 3,4 Z M4,18 L20,18 L20,13.7142857 L15.2272727,7.42857143 L10.5,13.7142857 L7.5,11.5 L4,16.5510204 L4,18 Z M7.41729323,10.2443609 C8.24572036,10.2443609 8.91729323,9.57278803 8.91729323,8.7443609 C8.91729323,7.91593378 8.24572036,7.2443609 7.41729323,7.2443609 C6.58886611,7.2443609 5.91729323,7.91593378 5.91729323,8.7443609 C5.91729323,9.57278803 6.58886611,10.2443609 7.41729323,10.2443609 Z"/>
                                </svg>
                            </button>
                        </li>

                        <li class="richEditor-menuItem" role="menuitem">
                            <button class="richEditor-button" type="button">
                                <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                                    <title><?php echo t('HTML View'); ?></title>
                                    <path d="M4,5a.944.944,0,0,0-1,.875v12.25A.944.944,0,0,0,4,19H20a.944.944,0,0,0,1-.875V5.875A.944.944,0,0,0,20,5ZM4,4H20a1.9,1.9,0,0,1,2,1.778V18.222A1.9,1.9,0,0,1,20,20H4a1.9,1.9,0,0,1-2-1.778V5.778A1.9,1.9,0,0,1,4,4ZM9.981,16.382l-4.264-3.7V11.645L9.981,7.45V9.126l-3.2,2.958,3.2,2.605Zm4.326-1.693,3.2-2.605-3.2-2.958V7.45l4.265,4.195v1.041l-4.265,3.7Z" style="fill: currentColor"/>
                                </svg>
                            </button>
                        </li>



                        <li class="richEditor-menuItem isRightAligned" role="menuitem">
                            <button class="richEditor-button" type="button">
                                <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                                    <title><?php echo t('Help'); ?></title>
                                    <path fill="currentColor" d="M12,19 C15.8659932,19 19,15.8659932 19,12 C19,8.13400675 15.8659932,5 12,5 C8.13400675,5 5,8.13400675 5,12 C5,15.8659932 8.13400675,19 12,19 Z M12,20 C7.581722,20 4,16.418278 4,12 C4,7.581722 7.581722,4 12,4 C16.418278,4 20,7.581722 20,12 C20,16.418278 16.418278,20 12,20 Z M11.1336706,13.4973545 L11.1336706,13.1587302 C11.1336706,12.7707212 11.2042167,12.4479731 11.3453108,12.1904762 C11.486405,11.9329793 11.7333161,11.666668 12.0860516,11.3915344 C12.5058068,11.0599631 12.7765272,10.8024701 12.8982209,10.6190476 C13.0199146,10.4356252 13.0807606,10.2169325 13.0807606,9.96296296 C13.0807606,9.66666519 12.9819961,9.43915423 12.7844643,9.28042328 C12.5869324,9.12169233 12.3029847,9.04232804 11.9326124,9.04232804 C11.5975138,9.04232804 11.2871112,9.08994661 11.0013955,9.18518519 C10.7156798,9.28042376 10.437023,9.39506106 10.1654167,9.52910053 L9.72097222,8.5978836 C10.4370252,8.19929254 11.2042133,8 12.0225595,8 C12.713921,8 13.2624164,8.16931048 13.6680622,8.50793651 C14.0737079,8.84656254 14.2765278,9.31393 14.2765278,9.91005291 C14.2765278,10.1746045 14.2377275,10.4100519 14.1601257,10.6164021 C14.0825239,10.8227524 13.9652411,11.0193994 13.8082738,11.2063492 C13.6513065,11.393299 13.3805861,11.6366828 12.9961045,11.9365079 C12.6680605,12.1940048 12.448486,12.4074066 12.3373743,12.5767196 C12.2262627,12.7460326 12.1707077,12.9735435 12.1707077,13.2592593 L12.1707077,13.4973545 L11.1336706,13.4973545 Z M10.9167394,15.1851852 C10.9167394,14.6525547 11.1759961,14.3862434 11.6945172,14.3862434 C11.9484867,14.3862434 12.1424883,14.4559076 12.2765278,14.5952381 C12.4105672,14.7345686 12.477586,14.9312157 12.477586,15.1851852 C12.477586,15.4356274 12.4096854,15.6340381 12.2738823,15.7804233 C12.1380791,15.9268085 11.9449594,16 11.6945172,16 C11.444075,16 11.2518371,15.9285721 11.1177976,15.7857143 C10.9837581,15.6428564 10.9167394,15.4426821 10.9167394,15.1851852 Z"/>
                                </svg>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <hr/>

        <h2>Paragraph Level Formatting Menu</h2>

        <div class="richEditor-menu">
            <ul class="richEditor-menuItems MenuItems" role="menubar" aria-label="<?php echo t('Paragraph Level Formatting Menu'); ?>">
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Title'); ?></title>
                            <path d="M12.3,17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607V7.006H12.3Zm5.944,0H16.637V10.547q0-1.155.055-1.832-.157.163-.387.362t-1.534,1.258l-.807-1.019L16.9,7.006h1.34Z" style="fill: currentColor"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Subtitle'); ?></title>
                            <path d="M12.3,17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607V7.006H12.3Zm8,0H13.526V15.783L16.1,13.192a22.007,22.007,0,0,0,1.514-1.657,3.978,3.978,0,0,0,.543-.92,2.475,2.475,0,0,0,.171-.923,1.4,1.4,0,0,0-.407-1.066,1.557,1.557,0,0,0-1.124-.39,3,3,0,0,0-1.111.212,5.239,5.239,0,0,0-1.241.766l-.868-1.06a5.612,5.612,0,0,1,1.62-1,4.744,4.744,0,0,1,1.675-.294,3.294,3.294,0,0,1,2.235.728,2.46,2.46,0,0,1,.841,1.959,3.453,3.453,0,0,1-.242,1.285,5.212,5.212,0,0,1-.746,1.254,17.041,17.041,0,0,1-1.671,1.747l-1.736,1.682v.068H20.3Z" style="fill: currentColor"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Quote'); ?></title>
                            <path d="M10.531,17.286V12.755H8.122a9.954,9.954,0,0,1,.1-1.408,4.22,4.22,0,0,1,.388-1.286,2.62,2.62,0,0,1,.735-.918A1.815,1.815,0,0,1,10.49,8.8V6.755a3.955,3.955,0,0,0-2,.49A4.164,4.164,0,0,0,7.082,8.551a5.84,5.84,0,0,0-.817,1.9A9.65,9.65,0,0,0,6,12.755v4.531Zm7.469,0V12.755H15.592a9.954,9.954,0,0,1,.1-1.408,4.166,4.166,0,0,1,.388-1.286,2.606,2.606,0,0,1,.734-.918A1.819,1.819,0,0,1,17.959,8.8V6.755a3.958,3.958,0,0,0-2,.49,4.174,4.174,0,0,0-1.408,1.306,5.86,5.86,0,0,0-.816,1.9,9.649,9.649,0,0,0-.266,2.306v4.531Z" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Paragraph Code Block'); ?></title>
                            <path fill="currentColor" fill-rule="evenodd" d="M9.11588626,16.5074223 L3.14440918,12.7070466 L3.14440918,11.6376386 L9.11588626,7.32465415 L9.11588626,9.04808032 L4.63575044,12.0883808 L9.11588626,14.7663199 L9.11588626,16.5074223 Z M14.48227,5.53936141 L11.1573124,18.4606386 L9.80043634,18.4606386 L13.131506,5.53936141 L14.48227,5.53936141 Z M15.1729321,14.7663199 L19.6530679,12.0883808 L15.1729321,9.04808032 L15.1729321,7.32465415 L21.1444092,11.6376386 L21.1444092,12.7070466 L15.1729321,16.5074223 L15.1729321,14.7663199 Z"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Spoiler'); ?></title>
                            <path d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z" style="fill: currentColor;"/>
                            <polygon points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </div>


        <hr/>

        <h2>Inline Level Formatting Menu</h2>

        <div class="richEditor-menu richEditorInlineMenu">
            <ul class="richEditor-menuItems MenuItems" role="menubar" aria-label="<?php echo t('Inline Level Formatting Menu'); ?>">
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Bold'); ?></title>
                            <path d="M6.511,18v-.62a4.173,4.173,0,0,0,.845-.093.885.885,0,0,0,.736-.79,5.039,5.039,0,0,0,.063-.884V8.452a6.585,6.585,0,0,0-.047-.876,1.116,1.116,0,0,0-.194-.527.726.726,0,0,0-.4-.263,3.658,3.658,0,0,0-.674-.1v-.62h4.975a7.106,7.106,0,0,1,3.6.752A2.369,2.369,0,0,1,16.68,8.964q0,1.843-2.651,2.6v.062a4.672,4.672,0,0,1,1.542.24,3.39,3.39,0,0,1,1.171.674,3.036,3.036,0,0,1,.744,1.023,3.125,3.125,0,0,1,.263,1.287,2.49,2.49,0,0,1-.38,1.379,3.05,3.05,0,0,1-1.092.992,7.794,7.794,0,0,1-3.8.775Zm6.076-.945q2.5,0,2.5-2.248a2.3,2.3,0,0,0-.9-2.015,3.073,3.073,0,0,0-1.2-.465,9.906,9.906,0,0,0-1.806-.139h-.744v3.1a1.664,1.664,0,0,0,.5,1.364A2.659,2.659,0,0,0,12.587,17.055Zm-1.24-5.8a4.892,4.892,0,0,0,1.21-.131,2.69,2.69,0,0,0,.868-.38,1.8,1.8,0,0,0,.743-1.6,2.107,2.107,0,0,0-.557-1.635,2.645,2.645,0,0,0-1.8-.5h-1.1q-.279,0-.279.264v3.983Z" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Italic'); ?></title>
                            <path d="M11.472,15.4a4.381,4.381,0,0,0-.186,1.085.744.744,0,0,0,.333.713,2.323,2.323,0,0,0,1.077.186L12.51,18H7.566l.17-.62a3.8,3.8,0,0,0,.791-.07,1.282,1.282,0,0,0,.566-.271,1.62,1.62,0,0,0,.41-.558,5.534,5.534,0,0,0,.326-.93L11.642,8.7a5.332,5.332,0,0,0,.233-1.271.577.577,0,0,0-.349-.612,3.714,3.714,0,0,0-1.186-.132l.171-.62h5.038l-.171.62a3.058,3.058,0,0,0-.852.1,1.246,1.246,0,0,0-.59.38,2.578,2.578,0,0,0-.441.774,11.525,11.525,0,0,0-.4,1.287Z" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Strikethrough'); ?></title>
                            <path d="M12.258,13H6V12h4.2l-.05-.03a4.621,4.621,0,0,1-1.038-.805,2.531,2.531,0,0,1-.55-.892A3.285,3.285,0,0,1,8.4,9.2a3.345,3.345,0,0,1,.256-1.318,3.066,3.066,0,0,1,.721-1.046,3.242,3.242,0,0,1,1.1-.682,3.921,3.921,0,0,1,1.4-.24,3.641,3.641,0,0,1,1.271.217,4.371,4.371,0,0,1,1.194.7l.4-.7h.357l.171,3.085h-.574A3.921,3.921,0,0,0,13.611,7.32a2.484,2.484,0,0,0-1.7-.619,2.269,2.269,0,0,0-1.5.465,1.548,1.548,0,0,0-.558,1.255,1.752,1.752,0,0,0,.124.674,1.716,1.716,0,0,0,.4.574,4.034,4.034,0,0,0,.729.542,9.854,9.854,0,0,0,1.116.566,20.49,20.49,0,0,1,1.906.953q.232.135.435.27h4.6v1H15.675a2.263,2.263,0,0,1,.3.544,3.023,3.023,0,0,1,.186,1.093,3.236,3.236,0,0,1-1.177,2.541,4.014,4.014,0,0,1-1.334.721,5.393,5.393,0,0,1-1.7.256,4.773,4.773,0,0,1-1.588-.248,4.885,4.885,0,0,1-1.434-.837l-.434.76H8.132L7.9,14.358h.573a3.886,3.886,0,0,0,.411,1.255A3.215,3.215,0,0,0,10.7,17.155a3.872,3.872,0,0,0,1.294.21,2.786,2.786,0,0,0,1.813-.543,1.8,1.8,0,0,0,.667-1.473,1.752,1.752,0,0,0-.573-1.34,4.04,4.04,0,0,0-.83-.6Q12.723,13.217,12.258,13Z" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Paragraph Code Block'); ?></title>
                            <path fill="currentColor" fill-rule="evenodd" d="M9.11588626,16.5074223 L3.14440918,12.7070466 L3.14440918,11.6376386 L9.11588626,7.32465415 L9.11588626,9.04808032 L4.63575044,12.0883808 L9.11588626,14.7663199 L9.11588626,16.5074223 Z M14.48227,5.53936141 L11.1573124,18.4606386 L9.80043634,18.4606386 L13.131506,5.53936141 L14.48227,5.53936141 Z M15.1729321,14.7663199 L19.6530679,12.0883808 L15.1729321,9.04808032 L15.1729321,7.32465415 L21.1444092,11.6376386 L21.1444092,12.7070466 L15.1729321,16.5074223 L15.1729321,14.7663199 Z"/>
                        </svg>
                    </button>
                </li>
                <li class="richEditor-menuItem" role="menuitem">
                    <button class="richEditor-button" type="button">
                        <svg class="richEditorInline-icon" viewBox="0 0 24 24">
                            <title><?php echo t('Link'); ?></title>
                            <path d="M9.108,12.272a.731.731,0,0,0,.909.08l1.078.9a2.094,2.094,0,0,1-2.889.087l-2.4-2.019A2.089,2.089,0,0,1,5.443,8.4L6.892,6.679a2.088,2.088,0,0,1,2.942-.144l2.4,2.019a2.089,2.089,0,0,1,.362,2.924l-.1.114-1.073-.9.1-.114a.705.705,0,0,0-.192-.95l-2.4-2.019a.7.7,0,0,0-.968-.026L6.516,9.3a.7.7,0,0,0,.191.95Zm9.085,1.293a2.088,2.088,0,0,1,.362,2.924l-1.448,1.722a2.088,2.088,0,0,1-2.942.144l-2.4-2.019a2.1,2.1,0,0,1-.409-2.86l1.077.9a.73.73,0,0,0,.235.883l2.4,2.019a.7.7,0,0,0,.968.026l1.448-1.722a.7.7,0,0,0-.192-.95l-2.4-2.019a.7.7,0,0,0-.967-.026l-.1.115-1.072-.9.1-.115a2.087,2.087,0,0,1,2.942-.144ZM10.028,10.6a.466.466,0,0,1,.658-.057l3.664,3.082a.467.467,0,0,1,.057.658l-.308.366a.466.466,0,0,1-.658.057L9.776,11.626a.469.469,0,0,1-.057-.659Z" style="fill: currentColor;"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </div>
        <hr/>
        <h2>@mention menu</h2>
        <div class="richEditor-menu richEditorLinkMenu">
            <ul class="richEditor-menuItems MenuItems" role="menubar" aria-label="<?php echo t('@mention'); ?>">
                <li class="richEditor-menuItem" role="menuitem">
                    <a href="#" class="richEditorLinkMenu-link">
                        <span class="richEditorLinkMenu-photoWrap">
                            <img src="https://secure.gravatar.com/avatar/b0420af06d6fecc16fc88a88cbea8218/?default=https%3A%2F%2Fvanillicon.com%2Fb0420af06d6fecc16fc88a88cbea8218_200.png&amp;rating=g&amp;size=120" alt="Linc" class="richEditorLinkMenu-photo"/>
                        </span>
                        <span class="richEditorLinkMenu-userName">
                            <mark class="richEditorLinkMenu-mark">Fra</mark>nk
                        </span>
                    </a>
                </li>
            </ul>
        </div>
        <hr/>

        <?php
        // Only add the format if it was set on the form. This allows plugins to remove the format.
        if ($format = $this->getValue('Format')) {
            $attributes['format'] = htmlspecialchars($format);
            $this->setValue('Format', $attributes['format']);
            $result .= $this->hidden('Format');
        }

        $result .= $this->textBox($column, $attributes);

        $result .= '</div>';

        return $result;
    }

    /**
     * Returns XHTML for a button.
     *
     * @param string $buttonCode The translation code for the text on the button.
     * @param array $attributes An associative array of attributes for the button. Here is a list of
     * "special" attributes and their default values:
     * Attribute  Options                        Default
     * ------------------------------------------------------------------------
     * Type       The type of submit button      'submit'
     * Value      Ignored for $buttonCode        $buttonCode translated
     *
     * @return string
     */
    public function button($buttonCode, $attributes = []) {
        $type = arrayValueI('type', $attributes);
        if ($type === false) {
            $type = 'submit';
        }

        $cssClass = arrayValueI('class', $attributes);
        if ($cssClass === false) {
            $attributes['class'] = $this->getStyle('button');
        } else {
            $attributes['class'] = $this->translateClasses($attributes['class']);
        }

        $elem = $this->getStyle('button-element');

        $return = "<$elem type=\"$type\"";
        $return .= $this->_idAttribute($buttonCode, $attributes);
        $return .= $this->_nameAttribute($buttonCode, $attributes);
        $return .= $this->_attributesToString($attributes);

        if ($elem === 'button') {
            $return .= ' value="'.val('value', $attributes, $buttonCode).'">'.htmlspecialchars(t($buttonCode, val('value', $attributes))).'</button>';
        } else {
            $return .= ' value="'.t($buttonCode, val('value', $attributes)).'"';
            $return .= " />\n";
        }
        return $return;
    }

    /**
     * Return a linked that will look like a button.
     *
     * @param string $code The text of the anchor.
     * @param string $destination The URL path of the anchor.
     * @param array $attributes Additional attributes for the anchor.
     * @see anchor()
     */
    public function linkButton($code, $destination = '', $attributes = []) {
        if (empty($attributes['class'])) {
            $cssClass = $this->getStyle('button', '');
        } else {
            $cssClass = $this->translateClasses($attributes['class']);
            unset($attributes['class']);
        }

        $result = anchor(t($code), $destination, $cssClass, $attributes, true);
        return $result;
    }

    /**
     * Builds a color-picker form element. Accepts three-character hex values with or without the leading '#',
     * but the saved value will be coerced into a six-character hex code with the leading '#'. Also accepts
     * 'transparent', 'initial' or 'inherit'. Can be configured to accept an empty string if $options['AllowEmpty']
     * is set to true. The hex value to be saved is the value of the input with the color-picker-value class.
     *
     * @param string $fieldName Name of the field being posted with this input.
     * @param array $options Currently supports a key of 'AllowEmpty' which signifies whether to accept empty
     * values for the color picker
     * @return string The form element for a color picker.
     */
    public function color($fieldName, $options = []) {

        $allowEmpty = val('AllowEmpty', $options, false);

        Gdn::controller()->addJsFile('colorpicker.js');

        $valueAttributes['class'] = 'js-color-picker-value color-picker-value Hidden';
        $textAttributes['class'] = 'js-color-picker-text color-picker-text';
        $colorAttributes['class'] = 'js-color-picker-color color-picker-color';

        // Default starting color for color input. Color inputs require one, Chrome will throw a warning if one
        // doesn't exist. The javascript will override this.
        $colorAttributes['value'] = '#ffffff';

        $cssClass = 'js-color-picker color-picker input-group';
        $dataAttribute = $allowEmpty ? 'data-allow-empty="true"' : 'data-allow-empty="false"';

        return '<div id="'.$this->escapeFieldName($fieldName).'" class="'.$cssClass.'" '.$dataAttribute.'>'
        .$this->input($fieldName, 'text', $valueAttributes)
        .$this->input($fieldName.'-text', 'text', $textAttributes)
        .'<span class="js-color-picker-preview color-picker-preview"></span>'
        .$this->input($fieldName.'-color', 'color', $colorAttributes)
        .'</div>';
    }

    /**
     * Returns XHTML for a standard calendar input control.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     * should related directly to a field name in $this->_DataArray.
     * @param array $attributes An associative array of attributes for the input. ie. onclick, class, etc
     * @return string
     * @todo Create calendar helper
     */
    public function calendar($fieldName, $attributes = []) {
        // TODO: CREATE A CALENDAR HELPER CLASS AND LOAD/REFERENCE IT HERE.
        // THE CLASS SHOULD BE DECLARED WITH:
        //  if (!class_exists('Calendar') {
        // AT THE BEGINNING SO OTHERS CAN OVERRIDE THE DEFAULT CALENDAR WITH ONE
        // OF THEIR OWN.
        $class = arrayValueI(
            'class',
            $attributes,
            false
        );
        if ($class === false) {
            $attributes['class'] = 'DateBox';
        }

        // IN THE MEANTIME...
        return $this->input($fieldName, 'text', $attributes);
    }

    /**
     * Returns Captcha HTML & adds translations to document head.
     *
     * Events: BeforeCaptcha
     *
     * @return string
     */
    public function captcha() {
        $this->fireEvent('Captcha');
    }

    /**
     * Returns XHTML for a select list containing categories that the user has
     * permission to use.
     *
     * @param array $fieldName An array of category data to render.
     * @param array $options An associative array of options for the select. Here
     * is a list of "special" options and their default values:
     *
     *   Attribute     Options                        Default
     *   ------------------------------------------------------------------------
     *   Value         The ID of the category that    FALSE
     *                 is selected.
     *   IncludeNull   Include a blank row?           TRUE
     *   Context       A set of categories to         []
     *                 interset with the CategoryData
     *                 that is relative to the category
     *                 we're in.
     *   CategoryData  Custom set of categories to    CategoryModel::categories()
     *                 display.
     *
     * @return string
     */
    public function categoryDropDown($fieldName = 'CategoryID', $options = []) {

        $this->EventArguments['Options'] = &$options;
        $this->fireEvent('BeforeCategoryDropDown');

        $value = arrayValueI('Value', $options); // The selected category id
        $categoryData = val('CategoryData', $options);

        if (!$categoryData && val('Context', $options)) {
            $categoryData = val('Context', $options);
        } elseif ($categoryData && val('Context', $options)) {
            $categoryData = array_intersect_key($categoryData, val('Context', $options));
        }

        // Sanity check
        if (is_object($categoryData)) {
            $categoryData = (array)$categoryData;
        } elseif (!is_array($categoryData)) {
            $categoryData = [];
        }

        $permission = val('Permission', $options, 'add');

        // Grab the category data.
        if (!$categoryData) {
            $categoryData = CategoryModel::getByPermission(
                'Discussions.View',
                $value,
                val('Filter', $options, ['Archived' => 0]),
                val('PermFilter', $options, [])
            );
        }

        // Remove categories the user shouldn't see.
        $safeCategoryData = [];
        $discussionType = val('DiscussionType', $options);
        foreach ($categoryData as $categoryID => $category) {
            if ($value != $categoryID) {
                if ($category['CategoryID'] <= 0 || !$category['PermsDiscussionsView']) {
                    continue;
                }

                if ($category['Archived']) {
                    continue;
                }

                // Filter out categories that don't allow our discussion type, if specified
                if ($discussionType) {
                    $permissionCategory = CategoryModel::permissionCategory($category);
                    $allowedDiscussionTypes = CategoryModel::allowedDiscussionTypes($permissionCategory, $category);
                    if (!array_key_exists($discussionType, $allowedDiscussionTypes)) {
                        continue;
                    }
                }
            }

            $safeCategoryData[$categoryID] = $category;
        }
        unset($discussionType, $permissionCategory, $allowedDiscussionTypes);

        unset($options['Filter'], $options['PermFilter'], $options['Context'], $options['CategoryData']);

        if (!isset($options['class'])) {
            $options['class'] = $this->getStyle('dropdown');
        }

        // Opening select tag
        $return = '<select';
        $return .= $this->_idAttribute($fieldName, $options);
        $return .= $this->_nameAttribute($fieldName, $options);
        $return .= $this->_attributesToString($options);
        $return .= ">\n";

        // Get value from attributes
        if ($value === false) {
            $value = $this->getValue($fieldName);
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        // Prevent default $Value from matching key of zero
        $hasValue = ($value !== [false] && $value !== ['']) ? true : false;

        // Start with null option?
        $includeNull = val('IncludeNull', $options);
        if ($includeNull === true) {
            $return .= '<option value="">'.t('Select a category...').'</option>';
        } elseif (is_array($includeNull))
            $return .= "<option value=\"{$includeNull[0]}\">{$includeNull[1]}</option>\n";
        elseif ($includeNull)
            $return .= "<option value=\"\">$includeNull</option>\n";
        elseif (!$hasValue)
            $return .= '<option value=""></option>';

        // Show root categories as headings (ie. you can't post in them)?
        $doHeadings = val('Headings', $options, c('Vanilla.Categories.DoHeadings'));

        // If making headings disabled and there was no default value for
        // selection, make sure to select the first non-disabled value, or the
        // browser will auto-select the first disabled option.
        $forceCleanSelection = ($doHeadings && !$hasValue && !$includeNull);

        // Write out the category options
        if (is_array($safeCategoryData)) {
            foreach ($safeCategoryData as $categoryID => $category) {
                $depth = val('Depth', $category, 0);
                $disabled = (($depth == 1 && $doHeadings) || !$category['AllowDiscussions'] || val('DisplayAs', $category) != 'Discussions');
                $selected = in_array($categoryID, $value) && $hasValue;
                if ($forceCleanSelection && $depth > 1) {
                    $selected = true;
                    $forceCleanSelection = false;
                }

                if ($category['AllowDiscussions']) {
                    if ($permission == 'add' && !$category['PermsDiscussionsAdd']) {
                        $disabled = true;
                    }
                }

                $return .= '<option value="'.$categoryID.'"';
                if ($disabled) {
                    $return .= ' disabled="disabled"';
                } elseif ($selected) {
                    $return .= ' selected="selected"'; // only allow selection if NOT disabled
                }

                $name = htmlspecialchars(val('Name', $category, 'Blank Category Name'));
                if ($depth > 1) {
                    $name = str_repeat('&#160;', 4 * ($depth - 1)).$name;
                }

                $return .= '>'.$name."</option>\n";
            }
        }
        return $return.'</select>';
    }

    /**
     * Outputs a checkbox painted as a toggle. Includes label wrap id a label is given.
     *
     * @param string $fieldName The key name for the field.
     * @param string $label The label for the field.
     * @param array $attributes The attributes for the checkbox input.
     * @param string $info The label description.
     * @param bool $reverse Whether to reverse the representation of the toggle (positive value is on, neg value is off).
     * @return string And HTML-formatted form field for a toggle.
     */
    public function toggle($fieldName, $label = '', $attributes = [], $info = '', $reverse = false) {
        $value = arrayValueI('value', $attributes, true);
        $attributes['value'] = $value;
        if (stringEndsWith($fieldName, '[]')) {
            if (!isset($attributes['checked'])) {
                $getValue = $this->getValue(substr($fieldName, 0, -2));
                if (is_array($getValue) && in_array($value, $getValue)) {
                    $attributes['checked'] = 'checked';
                } elseif ($getValue == $value)
                    $attributes['checked'] = 'checked';
            }
        } else {
            if ($this->getValue($fieldName) == $value) {
                $attributes['checked'] = 'checked';
            }
        }

        if ($reverse) {
            if ($attributes['checked'] === 'checked') {
                unset($attributes['checked']);
            } else {
                $attributes['checked'] = 'checked';
            }
        }

        $id = arrayValueI('id', $attributes, $this->escapeID($fieldName, false));

        $attributes['aria-labelledby'] = 'label-'.$id;
        $attributes['class'] = 'toggle-input';
        $input = $this->input($fieldName, 'checkbox', $attributes);
        $toggleLabel = '<label for="'.$id.'"'.
            attribute('class', 'toggle').
            attribute('title', val('title', $attributes)) .'>';

        if ($info) {
            $info = '<div class="info">'.t($info).'</div>';
        }

        if ($label) {
            $toggle = '
                <div class="label-wrap-wide">
                    <div class="label label-'.$fieldName.'" id="'.$attributes['aria-labelledby'].'">'.t($label).'</div>'.
                    $info.'
                </div>
                <div class="input-wrap-right">
                    <div class="toggle-wrap">'.
                        $input.
                        $toggleLabel.'
                    </div>
                </div>';
        } else {
            $toggle = '<div class="toggle-wrap">'.$input.$toggleLabel.'</div>';
        }

        return $toggle;
    }

    /**
     * Renders a search form.
     *
     * @param string $field The search field, supported field names are 'search' or 'Keywords'
     * @param string $url The url to show the search results.
     * @param array $textBoxAttributes The attributes for the text box. Placeholders go here.
     * @param string $searchInfo The info to add under the search box, usually a result count.
     * @return string The rendered form.
     */
    public function searchForm($field, $url, $textBoxAttributes = [], $searchInfo = '') {
        return $this->open(['action' => url($url)]).
            $this->errors().
            $this->searchInput($field, $url, $textBoxAttributes, $searchInfo).
            $this->close();
    }


    /**
     * Renders a stylized search field. Requires dashboard.css to look as intended. Use with searchForm() to output an
     * entire search form.
     *
     * @param string $field The search field, supported field names are 'search' or 'Keywords'
     * @param string $url The url to show the search results.
     * @param array $textBoxAttributes The attributes for the text box. Placeholders go here.
     * @param string $searchInfo The info to add under the search box, usually a result count.
     * @param array $wrapperAttributes The attributes to add to the search wrapper div.
     * @return string The rendered search field.
     */
    public function searchInput($field, $url, $textBoxAttributes = [], $searchInfo = '', $wrapperAttributes = []) {
        $clear = '';
        $searchTermFound = false;
        $searchKeys = ['search', 'keywords'];

        $getValues = Gdn::request()->get();

        // Check to see if any values in the above array exist in the get request and if so, add a clear button.
        foreach ($getValues as $key => $value) {
            if (in_array(strtolower($key), $searchKeys)) {
                $searchTermFound = true;
            }
        }

        if ($searchTermFound) {
            $closeIcon = dashboardSymbol('close');
            $clear = '<a class="search-icon-wrap search-icon-clear-wrap" href="'.url($url).'">'.$closeIcon.'</a>';
        }

        if ($searchInfo) {
            $searchInfo = '<div class="info search-info">'.$searchInfo.'</div>';
        }

        $wrapperAttributes['class'] = val('class', $wrapperAttributes, '');
        $wrapperAttributes['class'] .= ' search-wrap input-wrap';
        $wrapperAttributesString = attribute($wrapperAttributes);

        return '
            <div '.$wrapperAttributesString.' role="search">
                <div class="search-icon-wrap search-icon-search-wrap">'.dashboardSymbol('search').'</div>'.
                $this->textBox($field, $textBoxAttributes).
                $this->button('Go', ['class' => 'search-submit']).
                $clear.
                $searchInfo.'
            </div>';
    }


    /**
     * Outputs a stylized file upload input. Requires dashboard.js and dashboard.css to look and work as intended.
     *
     * @param string $fieldName
     * @param array $attributes
     * @return string
     */
    public function fileUpload($fieldName, $attributes = []) {
        $id = arrayValueI('id', $attributes, $this->escapeID($fieldName, false));
        unset($attributes['id']);
        $attributes['class'] = val('class', $attributes, '');
        $attributes['class'] .=  " js-file-upload form-control";
        $attributes = $this->_attributesToString($attributes);

        $upload = '
            <label class="file-upload">
              <input type="file" name="'.$fieldName.'" id="'.$id.'" '.$attributes.'>
              <span class="file-upload-choose" data-placeholder="'.t('Choose').'">'.t('Choose').'</span>
              <span class="file-upload-browse">'.t('Browse').'</span>
            </label>';

        return $upload;
    }

    /**
     * Outputs a stylized file upload input with a input wrapper div. Requires dashboard.js and dashboard.css to look
     * and work as intended.
     *
     * @param string $fieldName
     * @param array $attributes
     * @return string
     */
    public function fileUploadWrap($fieldName, $attributes = []) {
        return '<div class="input-wrap">'.$this->fileUpload($fieldName, $attributes).'</div>';
    }


    /**
     * Outputs the entire form group with both the label and input. Adds an image preview and a link to delete the
     * current image. Handles the ajax clearing of the image preview on removal.
     * Requires dashboard.js and dashboard.css to look and work as intended.
     *
     * @param string $fieldName The form field name for the input.
     * @param string $label The label.
     * @param string $labelDescription The label description.
     * @param string $removeUrl The endpoint to remove the image.
     * @param array $options An array of options with the following keys:
     *      'CurrentImage' (string) The current image to preview.
     *      'RemoveText' (string) The text for the remove image anchor, defaults to t('Remove').
     *      'RemoveConfirmText' (string) The text for the confirm modal, defaults to t('Are you sure you want to do that?').
     *      'Tag' (string) The tag for the form-group. Defaults to li, but you may want a div or something.
     * @param array $attributes The html attributes to pass to the file upload function.
     * @return string

     */
    public function imageUploadPreview($fieldName, $label = '', $labelDescription = '', $removeUrl = '', $options = [], $attributes = []) {

        $imageWrapperId = slugify($fieldName).'-preview-wrapper';

        // Compile the data for our current image and current image removal.
        $currentImage = val('CurrentImage', $options, '');
        if ($currentImage === '') {
            $currentImage = $this->currentImage($fieldName);
        }
        $removeAttributes = [];
        $removeCurrentImage = '';

        if ($this->getValue($fieldName) && $removeUrl) {
            $removeText = val('RemoveText', $options, t('Remove'));
            if (val('RemoveConfirmText', $options, false)) {
                $removeAttributes['data-body'] = val('RemoveConfirmText', $options);
            }
            $removeCurrentImage = wrap(anchor($removeText, $removeUrl, 'js-modal-confirm', $removeAttributes), 'div');
        }

        if ($label) {
            $label = wrap($label, 'div', ['class' => 'label']);
        }

        if ($labelDescription) {
            $labelDescription = wrap($labelDescription, 'div', ['class' => 'info']);
        }

        $label = '
            <div class="label-wrap">'
                .$label
                .$labelDescription.'
                <div id="'.$imageWrapperId.'" class="js-image-preview-old">'
                    .$currentImage
                    .$removeCurrentImage.'
                </div>
                <div class="js-image-preview-new hidden">
                    <div><img class="js-image-preview"></div>
                    <div><a class="js-remove-image-preview" href="#">'.t('Undo').'</a></div>
                </div>
            </div>';

        $class = val('class', $attributes, '');
        $attributes['class'] = trim($class.' js-image-upload');
        $input = $this->imageUploadWrap($fieldName, $attributes);

        $tag = val('Tag', $options, 'li');
        return '<'.$tag.' class="form-group js-image-preview-form-group">'.$label.$input.'</'.$tag.'>';
    }


    /**
     * Returns XHTML for a checkbox input element.
     *
     * Cannot consider all checkbox values to be boolean. (2009-04-02 mosullivan)
     * Cannot assume checkboxes are stored in database as string 'TRUE'. (2010-07-28 loki_racer)
     *
     * @param string $fieldName Name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray.
     * @param string $label Label to place next to the checkbox.
     * @param array $attributes Associative array of attributes for the input. (e.g. onclick, class)\
     *    Setting 'InlineErrors' to FALSE prevents error message even if $this->InlineErrors is enabled.
     * @return string
     */
    public function checkBox($fieldName, $label = '', $attributes = []) {
        $value = arrayValueI('value', $attributes, true);
        $attributes['value'] = $value;
        $display = val('display', $attributes, 'wrap');
        unset($attributes['display']);

        if (stringEndsWith($fieldName, '[]')) {
            if (!isset($attributes['checked'])) {
                $getValue = $this->getValue(substr($fieldName, 0, -2));
                if (is_array($getValue) && in_array($value, $getValue)) {
                    $attributes['checked'] = 'checked';
                } elseif ($getValue == $value)
                    $attributes['checked'] = 'checked';
            }
        } else {
            if ($this->getValue($fieldName) == $value) {
                $attributes['checked'] = 'checked';
            }
        }

        // Show inline errors?
        $showErrors = ($this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults));

        // Add error class to input element.
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        if (isset($attributes['class'])) {
            $class = $this->translateClasses($attributes['class']);
        } else {
            $class = $this->getStyle('checkbox', '');
        }

        $input = $this->input($fieldName, 'checkbox', $attributes);
        if ($label != '') {
            $labelElement = '<label for="'.
                arrayValueI('id', $attributes, $this->escapeID($fieldName, false)).'"'.
                attribute('class', $class).
                attribute('title', val('title', $attributes)).'>';

            if ($display === 'wrap') {
                $input = $labelElement.$input.' '.t($label).'</label>';
            } elseif ($display === 'before') {
                $input = $labelElement.t($label).'</label> '.$input;
            } elseif ($display === 'toggle') {
                $input = '<div class="label-wrap"><label>'.t($label).'</label></div><div class="toggle-box-wrapper"><div class="toggle-box">'.$input.$labelElement.'</label></div></div> ';
            } else {
                $input = $input.' '.$labelElement.t($label).'</label>';
            }
        }

        // Append validation error message
        if ($showErrors && arrayValueI('InlineErrors', $attributes, true)) {
            $input .= $this->inlineError($fieldName);
        }

        if ($this->getStyle('checkbox-container', '') && stripos($class, 'inline') == false) {
            $container = $this->getStyle('checkbox-container');
            $input = "<div class=\"$container\">".$input.'</div>';
        }

        return $input;
    }

    /**
     * Returns the XHTML for a list of checkboxes.
     *
     * @param string $fieldName Name of the field being posted with this input.
     *
     * @param mixed $dataSet Data to fill the checkbox list. Either an associative
     * array or a database dataset. ex: RoleID, Name from GDN_Role.
     *
     * @param mixed $valueDataSet Values to be pre-checked in $dataSet. Either an associative array
     * or a database dataset. ex: RoleID from GDN_UserRole for a single user.
     *
     * @param array $attributes An associative array of attributes for the select. Here is a list of
     * "special" attributes and their default values:
     * Attribute   Options                        Default
     * ------------------------------------------------------------------------
     * ValueField  The name of the field in       'value'
     *             $dataSet that contains the
     *             option values.
     * TextField   The name of the field in       'text'
     *             $dataSet that contains the
     *             option text.
     *
     * @return string
     */
    public function checkBoxList($fieldName, $dataSet, $valueDataSet = null, $attributes = []) {
        // Never display individual inline errors for these CheckBoxes
        $attributes['InlineErrors'] = false;

        $return = '';
        // If the form hasn't been posted back, use the provided $ValueDataSet
        if ($this->isPostBack() === false) {
            if ($valueDataSet === null) {
                $checkedValues = $this->getValue($fieldName);
            } else {
                $checkedValues = $valueDataSet;
                if (is_object($valueDataSet)) {
                    $checkedValues = array_column($valueDataSet->resultArray(), $fieldName);
                }
            }
        } else {
            $checkedValues = $this->getFormValue($fieldName, []);
        }
        $i = 1;
        if (is_object($dataSet)) {
            $valueField = arrayValueI('ValueField', $attributes, 'value');
            $textField = arrayValueI('TextField', $attributes, 'text');
            foreach ($dataSet->result() as $data) {
                $instance = $attributes;
                unset($instance['TextField'], $instance['ValueField']);
                $instance['value'] = $data->$valueField;
                $instance['id'] = $fieldName.$i;
                if (is_array($checkedValues) && in_array(
                    $data->$valueField,
                    $checkedValues
                )
                ) {
                    $instance['checked'] = 'checked';
                }

                $return .= '<li>'.$this->checkBox(
                    $fieldName.'[]',
                    $data->$textField,
                    $instance
                )."</li>\n";
                ++$i;
            }
        } elseif (is_array($dataSet)) {
            foreach ($dataSet as $text => $iD) {
                // Set attributes for this instance
                $instance = $attributes;
                unset($instance['TextField'], $instance['ValueField']);

                $instance['id'] = $fieldName.$i;

                if (is_array($iD)) {
                    $valueField = arrayValueI('ValueField', $attributes, 'value');
                    $textField = arrayValueI('TextField', $attributes, 'text');
                    $text = val($textField, $iD, '');
                    $iD = val($valueField, $iD, '');
                } else {
                    if (is_numeric($text)) {
                        $text = $iD;
                    }
                }
                $instance['value'] = $iD;

                if (is_array($checkedValues) && in_array($iD, $checkedValues)) {
                    $instance['checked'] = 'checked';
                }

                $return .= '<li>'.$this->checkBox($fieldName.'[]', $text, $instance)."</li>\n";
                ++$i;
            }
        }

        return '<ul class="'.concatSep(' ', 'CheckBoxList', val('listclass', $attributes)).'">'.$return.'</ul>';
    }

    /**
     * Returns the xhtml for a list of checkboxes; sorted into groups related to
     * the TextField value of the dataset.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     * should related directly to a field name in a user junction table.
     * ie. LUM_UserRole.RoleID
     *
     * @param mixed $dataSet The data to fill the options in the select list. Either an associative
     * array or a database dataset. ie. RoleID, Name from LUM_Role.
     *
     * @param mixed $valueDataSet The data that should be checked in $dataSet. Either an associative array
     * or a database dataset. ie. RoleID from LUM_UserRole for a single user.
     *
     * @param array $attributes An associative array of attributes for the select. Here is a list of
     * "special" attributes and their default values:
     *
     * Attribute   Options                        Default
     * ------------------------------------------------------------------------
     * ValueField  The name of the field in       'value'
     *             $dataSet that contains the
     *             option values.
     * TextField   The name of the field in       'text'
     *             $dataSet that contains the
     *             option text.
     *
     * @return string
     */
    public function checkBoxGrid($fieldName, $dataSet, $valueDataSet, $attributes) {
        // Never display individual inline errors for these CheckBoxes
        $attributes['InlineErrors'] = false;

        $return = '';
        $checkedValues = $valueDataSet;
        if (is_object($valueDataSet)) {
            $checkedValues = array_column($valueDataSet->resultArray(), $fieldName);
        }

        $i = 1;
        if (is_object($dataSet)) {
            $valueField = arrayValueI('ValueField', $attributes, 'value');
            $textField = arrayValueI('TextField', $attributes, 'text');
            $lastGroup = '';
            $group = [];
            $rows = [];
            $cols = [];
            $checkBox = '';
            foreach ($dataSet->result() as $data) {
                // Define the checkbox
                $instance = $attributes;
                unset($instance['TextField'], $instance['ValueField']);
                $instance['value'] = $data->$valueField;
                $instance['id'] = $fieldName.$i;
                if (is_array($checkedValues) && in_array(
                    $data->$valueField,
                    $checkedValues
                )
                ) {
                    $instance['checked'] = 'checked';
                }
                $checkBox = $this->checkBox($fieldName.'[]', '', $instance);

                // Organize the checkbox into an array for this group
                $currentTextField = $data->$textField;
                $aCurrentTextField = explode('.', $currentTextField);
                $aCurrentTextFieldCount = count($aCurrentTextField);
                $groupName = array_shift($aCurrentTextField);
                $colName = array_pop($aCurrentTextField);
                if ($aCurrentTextFieldCount >= 3) {
                    $rowName = implode('.', $aCurrentTextField);
                    if ($groupName != $lastGroup && $lastGroup != '') {
                        // Render the last group
                        $return .= $this->getCheckBoxGridGroup(
                            $lastGroup,
                            $group,
                            $rows,
                            $cols
                        );

                        // Clean out the $Group array & Rowcount
                        $group = [];
                        $rows = [];
                        $cols = [];
                    }

                    if (array_key_exists($colName, $group) === false || is_array($group[$colName]) === false) {
                        $group[$colName] = [];
                        if (!in_array($colName, $cols)) {
                            $cols[] = $colName;
                        }

                    }

                    if (!in_array($rowName, $rows)) {
                        $rows[] = $rowName;
                    }

                    $group[$colName][$rowName] = $checkBox;
                    $lastGroup = $groupName;
                }
                ++$i;
            }
        }
        return $return.$this->getCheckBoxGridGroup($lastGroup, $group, $rows, $cols);
    }

    /**
     *
     *
     * @param $data
     * @param $fieldName
     * @return string
     */
    public function checkBoxGridGroups($data, $fieldName) {
        $result = '';
        foreach ($data as $groupName => $groupData) {
            $result .= $this->checkBoxGridGroup($groupName, $groupData, $fieldName)."\n";
        }
        return $result;
    }

    /**
     *
     *
     * @param $groupName
     * @param $data
     * @param $fieldName
     * @return string
     */
    public function checkBoxGridGroup($groupName, $data, $fieldName) {
        // Never display individual inline errors for these CheckBoxes
        $attributes['InlineErrors'] = false;

        // Get the column and row info.
        $columns = $data['_Columns'];
        ksort($columns);
        $rows = $data['_Rows'];
        ksort($rows);
        unset($data['_Columns'], $data['_Rows']);

        if (array_key_exists('_Info', $data)) {
            $groupName = $data['_Info']['Name'];
            unset($data['_Info']);
        }

        $result = '<div class="table-wrap"><table class="table-data js-checkbox-grid table-checkbox-grid">';
        // Append the header.
        $result .= '<thead><tr><th>'.t($groupName).'</th>';
        foreach ($columns as $columnName => $x) {
            $result .=
                '<td>'
                .t($columnName)
                .'</td>';
        }
        $result.'</tr></thead>';

        // Append the rows.
        $result .= '<tbody>';
        $checkCount = 0;
        foreach ($rows as $rowName => $x) {
            $result .= '<tr><th>';

            // If the row name is still seperated by dots then put those in spans.
            $rowNames = explode('.', $rowName);
            for ($i = 0; $i < count($rowNames) - 1; ++$i) {
                $result .= '<span class="Parent">'.t($rowNames[$i]).'</span>';
            }
            $result .= t(self::labelCode($rowNames[count($rowNames) - 1])).'</th>';
            // Append the columns within the rows.
            foreach ($columns as $columnName => $y) {
                $result .= '<td>';
                // Check to see if there is a row corresponding to this area.
                if (array_key_exists($rowName.'.'.$columnName, $data)) {
                    $checkBox = $data[$rowName.'.'.$columnName];
                    $attributes = [
                        'value' => $checkBox['PostValue'],
                        'display' => 'after'
                    ];
                    if ($checkBox['Value']) {
                        $attributes['checked'] = 'checked';
                    }
//               $Attributes['id'] = "{$GroupName}_{$FieldName}_{$CheckCount}";
                    $checkCount++;

                    $result .= wrap(
                        $this->checkBox($fieldName.'[]', $rowName.'.'.$columnName, $attributes),
                        'div',
                        ['class' => 'checkbox-painted-wrapper']
                    );
                } else {
                    $result .= ' ';
                }
                $result .= '</td>';
            }
            $result .= '</tr>';
        }
        $result .= '</tbody></table></div>';
        return $result;
    }

    /**
     * Returns the closing of the form tag with an optional submit button.
     *
     * @param string $buttonCode
     * @param string $xhtml
     * @return string
     */
    public function close($buttonCode = '', $xhtml = '', $attributes = []) {
        $return = "</div>\n</form>";

        if ($xhtml != '') {
            $return = $xhtml.$return;
        }

        $formFooter = val('FormFooter', $attributes, false);

        if ($formFooter) {
            unset($attributes['FormFooter']);
        }

        if ($buttonCode != '') {
            $buttonCode = $this->button($buttonCode, $attributes);
        }

        if ($formFooter || $buttonCode) {
            $return = '<div class="'.$this->getStyle('form-footer').'">'.$formFooter.$buttonCode.'</div>'.$return;
        }

        return $return;
    }

    /**
     * Returns the current image in a field.
     * This is meant to be used with image uploads so that users can see the current value.
     *
     * @param string $fieldName
     * @param array $attributes
     * @return string
     */
    public function currentImage($fieldName, $attributes = []) {
        $result = $this->hidden($fieldName);

        $value = $this->getValue($fieldName);
        if ($value) {
            touchValue('class', $attributes, 'CurrentImage');
            $result .= img(Gdn_Upload::url($value), $attributes);
        }

        return $result;
    }

    /**
     * Returns XHTML for a standard date input control.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     *    should related directly to a field name in $this->_DataArray.
     * @param array $attributes An associative array of attributes for the input, e.g. onclick, class.
     *    Special attributes:
     *       YearRange, specified in yyyy-yyyy format. Default is 1900 to current year.
     *       Fields, array of month, day, year. Those are only valid values. Order matters.
     * @return string
     */
    public function date($fieldName, $attributes = []) {
        $return = '';
        $yearRange = arrayValueI('yearrange', $attributes, false);
        $startYear = 0;
        $endYear = 0;
        if ($yearRange !== false) {
            if (preg_match("/^[\d]{4}-{1}[\d]{4}$/i", $yearRange) == 1) {
                $startYear = substr($yearRange, 0, 4);
                $endYear = substr($yearRange, 5);
            }
        }
        if ($yearRange === false) {
            $startYear = date('Y');
            $endYear = 1900;
        }

        $months = array_map(
            't',
            explode(',', 'Month,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec')
        );

        $days = [t('Day')];
        for ($i = 1; $i < 32; ++$i) {
            $days[] = $i;
        }

        $years = [t('Year')];
        foreach (range($startYear, $endYear) as $year) {
            $years[$year] = $year;
        }

        // Show inline errors?
        $showErrors = $this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults);

        // Add error class to input element
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        // Never display individual inline errors for these DropDowns
        $attributes['InlineErrors'] = false;

        $cssClass = arrayValueI('class', $attributes, '');

        if ($this->getValue($fieldName) > 0) {
            $submittedTimestamp = strtotime($this->getValue($fieldName));
        } else {
            $submittedTimestamp = false;
        }

        // Allow us to specify which fields to show & order
        $fields = arrayValueI('fields', $attributes, ['month', 'day', 'year']);
        if (is_array($fields)) {
            foreach ($fields as $field) {
                switch ($field) {
                    case 'month':
                        // Month select
                        $attributes['class'] = trim($cssClass.' Month');
                        if ($submittedTimestamp) {
                            $attributes['Value'] = date('n', $submittedTimestamp);
                        }
                        $return .= $this->dropDown($fieldName.'_Month', $months, $attributes);
                        break;
                    case 'day':
                        // Day select
                        $attributes['class'] = trim($cssClass.' Day');
                        if ($submittedTimestamp) {
                            $attributes['Value'] = date('j', $submittedTimestamp);
                        }
                        $return .= $this->dropDown($fieldName.'_Day', $days, $attributes);
                        break;
                    case 'year':
                        // Year select
                        $attributes['class'] = trim($cssClass.' Year');
                        if ($submittedTimestamp) {
                            $attributes['Value'] = date('Y', $submittedTimestamp);
                        }
                        $return .= $this->dropDown($fieldName.'_Year', $years, $attributes);
                        break;
                }
            }
        }

        $return .= '<input type="hidden" name="DateFields[]" value="'.$fieldName.'" />';

        // Append validation error message
        if ($showErrors) {
            $return .= $this->inlineError($fieldName);
        }

        return $return;
    }

    /**
     * Returns XHTML for a select list.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     *    should related directly to a field name in $this->_DataArray. ie. RoleID
     * @param mixed $dataSet The data to fill the options in the select list. Either an associative
     *    array or a database dataset.
     * @param array $attributes An associative array of attributes for the select. Here is a list of
     *    "special" attributes and their default values:
     *
     *   Attribute   Options                        Default
     *   ------------------------------------------------------------------------
     *   ValueField  The name of the field in       'value'
     *               $dataSet that contains the
     *               option values.
     *   TextField   The name of the field in       'text'
     *               $dataSet that contains the
     *               option text.
     *   Value       A string or array of strings.  $this->_DataArray->$fieldName
     *   IncludeNull TRUE to include a blank row    FALSE
     *               String to create disabled
     *               first option.
     *   InlineErrors  Show inline error message?   TRUE
     *               Allows disabling per-dropdown
     *               for multi-fields like date()
     *
     * @return string
     */
    public function dropDown($fieldName, $dataSet, $attributes = []) {
        // Show inline errors?
        $showErrors = ($this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults));

        // Add error class to input element
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        if (!isset($attributes['class'])) {
            $attributes['class'] = $this->getStyle('dropdown');
        } else {
            $attributes['class'] = $this->translateClasses($attributes['class']);
        }

        $return = '';

        $wrap = val('Wrap', $attributes, false);
        if ($wrap) {
            $return = '<div class="'.$this->getStyle('input-wrap').'">';
        }

        // Opening select tag
        $return .= '<select';
        $return .= $this->_idAttribute($fieldName, $attributes);
        $return .= $this->_nameAttribute($fieldName, $attributes);
        $return .= $this->_attributesToString($attributes);
        $return .= ">\n";

        // Get value from attributes and ensure it's an array
        $value = arrayValueI('Value', $attributes);
        if ($value === false) {
            $value = $this->getValue($fieldName, val('Default', $attributes));
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        // Prevent default $Value from matching key of zero
        $hasValue = ($value !== [false] && $value !== ['']) ? true : false;

        // Start with null option?
        $includeNull = arrayValueI('IncludeNull', $attributes, false);
        if ($includeNull === true) {
            $return .= "<option value=\"\"></option>\n";
        } elseif ($includeNull)
            $return .= "<option value=\"\">$includeNull</option>\n";

        if (is_object($dataSet)) {
            $fieldsExist = false;
            $valueField = arrayValueI('ValueField', $attributes, 'value');
            $textField = arrayValueI('TextField', $attributes, 'text');
            $data = $dataSet->firstRow();
            if (is_object($data) && property_exists($data, $valueField) && property_exists(
                $data,
                $textField
            )
            ) {
                foreach ($dataSet->result() as $data) {
                    $return .= '<option value="'.$data->$valueField.
                        '"';
                    if (in_array($data->$valueField, $value) && $hasValue) {
                        $return .= ' selected="selected"';
                    }

                    $return .= '>'.$data->$textField."</option>\n";
                }
            }
        } elseif (is_array($dataSet)) {
            foreach ($dataSet as $iD => $text) {
                if (is_array($text)) {
                    $attribs = $text;
                    $text = val('Text', $attribs, '');
                    unset($attribs['Text']);
                } else {
                    $attribs = [];
                }
                $return .= '<option value="'.$iD.'"';
                if (in_array($iD, $value) && $hasValue) {
                    $return .= ' selected="selected"';
                }

                $return .= attribute($attribs).'>'.$text."</option>\n";
            }
        }
        $return .= '</select>';

        if ($wrap) {
            $return .= '</div>';
        }

        // Append validation error message
        if ($showErrors && arrayValueI('InlineErrors', $attributes, true)) {
            $return .= $this->inlineError($fieldName);
        }

        return $return;
    }

    /**
     * Returns the xhtml for a dropdown list with option groups.
     * @param string $fieldName
     * @param array $data
     * @param string $groupField
     * @param string $textField
     * @param string $valueField
     * @param array $attributes
     * @return string
     */
    public function dropDownGroup($fieldName, $data, $groupField, $textField, $valueField, $attributes = []) {
        $return = '<select'
            .$this->_idAttribute($fieldName, $attributes)
            .$this->_nameAttribute($fieldName, $attributes)
            .$this->_attributesToString($attributes)
            .">\n";

        // Get the current value.
        $currentValue = val('Value', $attributes, false);
        if ($currentValue === false) {
            $currentValue = $this->getValue($fieldName, getValue('Default', $attributes));
        }

        // Add a null option?
        $includeNull = arrayValueI('IncludeNull', $attributes, false);
        if ($includeNull === true) {
            $return .= "<option value=\"\"></option>\n";
        } elseif ($includeNull)
            $return .= "<option value=\"\">$includeNull</option>\n";

        $lastGroup = null;

        foreach ($data as $row) {
            $group = $row[$groupField];

            // Check for a group header.
            if ($lastGroup !== $group) {
                // Close off the last opt group.
                if ($lastGroup !== null) {
                    $return .= '</optgroup>';
                }

                $return .= '<optgroup label="'.htmlspecialchars($group)."\">\n";
                $lastGroup = $group;
            }

            $value = $row[$valueField];

            if ($currentValue == $value) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            $return .= '<option value="'.htmlspecialchars($value).'"'.$selected.'>'.htmlspecialchars($row[$textField])."</option>\n";

        }

        if ($lastGroup) {
            $return .= '</optgroup>';
        }

        $return .= '</select>';

        return $return;
    }

    /**
     * Returns XHTML for all form-related errors that have occurred.
     *
     * @return string
     */
    public function errors() {
        $return = '';
        if (is_array($this->_ValidationResults) && count($this->_ValidationResults) > 0) {
            $return = "<div class=\"Messages Errors\">\n<ul>\n";
            foreach ($this->_ValidationResults as $fieldName => $problems) {
                $count = count($problems);
                for ($i = 0; $i < $count; ++$i) {
                    if (substr($problems[$i], 0, 1) == '@') {
                        $return .= "<li>".substr($problems[$i], 1)."</li>\n";
                    } else {
                        $return .= '<li>'.sprintf(
                            t($problems[$i]),
                            t($fieldName)
                        )."</li>\n";
                    }
                }
            }
            $return .= "</ul>\n</div>\n";
        }
        return $return;
    }

    public function errorString() {
        $return = '';
        if (is_array($this->_ValidationResults) && count($this->_ValidationResults) > 0) {
            foreach ($this->_ValidationResults as $fieldName => $problems) {
                $count = count($problems);
                for ($i = 0; $i < $count; ++$i) {
                    if (substr($problems[$i], 0, 1) == '@') {
                        $return .= rtrim(substr($problems[$i], 1), '.').'. ';
                    } else {
                        $return .= rtrim(sprintf(
                            t($problems[$i]),
                            t($fieldName)
                        ), '.').'. ';
                    }
                }
            }
        }
        return trim($return);
    }

    /**
     * @see Gdn_Form::escapeFieldName()
     * @deprecated
     *
     * @param string $string
     * @return string
     */
    public function escapeString($string) {
        deprecated('Gd_Form::escapeString()');
        return $this->escapeFieldName($string);
    }

    /**
     * Returns a checkbox table.
     *
     * @param string $groupName The name of the checkbox table (the text that appears in the top-left
     * cell of the table). This value will be passed through the t()
     * function before render.
     *
     * @param array $group An array of $PermissionName => $CheckBoxXhtml to be rendered within the
     * grid. This represents the final (third) part of the permission name
     * string, as in the "Edit" part of "Garden.Roles.Edit".
     * ie. 'Edit' => '<input type="checkbox" id="PermissionID"
     * name="Role/PermissionID[]" value="20" />';
     *
     * @param array $rows An array of rows to appear in the grid. This represents the middle part
     * of the permission name, as in the "Roles" part of "Garden.Roles.Edit".
     *
     * @param array $cols An array of columns to appear in the grid for each row. This (again)
     * represents the final part of the permission name, as in the "Edit" part
     * of "Garden.Roles.Edit".
     * ie. Row1 = array('Add', 'Edit', 'Delete');
     */
    public function getCheckBoxGridGroup($groupName, $group, $rows, $cols) {
        $return = '';
        $headings = '';
        $cells = '';
        $rowCount = count($rows);
        $colCount = count($cols);
        for ($j = 0; $j < $rowCount; ++$j) {
            $alt = true;
            for ($i = 0; $i < $colCount; ++$i) {
                $colName = $cols[$i];
                $rowName = $rows[$j];

                if ($j == 0) {
                    $headings .= '<td'.($alt ? ' class="Alt"' : '').
                    '>'.t($colName).'</td>';
                }

                if (array_key_exists($rowName, $group[$colName])) {
                    $cells .= '<td'.($alt ? ' class="Alt"' : '').
                        '>'.$group[$colName][$rowName].
                        '</td>';
                } else {
                    $cells .= '<td'.($alt ? ' class="Alt"' : '').
                        '>&#160;</td>';
                }
                $alt = !$alt;
            }
            if ($headings != '') {
                $return .= "<thead><tr><th>".t($groupName)."</th>".
                $headings."</tr></thead>\r\n<tbody>";
            }

            $aRowName = explode('.', $rowName);
            $rowNameCount = count($aRowName);
            if ($rowNameCount > 1) {
                $rowName = '';
                for ($i = 0; $i < $rowNameCount; ++$i) {
                    if ($i < $rowNameCount - 1) {
                        $rowName .= '<span class="Parent">'.
                        t($aRowName[$i]).'</span>';
                    } else {
                        $rowName .= t($aRowName[$i]);
                    }
                }
            } else {
                $rowName = t($rowName);
            }
            $return .= '<tr><th>'.$rowName.'</th>'.$cells."</tr>\r\n";
            $headings = '';
            $cells = '';
        }
        return $return == '' ? '' : '<div class="table-wrap"><table class="table-data js-tj js-checkbox-grid table-checkbox-grid">'.$return.'</tbody></table></div>';
    }

    /**
     * Returns XHTML for all hidden fields.
     *
     * @return string
     */
    public function getHidden() {
        $return = '';
        if (is_array($this->HiddenInputs)) {
            foreach ($this->HiddenInputs as $name => $value) {
                $return .= $this->hidden($name, ['value' => $value]);
            }
            // Clean out the array
            // mosullivan - removed cleanout so that entry forms can all have the same hidden inputs added once on the entry/index view.
            // TODO - WATCH FOR BUGS BECAUSE OF THIS CHANGE.
            // $this->HiddenInputs = array();
        }
        return $return;
    }

    /**
     * Returns the xhtml for a hidden input.
     *
     * @param string $fieldName The name of the field that is being hidden/posted with this input. It
     * should related directly to a field name in $this->_DataArray.
     * @param array $attributes An associative array of attributes for the input. ie. maxlength, onclick,
     * class, etc
     * @return string
     */
    public function hidden($fieldName, $attributes = []) {
        $return = '<input type="hidden"';
        $return .= $this->_idAttribute($fieldName, $attributes);
        $return .= $this->_nameAttribute($fieldName, $attributes);
        $return .= $this->_valueAttribute($fieldName, $attributes);
        $return .= $this->_attributesToString($attributes);
        $return .= ' />';
        return $return;
    }

    /**
     * Return a control for uploading images.
     *
     * @param string $fieldName
     * @param array $attributes
     * @return string
     * @since 2.1
     */
    public function imageUpload($fieldName, $attributes = []) {
        $result = '<div class="FileUpload ImageUpload">'.
            $this->currentImage($fieldName, $attributes).
            '<div>'.
            $this->input($fieldName.'_New', 'file').
            '</div>'.
            '</div>';

        return $result;
    }

    /**
     * Return a control for uploading images with a wrapper div. The existing image should be displayed by the label.
     *
     * @param string $fieldName
     * @param array $attributes
     * @return string
     */
    public function imageUploadWrap($fieldName, $attributes = []) {
        return $this->fileUploadWrap($fieldName.'_New', $attributes);
    }

    /**
     * Returns XHTML of inline error for specified field.
     *
     * @since 2.0.18
     * @access public
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @return string
     */
    public function inlineError($fieldName) {
        $appendError = '<p class="'.$this->ErrorClass.'">';
        foreach ($this->_ValidationResults[$fieldName] as $validationError) {
            $appendError .= sprintf(t($validationError), t($fieldName)).' ';
        }
        $appendError .= '</p>';

        return $appendError;
    }

    /**
     * Returns the xhtml for a standard input tag.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @param string $type The type attribute for the input.
     * @param array $attributes An associative array of attributes for the input. (e.g. maxlength, onclick, class)
     *    Setting 'InlineErrors' to FALSE prevents error message even if $this->InlineErrors is enabled.
     * @return string
     */
    public function input($fieldName, $type = 'text', $attributes = []) {
        switch ($type) {
            case 'checkbox':
            case 'button':
            case 'hidden':
            case 'radio':
            case 'reset':
            case 'submit':
                $typeClass = '';
                break;
            case 'file':
                $typeClass = 'file';
                break;
            default:
                $typeClass = 'textbox';
                break;
        }
        $attributes['class'] = $this->translateClasses(arrayValueI('class', $attributes).' '.$typeClass);

        // Show inline errors?
        $showErrors = $this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults);

        // Add error class to input element
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        $return = '';
        $wrap = val('Wrap', $attributes, false, true);
        $strength = val('Strength', $attributes, false, true);
        if ($wrap) {
            $return .= '<div class="'.$this->getStyle('input-wrap').'">';
        }

        if (strtolower($type) == 'checkbox') {
            if (isset($attributes['nohidden'])) {
                unset($attributes['nohidden']);
            } else {
                $return .= '<input type="hidden" name="Checkboxes[]" value="'.
                    (substr($fieldName, -2) === '[]' ? substr($fieldName, 0, -2) : $fieldName).
                    '" />';
            }
        }


        $return .= '<input type="'.$type.'"';
        $return .= $this->_idAttribute($fieldName, $attributes);
        if ($type == 'file') {
            $return .= attribute(
                'name',
                arrayValueI('Name', $attributes, $fieldName)
            );
        } else {
            $return .= $this->_nameAttribute($fieldName, $attributes);
            if ($strength) {
                $return .= ' data-strength="true"';
            }
            $return .= $this->_valueAttribute($fieldName, $attributes);
        }

        $return .= $this->_attributesToString($attributes);
        $return .= ' />';


        // Append validation error message
        if ($showErrors && arrayValueI('InlineErrors', $attributes, true)) {
            $return .= $this->inlineError($fieldName);
        }

        if ($type == 'password' && $strength) {
            $return .= <<<PASSWORDMETER
<div class="PasswordStrength">
   <div class="Background"></div>
   <div class="Strength"></div>
   <div class="Separator" style="left: 20%;"></div>
   <div class="Separator" style="left: 40%;"></div>
   <div class="Separator" style="left: 60%;"></div>
   <div class="Separator" style="left: 80%;"></div>
   <div class="StrengthText">&nbsp;</div>
</div>
PASSWORDMETER;
        }

        if ($wrap) {
            $return .= '</div>';
        }

        return $return;
    }


    public function inputWrap($fieldName, $type = 'text', $attributes = []) {
        return '<div class="input-wrap">'.$this->input($fieldName, $type, $attributes).'</div>';
    }

        /**
     * Returns XHTML for a label element.
     *
     * @param string $translationCode Code to be translated and presented within the label tag.
     * @param string $fieldName Name of the field that the label is for.
     * @param array $attributes Associative array of attributes for the input that the label is for.
     *    This is only available in case the related input has a custom id specified in the attributes array.
     *
     * @return string
     */
    public function label($translationCode, $fieldName = '', $attributes = []) {
        // Assume we always want a 'for' attribute because it's Good & Proper.
        // Precedence: 'for' attribute, 'id' attribute, $FieldName, $TranslationCode
        $defaultFor = ($fieldName == '') ? $translationCode : $fieldName;
        $for = arrayValueI('for', $attributes, arrayValueI('id', $attributes, $this->escapeID($defaultFor, false)));

        $return = '<label for="'.$for.'"'.$this->_attributesToString($attributes).'>'.t($translationCode)."</label>\n";
        return $return;
    }

    public function labelWrap($translationCode, $fieldName = '', $attributes = []) {
        return '<div class="label-wrap">'.$this->label($translationCode, $fieldName, $attributes).'</div>';
    }

    /**
     * Generate a friendly looking label translation code from a camel case variable name
     * @param string|array $item The item to generate the label from.
     *  - string: Generate the label directly from the item.
     *  - array: Generate the label from the item as if it is a schema row passed to Gdn_Form::simple().
     * @return string
     */
    public static function labelCode($item) {
        if (is_array($item)) {
            if (isset($item['LabelCode'])) {
                return $item['LabelCode'];
            }

            $labelCode = $item['Name'];
        } else {
            $labelCode = $item;
        }


        if (strpos($labelCode, '.') !== false) {
            $labelCode = trim(strrchr($labelCode, '.'), '.');
        }

        // Split camel case labels into seperate words.
        $labelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $labelCode);
        $labelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $labelCode);
        $labelCode = trim($labelCode);

        return $labelCode;
    }

    /**
     * Returns the xhtml for the opening of the form (the form tag and all
     * hidden elements).
     *
     * @param array $attributes An associative array of attributes for the form tag. Here is a list of
     *  "special" attributes and their default values:
     *
     *   Attribute  Options     Default
     *   ----------------------------------------
     *   method     get,post    post
     *   action     [any url]   [The current url]
     *   ajax       TRUE,FALSE  FALSE
     *
     * @return string
     *
     * @todo check that missing DataObject parameter
     */
    public function open($attributes = []) {
        if (!is_array($attributes)) {
            $attributes = [];
        }

        $return = '<form';
        if (array_key_exists('id', $attributes)) {
            $return .= $this->_idAttribute('', $attributes);
        }

        // Method
        $methodFromAttributes = arrayValueI('method', $attributes);
        $this->Method = $methodFromAttributes === false ? $this->Method : $methodFromAttributes;

        // Action
        $actionFromAttributes = arrayValueI('action', $attributes);
        if ($this->Action == '') {
            $this->Action = url();
        }

        $this->Action = $actionFromAttributes === false ? $this->Action : $actionFromAttributes;

        if (strcasecmp($this->Method, 'get') == 0) {
            // The path is not getting passed on get forms so put them in hidden fields.
            $action = strrchr($this->Action, '?');
            $exclude = val('Exclude', $attributes, []);
            if ($action !== false) {
                $this->Action = substr($this->Action, 0, -strlen($action));
                parse_str(trim($action, '?'), $query);
                $hiddens = '';
                foreach ($query as $key => $value) {
                    if (in_array($key, $exclude)) {
                        continue;
                    }
                    $key = Gdn_Format::form($key);
                    $value = Gdn_Format::form($value);
                    $hiddens .= "\n<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
                }
            }
        }

        $return .= ' method="'.$this->Method.'"'
            .' action="'.$this->Action.'"'
            .$this->_attributesToString($attributes)
            .">\n<div>\n";

        if (isset($hiddens)) {
            $return .= $hiddens;
        }

        // Postback Key - don't allow it to be posted in the url (prevents csrf attacks & hijacks)
        if ($this->Method != "get") {
            $session = Gdn::session();
            $return .= $this->hidden(
                'TransientKey',
                ['value' => $session->transientKey()]
            );
            // Also add a honeypot if Forms.HoneypotName has been defined
            $honeypotName = Gdn::config(
                'Garden.Forms.HoneypotName'
            );
            if ($honeypotName) {
                $return .= $this->hidden(
                    $honeypotName,
                    ['Name' => $honeypotName, 'style' => "display: none;"]
                );
            }
        }

        // Render all other hidden inputs that have been defined
        $return .= $this->getHidden();
        return $return;
    }

    /**
     * Returns XHTML for a radio input element.
     *
     * Provides way of wrapping input() with a label.
     *
     * @param string $fieldName Name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray.
     * @param string $label Label to place next to the radio.
     * @param array $attributes Associative array of attributes for the input (e.g. onclick, class).
     *    Special values 'Value' and 'Default' (see RadioList).
     * @return string
     */
    public function radio($fieldName, $label = '', $attributes = []) {
        $value = arrayValueI('Value', $attributes, 'TRUE');
        $attributes['value'] = $value;
        $formValue = $this->getValue($fieldName, arrayValueI('Default', $attributes));
        $display = val('display', $attributes, 'wrap');
        unset($attributes['display']);

        // Check for 'checked'
        if ($formValue == $value) {
            $attributes['checked'] = 'checked';
        }

        // Never display individual inline errors for this Input
        $attributes['InlineErrors'] = false;

        // Get standard radio Input
        $input = $this->input($fieldName, 'radio', $attributes);

        if (isset($attributes['class'])) {
            $class = $this->translateClasses($attributes['class']);
        } else {
            $class = $this->getStyle('radio');
        }

        // Wrap with label.
        if ($label != '') {
            $labelElement = '<label for="'.arrayValueI('id', $attributes, $this->escapeID($fieldName, false)).'" class="'.val('class', $attributes, 'RadioLabel').'">';
            if ($display === 'wrap') {
                $labelElement = '<label'.attribute('class', $class).'>';
                $input = $labelElement.$input.' '.t($label).'</label>';
            } elseif ($display === 'before') {
                $input = $labelElement.t($label).'</label> '.$input;
            } else {
                $input = $input.' '.$labelElement.t($label).'</label>';
            }
        }

        return $input;
    }

    /**
     * Returns XHTML for an unordered list of radio button elements.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray. ie. RoleID
     * @param mixed $dataSet The data to fill the options in the select list. Either an associative
     *    array or a database dataset.
     * @param array $attributes An associative array of attributes for the list. Here is a list of
     *    "special" attributes and their default values:
     *
     *   Attribute   Options                        Default
     *   ------------------------------------------------------------------------
     *   ValueField  The name of the field in       'value'
     *               $dataSet that contains the
     *               option values.
     *   TextField   The name of the field in       'text'
     *               $dataSet that contains the
     *               option text.
     *   Value       A string or array of strings.  $this->_DataArray->$fieldName
     *   Default     The default value.             empty
     *   InlineErrors  Show inline error message?   TRUE
     *               Allows disabling per-dropdown
     *               for multi-fields like date()
     *
     * @return string
     */
    public function radioList($fieldName, $dataSet, $attributes = []) {
        $list = val('list', $attributes);

        $return = '';

        if ($list) {
            $return .= '<ul'.(isset($attributes['listclass']) ? " class=\"{$attributes['listclass']}\"" : '').'>';
            $liOpen = '<li'.attribute('class', $this->getStyle('radio-container', '').' '.val('list-item-class', $attributes)).'>';
            $liClose = '</li>';
        } elseif ($this->getStyle('radio-container', '') && stripos(val('class', $attributes), 'inline') === false) {
            $class = $this->getStyle('radio-container');
            $liOpen = "<div class=\"$class\">";
            $liClose = '</div>';
        } else {
            $liOpen = '';
            $liClose = ' ';
        }

        // Show inline errors?
        $showErrors = ($this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults));

        // Add error class to input element
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        if (is_object($dataSet)) {
            $valueField = arrayValueI('ValueField', $attributes, 'value');
            $textField = arrayValueI('TextField', $attributes, 'text');
            $data = $dataSet->firstRow();
            if (property_exists($data, $valueField) && property_exists(
                $data,
                $textField
            )
            ) {
                foreach ($dataSet->result() as $data) {
                    $attributes['value'] = $data->$valueField;

                    $return .= $liOpen.$this->radio($fieldName, $data->$textField, $attributes).$liClose;
                }
            }
        } elseif (is_array($dataSet)) {
            foreach ($dataSet as $iD => $text) {
                $attributes['value'] = $iD;
                $return .= $liOpen.$this->radio($fieldName, $text, $attributes).$liClose;
            }
        }

        if ($list) {
            $return .= '</ul>';
        }

        // Append validation error message
        if ($showErrors && arrayValueI('InlineErrors', $attributes, true)) {
            $return .= $this->inlineError($fieldName);
        }

        return $return;
    }

    /**
     * Returns the xhtml for a text-based input.
     *
     * @param string $fieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @param array $attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *  class, etc
     * @return string
     */
    public function textBox($fieldName, $attributes = []) {
        if (!is_array($attributes)) {
            $attributes = [];
        }

        $multiLine = arrayValueI('MultiLine', $attributes);

        if ($multiLine) {
            $attributes['rows'] = arrayValueI('rows', $attributes, '6'); // For xhtml compliance
            $attributes['cols'] = arrayValueI('cols', $attributes, '100'); // For xhtml compliance
        }

        // Show inline errors?
        $showErrors = $this->_InlineErrors && array_key_exists($fieldName, $this->_ValidationResults);

        $cssClass = arrayValueI('class', $attributes);
        if ($cssClass == false) {
            $attributes['class'] = $this->getStyle($multiLine ? 'textarea' : 'textbox');
        } else {
            $attributes['class'] = $this->translateClasses($cssClass);
        }

        // Add error class to input element
        if ($showErrors) {
            $this->addErrorClass($attributes);
        }

        $return = '';
        $wrap = val('Wrap', $attributes, false, true);
        if ($wrap) {
            $return .= '<div class="'.$this->getStyle('input-wrap').'">';
        }

        $return .= $multiLine === true ? '<textarea' : '<input type="'.val('type', $attributes, 'text').'"';
        $return .= $this->_idAttribute($fieldName, $attributes);
        $return .= $this->_nameAttribute($fieldName, $attributes);
        $return .= $multiLine === true ? '' : $this->_valueAttribute($fieldName, $attributes);
        $return .= $this->_attributesToString($attributes);

        $value = arrayValueI('value', $attributes, $this->getValue($fieldName));

        $return .= $multiLine === true ? '>'.htmlentities($value, ENT_COMPAT, 'UTF-8').'</textarea>' : ' />';

        // Append validation error message
        if ($showErrors) {
            $return .= $this->inlineError($fieldName);
        }

        if ($wrap) {
            $return .= '</div>';
        }

        return $return;
    }

    public function textBoxWrap($fieldName, $attributes = []) {
        return '<div class="input-wrap">'.$this->textBox($fieldName, $attributes).'</div>';
    }


        /// =========================================================================
    /// Methods for interfacing with the model & db.
    /// =========================================================================

    /**
     * Adds an error to the errors collection and optionally relates it to the
     * specified FieldName. Errors added with this method can be rendered with
     * $this->errors().
     *
     * @param mixed $errorCode
     *  - <b>string</b>: The translation code that represents the error to display.
     *  - <b>Exception</b>: The exception to display the message for.
     * @param string $fieldName The name of the field to relate the error to.
     */
    public function addError($error, $fieldName = '') {
        if (is_string($error)) {
            $errorCode = $error;
        } elseif (is_a($error, 'Gdn_UserException')) {
            $errorCode = '@'.Gdn_Format::htmlFilter($error->getMessage());
        } elseif (is_a($error, 'Exception')) {
            // Strip the extra information out of the exception.
            $parts = explode('|', $error->getMessage());
            $message = htmlspecialchars($parts[0]);
            if (count($parts) >= 3) {
                $fileSuffix = ": {$parts[1]}->{$parts[2]}(...)";
            } else {
                $fileSuffix = "";
            }

            if (debug()) {
                $errorCode = '@<pre>'.
                    $message."\n".
                    '## '.$error->getFile().'('.$error->getLine().")".$fileSuffix."\n".
                    htmlspecialchars($error->getTraceAsString()).
                    '</pre>';
            } else {
                $errorCode = '@'.htmlspecialchars(strip_tags($error->getMessage()));
            }
        }

        if ($fieldName == '') {
            $fieldName = '<General Error>';
        }

        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = [];
        }

        if (!array_key_exists($fieldName, $this->_ValidationResults)) {
            $this->_ValidationResults[$fieldName] = [$errorCode];
        } else {
            if (!is_array($this->_ValidationResults[$fieldName])) {
                $this->_ValidationResults[$fieldName] = [
                $this->_ValidationResults[$fieldName],
                $errorCode];
            } else {
                $this->_ValidationResults[$fieldName][] = $errorCode;
            }
        }
    }

    /**
     * Adds a hidden input value to the form.
     *
     * If the $forceValue parameter remains FALSE, it will grab the value into the hidden input from the form
     * on postback. Otherwise it will always force the assigned value to the
     * input regardless of postback.
     *
     * @param string $fieldName The name of the field being added as a hidden input on the form.
     * @param string $value The value being assigned in the hidden input. Unless $forceValue is
     *  changed to TRUE, this field will be retrieved from the form upon
     *  postback.
     * @param bool $forceValue
     */
    public function addHidden($fieldName, $value = null, $forceValue = false) {
        if ($this->isPostBack() && $forceValue === false) {
            $value = $this->getFormValue($fieldName, $value);
        }

        $this->HiddenInputs[$fieldName] = $value;
    }

    /**
     * Returns a boolean value indicating if the current page has an authenticated postback.
     *
     * It validates the postback by looking at a transient value that was rendered using $this->open()
     * and submitted with the form. Ref: http://en.wikipedia.org/wiki/Cross-site_request_forgery
     *
     * @param bool $throw Whether or not to throw an exception if this is a postback AND the transient key doesn't validate.
     * @return bool Returns true if the postback could be authenticated or false otherwise.
     * @throws Gdn_UserException Throws an exception when this is a postback AND the transient key doesn't validate.
     */
    public function authenticatedPostBack($throw = false) {
        $keyName = 'TransientKey';
        $postBackKey = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, $keyName, false);

        // If this isn't a postback then return false if there isn't a transient key.
        if (!$postBackKey && !Gdn::request()->isPostBack()) {
            return false;
        }

        $result = Gdn::session()->validateTransientKey($postBackKey);

        if (!$result && $throw && Gdn::request()->isPostBack()) {
            throw new Gdn_UserException(t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 403);
        }

        return $result;
    }

    /**
     * Checks $this->formValues() to see if the specified button translation
     * code was submitted with the form (helps figuring out what button was
     * pressed to submit the form when there is more than one button available).
     *
     * @param string $buttonCode The translation code of the button to check for.
     * @return boolean
     */
    public function buttonExists($buttonCode) {
        return array_key_exists($buttonCode, $this->formValues()) ? true : false;
    }

    /**
     * Emptys the $this->_FormValues collection so that all form fields will load empty.
     */
    public function clearInputs() {
        $this->_FormValues = [];
    }

    /**
     * Returns a count of the number of errors that have occurred.
     *
     * @return int
     */
    public function errorCount() {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = [];
        }

        return count($this->_ValidationResults);
    }

    /**
     * Returns the provided fieldname with improper characters stripped.
     *
     * PHP doesn't allow "." in variable names from external sources such as a
     * HTML form. Some Vanilla components however rely on variable names such
     * as "a.b.c". So we need to escape them for backwards compatibility.
     *
     * Replaces e.g. "\" with "\\", "-dot-" with "\\-dot-" and "." with "-dot-".
     *
     * @see Gdn_Form::unescapeFieldName()
     *
     * @param string $string
     * @return string
     */
    public function escapeFieldName($string) {
        $search = ['\\', '-dot-', '.'];
        $replace = ['\\\\', '\\-dot-', '-dot-'];
        return str_replace($search, $replace, $string);
    }

    /**
     * Unescape strings that were escaped with {@link Gdn_Form::escapeFieldName()}.
     *
     * Replaces e.g. "\\" with "\", "\\-dot-" with "-dot-" and "-dot-" with ".".
     *
     * @see Gdn_Form::escapeFieldName()
     *
     * @param string $string
     * @return string
     */
    public function unescapeFieldName($string) {
        $search = ['/(?<!\\\\)(\\\\\\\\)*-dot-/', '/\\\\-dot-/', '/\\\\\\\\/'];
        $replace = ['$1.', '-dot-', '\\\\'];
        return preg_replace($search, $replace, $string);
    }

    /**
     * Returns the provided fieldname with non-alpha-numeric values stripped and
     * $this->IDPrefix prepended.
     *
     * @param string $fieldName
     * @param bool $forceUniqueID
     * @return string
     */
    public function escapeID(
        $fieldName,
        $forceUniqueID = true
    ) {
        $iD = $fieldName;
        if (substr($iD, -2) == '[]') {
            $iD = substr($iD, 0, -2);
        }

        $iD = $this->IDPrefix.Gdn_Format::alphaNumeric(str_replace('.', '-dot-', $iD));
        $tmp = $iD;
        $i = 1;
        if ($forceUniqueID === true) {
            if (array_key_exists($iD, $this->_IDCollection)) {
                $tmp = $iD.$this->_IDCollection[$iD];
                $this->_IDCollection[$iD]++;
            } else {
                $tmp = $iD;
                $this->_IDCollection[$iD] = 1;

            }
        } else {
            // If not forcing unique (ie. getting the id for a label's "for" tag),
            // get the last used copy of the requested id.
            $found = false;
            $count = val($iD, $this->_IDCollection, 0);
            if ($count <= 1) {
                $tmp = $iD;
            } else {
                $tmp = $iD.($count - 1);
            }
        }
        return $tmp;
    }

    /**
     *
     *
     * @return array
     */
    public function formDataSet() {
        if (is_null($this->_FormValues)) {
            $this->formValues();
        }

        $result = [[]];
        foreach ($this->_FormValues as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $rowIndex => $rowValue) {
                    if (!array_key_exists($rowIndex, $result)) {
                        $result[$rowIndex] = [$key => $rowValue];
                    } else {
                        $result[$rowIndex][$key] = $rowValue;
                    }
                }
            } else {
                $result[0][$key] = $value;
            }
        }

        return $result;
    }

    /**
     * If the form has been posted back, this method return an associative
     * array of $fieldName => $value pairs which were sent in the form.
     *
     * Note: these values are typically used by the model and it's validation object.
     *
     * @return array
     */
    public function formValues($newValue = null) {
        if ($newValue !== null) {
            $this->_FormValues = $newValue;
            return;
        }

        if (!is_array($this->_FormValues)) {
            $this->_FormValues = [];

            $request = Gdn::request();
            $collection = $this->Method == 'get' ? $request->get() : $request->post();

            foreach ($collection as $fieldName => $value) {
                $fieldName = $this->unescapeFieldName($fieldName);
                $this->_FormValues[$fieldName] = $value;
            }

            // Make sure that unchecked checkboxes get added to the collection
            if (array_key_exists('Checkboxes', $collection)) {
                $uncheckedCheckboxes = $collection['Checkboxes'];
                if (is_array($uncheckedCheckboxes) === true) {
                    $count = count($uncheckedCheckboxes);
                    for ($i = 0; $i < $count; ++$i) {
                        if (!array_key_exists($uncheckedCheckboxes[$i], $this->_FormValues)) {
                            $this->_FormValues[$uncheckedCheckboxes[$i]] = false;
                        }
                    }
                }
            }

            // Make sure that Date inputs (where the day, month, and year are
            // separated into their own dropdowns on-screen) get added to the
            // collection as a single field as well...
            if (array_key_exists(
                'DateFields',
                $collection
            ) === true
            ) {
                $dateFields = $collection['DateFields'];
                if (is_array($dateFields) === true) {
                    $count = count($dateFields);
                    for ($i = 0; $i < $count; ++$i) {
                        if (array_key_exists(
                            $dateFields[$i],
                            $this->_FormValues
                        ) ===
                            false
                        ) { // Saving dates in the format: YYYY-MM-DD
                            $year = val(
                                $dateFields[$i].
                                '_Year',
                                $this->_FormValues,
                                0
                            );
                        }
                        $month = val(
                            $dateFields[$i].
                            '_Month',
                            $this->_FormValues,
                            0
                        );
                        $day = val(
                            $dateFields[$i].
                            '_Day',
                            $this->_FormValues,
                            0
                        );
                        $month = str_pad(
                            $month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );
                        $day = str_pad(
                            $day,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );
                        $this->_FormValues[$dateFields[$i]] = $year.
                            '-'.
                            $month.
                            '-'.
                            $day;
                    }
                }
            }
        }

        return $this->_FormValues;
    }

    /**
     * Get form data array
     *
     * Returns an associative array containing all the pre-propulated field data
     * for the current form.
     *
     * @return array
     */
    public function formData() {
        return $this->_DataArray;
    }

    /**
     * Gets the value associated with $fieldName from the sent form fields.
     * If $fieldName isn't found in the form, it returns $default.
     *
     * @param string $fieldName The name of the field to get the value of.
     * @param mixed $default The default value to return if $fieldName isn't found.
     * @return unknown
     */
    public function getFormValue($fieldName, $default = '') {
        return val($fieldName, $this->formValues(), $default);
    }

    /**
     * Gets the value associated with $fieldName.
     *
     * If the form has been posted back, it will retrieve the value from the form.
     * If it hasn't been posted back, it gets the value from $this->_DataArray.
     * Failing either of those, it returns $default.
     *
     * @param string $fieldName
     * @param mixed $default
     * @return mixed
     *
     * @todo check returned value type
     */
    public function getValue($fieldName, $default = false) {
        $return = '';
        // Only retrieve values from the form collection if this is a postback.
        if ($this->isMyPostBack()) {
            $return = $this->getFormValue($fieldName, $default);
        } else {
            $return = val($fieldName, $this->_DataArray, $default);
        }
        return $return;
    }

    /**
     * Disable inline errors (this is the default).
     */
    public function hideErrors() {
        $this->_InlineErrors = false;
    }

    /**
     * Examines the sent form variable collection to see if any data was sent
     * via the form back to the server. Returns TRUE on if anything is found.
     *
     * @return boolean
     */
    public function isPostBack() {
        /*
        2009-01-10 - $_GET should not dictate a "post" back.
        return count($_POST) > 0 ? TRUE : FALSE;

        2009-03-31 - switching back to "get" dictating a postback

        2012-06-27 - Using the request method to determine a postback.
        */

        switch (strtolower($this->Method)) {
            case 'get':
                return count($_GET) > 0 || (is_array($this->formValues()) && count($this->formValues()) > 0) ? true : false;
            default:
                return Gdn::request()->isPostBack();
        }
    }

    /**
     * Check if THIS particular form was submitted
     *
     * Just like isPostBack(), except auto populates FormValues and doesnt just check
     * "was some data submitted lol?!".
     *
     * @return boolean
     */
    public function isMyPostBack() {
        switch (strtolower($this->Method)) {
            case 'get':
                return count($_GET) > 0 || (is_array($this->formValues()) && count($this->formValues()) > 0) ? true : false;
            default:
                return Gdn::request()->isPostBack();
        }
    }

    /**
     * This is a convenience method so that you don't have to code this every time
     * you want to save a simple model's data.
     *
     * It uses the assigned model to save the sent form fields.
     * If saving fails, it populates $this->_ValidationResults with validation errors & related fields.
     *
     * @return unknown
     */
    public function save() {
        $saveResult = false;
        if ($this->errorCount() == 0) {
            if (!isset($this->_Model)) {
                trigger_error(
                    errorMessage(
                        "You cannot call the form's save method if a model has not been defined.",
                        "Form",
                        "Save"
                    ),
                    E_USER_ERROR
                );
            }

            $data = $this->formValues();
            if (method_exists($this->_Model, 'FilterForm')) {
                $data = $this->_Model->filterForm($this->formValues());
            }

            $args = array_merge(
                func_get_args(),
                [
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null]
            );
            $saveResult = $this->_Model->save(
                $data,
                $args[0],
                $args[1],
                $args[2],
                $args[3],
                $args[4],
                $args[5],
                $args[6],
                $args[7],
                $args[8],
                $args[9]
            );
            if ($saveResult === false) {
                // NOTE: THE VALIDATION FUNCTION NAMES ARE ALSO THE LANGUAGE
                // TRANSLATIONS OF THE ERROR MESSAGES. CHECK THEM OUT IN THE LOCALE
                // FILE.
                $this->setValidationResults($this->_Model->validationResults());
            }
        }
        return $saveResult;
    }

    /**
     * Save an image from a field.
     *
     * @param string $field The name of the field. The image will be uploaded with the _New extension while the current image will be just the field name.
     * @param array $options
     *  - CurrentImage: Current image to clean if the save is successful
     * @return bool
     */
    public function saveImage($field, $options = []) {
        $upload = new Gdn_UploadImage();

        $fileField = str_replace('.', '_', $field);

        if (!getValueR("{$fileField}_New.name", $_FILES)) {
            trace("$field not uploaded, returning.");
            return false;
        }

        // First make sure the file is valid.
        try {
            $tmpName = $upload->validateUpload($fileField.'_New', true);

            if (!$tmpName) {
                return false; // no file uploaded.
            }
        } catch (Exception $ex) {
            $this->addError($ex);
            return false;
        }

        // Get the file extension of the file.
            $ext = val('OutputType', $options, trim($upload->getUploadedFileExtension(), '.'));
        if ($ext == 'jpeg') {
            $ext = 'jpg';
        }
            trace($ext, 'Ext');

        // The file is valid so let's come up with its new name.
        if (isset($options['Name'])) {
            $name = $options['Name'];
        } elseif (isset($options['Prefix']))
            $name = $options['Prefix'].md5(microtime()).'.'.$ext;
        else {
            $name = md5(microtime()).'.'.$ext;
        }

        // We need to parse out the size.
            $size = val('Size', $options);
        if ($size) {
            if (is_numeric($size)) {
                touchValue('Width', $options, $size);
                touchValue('Height', $options, $size);
            } elseif (preg_match('`(\d+)x(\d+)`i', $size, $m)) {
                touchValue('Width', $options, $m[1]);
                touchValue('Height', $options, $m[2]);
            }
        }

            trace($options, "Saving image $name.");
        try {
            $parsed = $upload->saveImageAs($tmpName, $name, val('Height', $options, ''), val('Width', $options, ''), $options);
            trace($parsed, 'Saved Image');

            if (val('DeleteOriginal', $options, false)) {
                deprecated('Option DeleteOriginal', 'CurrentImage');
            }

            $currentImage = val('CurrentImage', $options, false);
            if ($currentImage) {
                trace("Deleting original image: $currentImage.");
                $upload->delete($currentImage);
            }

            // Set the current value.
            $this->setFormValue($field, $parsed['SaveName']);
        } catch (Exception $ex) {
            $this->addError($ex);
        }
    }

    /**
     * Assign a set of data to be displayed in the form elements.
     *
     * @param array $data A result resource or associative array containing data to be filled in
     */
    public function setData($data) {
        if (is_object($data) === true) {
            // If this is a result object (/garden/library/database/class.dataset.php)
            // retrieve it's values as arrays
            if ($data instanceof DataSet) {
                $resultSet = $data->resultArray();
                if (count($resultSet) > 0) {
                    $this->_DataArray = $resultSet[0];
                }

            } else {
                // Otherwise assume it is an object representation of a data row.
                $this->_DataArray = Gdn_Format::objectAsArray($data);
            }
        } elseif (is_array($data)) {
            $this->_DataArray = $data;
        }
    }

    /**
     * Sets the value associated with $fieldName from the sent form fields.
     * Essentially overwrites whatever was retrieved from the form.
     *
     * @param string $fieldName The name of the field to set the value of.
     * @param mixed $value The new value of $fieldName.
     */
    public function setFormValue($fieldName, $value = null) {
        $this->formValues();
        if (is_array($fieldName)) {
            $this->_FormValues = array_merge($this->_FormValues, $fieldName);
        } else {
            $this->_FormValues[$fieldName] = $value;
        }
    }

    /**
     * Remove an element from a form.
     *
     * @param string $fieldName
     */
    public function removeFormValue($fieldName) {
        $this->formValues();

        if (!is_array($fieldName)) {
            $fieldName = [$fieldName];
        }

        foreach ($fieldName as $field) {
            unset($this->_FormValues[$field]);
        }
    }

    /**
     * Set the name of the model that will enforce data rules on $this->_DataArray.
     *
     * This value is also used to identify fields in the $_POST or $_GET
     * (depending on the forms method) collection when the form is submitted.
     *
     * @param Gdn_Model $model The Model that will enforce data rules on $this->_DataArray. This value
     *  is passed by reference so any changes made to the model outside this
     *  object apply when it is referenced here.
     * @param Ressource $dataSet A result resource containing data to be filled in the form.
     */
    public function setModel($model, $dataSet = false) {
        $this->_Model = $model;

        if ($dataSet !== false) {
            $this->setData($dataSet);
        }
    }

    /**
     *
     *
     * @param $validationResults
     */
    public function setValidationResults($validationResults) {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = [];
        }

        $this->_ValidationResults = array_merge_recursive($this->_ValidationResults, $validationResults);
    }

    /**
     * Sets the value associated with $fieldName.
     *
     * It sets the value in $this->_DataArray rather than in $this->_FormValues.
     *
     * @param string $fieldName
     * @param mixed $Default
     */
    public function setValue($fieldName, $value) {
        if (!is_array($this->_DataArray)) {
            $this->_DataArray = [];
        }

        $this->_DataArray[$fieldName] = $value;
    }

    /**
     * Enable inline errors.
     */
    public function showErrors() {
        $this->_InlineErrors = true;
    }

    /**
     * Generates a multi-field form from a schema.
     *
     * @param array $schema An array where each item of the array is a row that identifies a form field with the following information:
     *  - Name: The name of the form field.
     *  - Control: The type of control used for the field. This is one of the control methods on the Gdn_Form object.
     *  - LabelCode: The translation code for the label. Optional.
     *  - Description: An optional description for the field.
     *  - Items: If the control is a list control then its items are specified here.
     *  - Options: Additional options to be passed into the control.
     * @param type $options Additional options to pass into the form.
     *  - Wrap: A two item array specifying the text to wrap the form in.
     *  - ItemWrap: A two item array specifying the text to wrap each form item in.
     */
    public function simple($schema, $options = []) {
        $result = valr('Wrap.0', $options, '<ul>');

        foreach ($schema as $index => $row) {
            if (is_string($row)) {
                $row = ['Name' => $index, 'Control' => $row];
            }

            if (!isset($row['Name'])) {
                $row['Name'] = $index;
            }
            if (!isset($row['Options'])) {
                $row['Options'] = [];
            }

            touchValue('Control', $row, 'TextBox');

            if (strtolower($row['Control']) == 'callback') {
                $itemWrap = '';
            } else {
                $defaultWrap = ['<li class="'.$this->getStyle('form-group')."\">\n", "\n</li>\n"];
                $itemWrap = val('ItemWrap', $row, val('ItemWrap', $options, $defaultWrap));
            }

            $result .= $itemWrap[0];

            $labelCode = self::labelCode($row);

            $image = '';

            if (strtolower($row['Control']) == 'imageupload') {
                $image = $this->currentImage($row['Name'], $row['Options']);
                $image = wrap($image, 'div', ['class' => 'image-wrap-label']);
            }

            $description = val('Description', $row, '');

            if ($description) {
                $description = wrap($description, 'div', ['class' => 'description info']);
            }

            $description .= $image;

            $labelOptions = [];
            if (arrayValueI('id', $row['Options'])) {
                $labelOptions['for'] = arrayValueI('id', $row['Options']);
            }
            if ($description) {
                $labelWrap = wrap($this->label($labelCode, $row['Name'], $labelOptions).$description, 'div', ['class' => 'label-wrap']);
            } else {
                $labelWrap = wrap($this->label($labelCode, $row['Name'], $labelOptions), 'div', ['class' => 'label-wrap']);
            }

            switch (strtolower($row['Control'])) {
                case 'categorydropdown':
                    $result .= $this->label($labelCode, $row['Name'])
                        .$description
                        .$this->categoryDropDown($row['Name'], $row['Options']);
                    break;
                case 'checkbox':
                    $result .= $labelWrap
                        .wrap($this->checkBox($row['Name'], $labelCode, $row['Options']), 'div', ['class' => 'input-wrap']);
                    break;
                case 'toggle':
                    $result .= $this->toggle($row['Name'], $labelCode, $row['Options'], $description);
                    break;
                case 'dropdown':
                    $row['Options']['Wrap'] = true;
                    $result .= $labelWrap
                        .$this->dropDown($row['Name'], $row['Items'], $row['Options']);
                    break;
                case 'radiolist':
                    $result .= $labelWrap
                        .wrap($this->radioList($row['Name'], $row['Items'], $row['Options']), 'div', ['class' => 'input-wrap']);
                    break;
                case 'checkboxlist':
                    $result .= $labelWrap
                        .wrap($this->checkBoxList($row['Name'], $row['Items'], null, $row['Options']), 'div', ['class' => 'input-wrap']);
                    break;
                case 'imageupload':
                    $result .= $labelWrap
                        .$this->imageUploadWrap($row['Name'], $row['Options']);
                    break;
                case 'textbox':
                    $row['Options']['Wrap'] = true;
                    $result .= $labelWrap
                        .$this->textBox($row['Name'], $row['Options']);
                    break;
                case 'callback':
                    $row['DescriptionHtml'] = $description;
                    $row['LabelCode'] = $labelCode;
                    $result .= call_user_func($row['Callback'], $this, $row);
                    break;
                default:
                    $result .= "Error a control type of {$row['Control']} is not supported.";
                    break;
            }
            $result .= $itemWrap[1];
        }
        $result .= valr('Wrap.1', $options, '</ul>');
        return $result;
    }

    /**
     * If not saving data directly to the model, this method allows you to
     * utilize a model's schema to validate a form's inputs regardless.
     *
     * ie. A sign-in form that just needs to compare data to the model and still
     * enforce it's rules. Returns the number of errors that were recorded
     * through validation.
     *
     * @return int
     */
    public function validateModel() {
        $this->_Model->defineSchema();
        if ($this->_Model->Validation->validate($this->formValues()) === false) {
            $this->_ValidationResults = $this->_Model->validationResults();
        }
        return $this->errorCount();
    }

    /**
     * Validates a rule on the form and adds its result to the errors collection.
     *
     * @param string $fieldName The name of the field to validate.
     * @param string|array $rule The rule to validate against.
     * @param string $customError A custom error string.
     * @return bool Whether or not the rule succeeded.
     *
     * @see Gdn_Validation::validateRule()
     */
    public function validateRule($fieldName, $rule, $customError = '') {
        $value = $this->getFormValue($fieldName);
        $valid = Gdn_Validation::validateRule($value, $fieldName, $rule, $customError);

        if ($valid === true) {
            return true;
        } else {
            $this->addError('@'.$valid, $fieldName);
            return false;
        }

    }

    /**
     * Gets the validation results in the form.
     *
     * @return array
     */
    public function validationResults() {
        return $this->_ValidationResults;
    }


    /**
     * Takes an associative array of $attributes and returns them as a string of
     * param="value" sets to be placed in an input, select, textarea, etc tag.
     *
     * @param array $attributes An associative array of attribute key => value pairs to be converted to a
     *    string. A number of "reserved" keys will be ignored: 'id', 'name',
     *    'maxlength', 'value', 'method', 'action', 'type'.
     * @return string
     */
    protected function _attributesToString($attributes) {
        $reservedAttributes = [
            'id',
            'name',
            'value',
            'method',
            'action',
            'type',
            'for',
            'multiline',
            'default',
            'textfield',
            'valuefield',
            'includenull',
            'yearrange',
            'fields',
            'inlineerrors',
            'wrap',
            'categorydata'
        ];
        $return = '';

        // Build string from array
        if (is_array($attributes)) {
            foreach ($attributes as $attribute => $value) {
                // Ignore reserved attributes
                if (!in_array(strtolower($attribute), $reservedAttributes)) {
                    $return .= ' '.$attribute.($value === true ? '' : '="'.htmlspecialchars($value, ENT_COMPAT, 'UTF-8').'"');
                }
            }
        }
        return $return;
    }

    /**
     * Creates an ID attribute for a form input and returns it in this format: [ id="IDNAME"]
     *
     * @param string $fieldName The name of the field that is being converted to an ID attribute.
     * @param array $attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $attributes contains an 'id' key, it will override the
     *    one automatically generated by $fieldName.
     * @return string
     */
    protected function _idAttribute($fieldName, $attributes) {
        // ID from attributes overrides the default.
        $id = arrayValueI('id', $attributes, false);
        if (!$id) {
            $id = $this->escapeID($fieldName);
        }

        if (isset(self::$idCounters[$id])) {
            $id .= self::$idCounters[$id]++;
        } else {
            self::$idCounters[$id] = 1;
        }

        return ' id="'.htmlspecialchars($id).'"';
    }

    /**
     * Creates a NAME attribute for a form input and returns it in this format: [ name="NAME"]
     *
     * @param string $fieldName The name of the field that is being converted to a NAME attribute.
     * @param array $attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $attributes contains a 'name' key, it will override the
     *    one automatically generated by $fieldName.
     * @return string
     */
    protected function _nameAttribute($fieldName, $attributes) {
        // Name from attributes overrides the default.
        $name = $this->escapeFieldName(arrayValueI('name', $attributes, $fieldName));
        return ' name="'.htmlspecialchars($name).'"';
    }

    /**
     * Creates a VALUE attribute for a form input and returns it in this format: [ value="VALUE"]
     *
     * @param string $fieldName The name of the field that contains the value in $this->_DataArray.
     * @param array $attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $attributes contains a 'value' key, it will override the
     *    one automatically generated by $fieldName.
     * @return string
     */
    protected function _valueAttribute($fieldName, $attributes) {
        // Value from $Attributes overrides the datasource and the postback.
        return ' value="'.Gdn_Format::form(arrayValueI('value', $attributes, $this->getValue($fieldName))).'"';
    }
}
