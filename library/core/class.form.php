<?php
/**
 * Gdn_Form.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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

    /**
     * @var string All form-related elements (form, input, select, etc) will have
     *    this value prefixed on their name attribute. Default is "Form".
     *    If a model is assigned, the model name is used instead.
     */
    public $InputPrefix = '';

    /** @var string Form submit method. Options are 'post' or 'get'. */
    public $Method = 'post';

    /**
     * @var array Associative array containing the key => value pairs being placed in the
     *    controls returned by this object. Assigned by $this->Open() or $this->SetData().
     */
    protected $_DataArray;

    /** @var bool Whether to display inline errors with form elements. Set with ShowErrors() and HideErrors(). */
    protected $_InlineErrors = false;

    /** @var object Model that enforces data rules on $this->_DataArray. */
    protected $_Model;

    /**
     * @var array Associative array of $FieldName => $ValidationFunctionName arrays that
     *    describe how each field specified failed validation.
     */
    protected $_ValidationResults = array();

    /**
     * @var array $Field => $Value pairs from the form in the $_POST or $_GET collection
     *    (depending on which method was specified for sending form data in $this->Method).
     *    Populated & accessed by $this->FormValues().
     *    Values can be retrieved with $this->GetFormValue($FieldName).
     */
    public $_FormValues;

    /**
     * @var array Collection of IDs that have been created for form elements. This
     *    private property is used to record all IDs so that duplicate IDs are not
     *    added to the screen.
     */
    private $_IDCollection = array();

    /**
     * Constructor
     *
     * @param string $TableName
     */
    public function __construct($TableName = '') {
        if ($TableName != '') {
            $TableModel = new Gdn_Model($TableName);
            $this->setModel($TableModel);
        }

        // Get custom error class
        $this->ErrorClass = C('Garden.Forms.InlineErrorClass', 'Error');

        parent::__construct();
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
     * @param array $Attributes Field attributes passed by reference (property => value).
     */
    public function addErrorClass(&$Attributes) {
        if (isset($Attributes['class'])) {
            $Attributes['class'] .= ' '.$this->ErrorClass;
        } else {
            $Attributes['class'] = $this->ErrorClass;
        }
    }

    /**
     * A special text box for formattable text.
     *
     * Formatting plugins like ButtonBar will auto-attach to this element.
     *
     * @param string $Column
     * @param array $Attributes
     * @since 2.1
     * @return string HTML element.
     */
    public function bodyBox($Column = 'Body', $Attributes = array()) {
        touchValue('MultiLine', $Attributes, true);
        touchValue('Wrap', $Attributes, true);
        touchValue('class', $Attributes, '');
        $Attributes['class'] .= ' TextBox BodyBox';

        $this->setValue('Format', val('Format', $Attributes, $this->getValue('Format', Gdn_Format::defaultFormat())));

        $Result = '<div class="bodybox-wrap">';

        // BeforeBodyBox
        $this->EventArguments['Table'] = val('Table', $Attributes);
        $this->EventArguments['Column'] = $Column;
        $this->EventArguments['Attributes'] = $Attributes;
        $this->EventArguments['BodyBox'] =& $Result;
        $this->fireEvent('BeforeBodyBox');

        // Only add the format if it was set on the form. This allows plugins to remove the format.
        if ($format = $this->getValue('Format')) {
            $Attributes['format'] = htmlspecialchars($format);
            $this->setValue('Format', $Attributes['format']);
            $Result .= $this->hidden('Format');
        }

        $Result .= $this->textBox($Column, $Attributes);

        $Result .= '</div>';

        return $Result;
    }

    /**
     * Returns XHTML for a button.
     *
     * @param string $ButtonCode The translation code for the text on the button.
     * @param array $Attributes An associative array of attributes for the button. Here is a list of
     * "special" attributes and their default values:
     * Attribute  Options                        Default
     * ------------------------------------------------------------------------
     * Type       The type of submit button      'submit'
     * Value      Ignored for $ButtonCode        $ButtonCode translated
     *
     * @return string
     */
    public function button($ButtonCode, $Attributes = false) {
        $Type = arrayValueI('type', $Attributes);
        if ($Type === false) {
            $Type = 'submit';
        }

        $CssClass = arrayValueI('class', $Attributes);
        if ($CssClass === false) {
            $Attributes['class'] = 'Button';
        }

        $Return = '<input type="'.$Type.'"';
        $Return .= $this->_idAttribute($ButtonCode, $Attributes);
        $Return .= $this->_nameAttribute($ButtonCode, $Attributes);
        $Return .= ' value="'.t($ButtonCode, arrayValue('value', $Attributes)).'"';
        $Return .= $this->_attributesToString($Attributes);
        $Return .= " />\n";
        return $Return;
    }

    /**
     * Returns XHTML for a standard calendar input control.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     * should related directly to a field name in $this->_DataArray.
     * @param array $Attributes An associative array of attributes for the input. ie. onclick, class, etc
     * @return string
     * @todo Create calendar helper
     */
    public function calendar($FieldName, $Attributes = false) {
        // TODO: CREATE A CALENDAR HELPER CLASS AND LOAD/REFERENCE IT HERE.
        // THE CLASS SHOULD BE DECLARED WITH:
        //  if (!class_exists('Calendar') {
        // AT THE BEGINNING SO OTHERS CAN OVERRIDE THE DEFAULT CALENDAR WITH ONE
        // OF THEIR OWN.
        $Class = arrayValueI(
            'class',
            $Attributes,
            false
        );
        if ($Class === false) {
            $Attributes['class'] = 'DateBox';
        }

        // IN THE MEANTIME...
        return $this->input($FieldName, 'text', $Attributes);
    }

    /**
     * Returns Captcha HTML & adds translations to document head.
     *
     * @return string
     */
    public function captcha() {
        // Google whitelist
        $Whitelist = array('ar', 'bg', 'ca', 'zh-CN', 'zh-TW', 'hr', 'cs', 'da', 'nl', 'en-GB', 'en', 'fil', 'fi', 'fr', 'fr-CA', 'de', 'de-AT', 'de-CH', 'el', 'iw', 'hi', 'hu', 'id', 'it', 'ja', 'ko', 'lv', 'lt', 'no', 'fa', 'pl', 'pt', 'pt-BR', 'pt-PT', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'es-419', 'sv', 'th', 'tr', 'uk', 'vi');

        // reCAPTCHA Options
        $Options = array(
            'custom_translations' => array(
                'instructions_visual' => t("Type the text:"),
                'instructions_audio' => t("Type what you hear:"),
                'play_again' => t("Play the sound again"),
                'cant_hear_this' => t("Download the sounds as MP3"),
                'visual_challenge' => t("Get a visual challenge"),
                'audio_challenge' => t("Get an audio challenge"),
                'refresh_btn' => t("Get a new challenge"),
                'help_btn' => t("Help"),
                'incorrect_try_again' => t("Incorrect. Try again.")
            )
        );

        // Use our current locale against the whitelist.
        $Language = Gdn::locale()->language();
        if (!in_array($Language, $Whitelist)) {
            $Language = (in_array(Gdn::locale()->Locale, $Whitelist)) ? Gdn::locale()->Locale : false;
        }
        if ($Language) {
            $Options['lang'] = $Language;
        }

        // Add custom translation strings as JSON.
        Gdn::controller()->Head->addString('<script type="text/javascript">var RecaptchaOptions = '.json_encode($Options).';</script>');

        require_once PATH_LIBRARY.'/vendors/recaptcha/functions.recaptchalib.php';

        return recaptcha_get_html(c('Garden.Registration.CaptchaPublicKey'), null, Gdn::request()->scheme() == 'https');
    }

    /**
     * Returns XHTML for a select list containing categories that the user has
     * permission to use.
     *
     * @param array $FieldName An array of category data to render.
     * @param array $Options An associative array of options for the select. Here
     * is a list of "special" options and their default values:
     *
     *   Attribute     Options                        Default
     *   ------------------------------------------------------------------------
     *   Value         The ID of the category that    FALSE
     *                 is selected.
     *   IncludeNull   Include a blank row?           TRUE
     *   CategoryData  Custom set of categories to    CategoryModel::Categories()
     *                 display.
     *
     * @return string
     */
    public function categoryDropDown($FieldName = 'CategoryID', $Options = false) {
        $Value = arrayValueI('Value', $Options); // The selected category id
        $CategoryData = val('CategoryData', $Options);

        // Sanity check
        if (is_object($CategoryData)) {
            $CategoryData = (array)$CategoryData;
        } elseif (!is_array($CategoryData)) {
            $CategoryData = array();
        }

        $Permission = GetValue('Permission', $Options, 'add');

        // Grab the category data.
        if (!$CategoryData) {
            $CategoryData = CategoryModel::GetByPermission(
                'Discussions.View',
                $Value,
                val('Filter', $Options, array('Archived' => 0)),
                val('PermFilter', $Options, array())
            );
        }

        // Respect category permissions (remove categories that the user shouldn't see).
        $SafeCategoryData = array();
        foreach ($CategoryData as $CategoryID => $Category) {
            $Name = $Category['Name'];

            if ($Value != $CategoryID) {
                if ($Category['CategoryID'] <= 0 || !$Category['PermsDiscussionsView']) {
                    continue;
                }

                if ($Category['Archived']) {
                    continue;
                }
            }

            $SafeCategoryData[$CategoryID] = $Category;
        }

        unset($Options['Filter'], $Options['PermFilter']);

        // Opening select tag
        $Return = '<select';
        $Return .= $this->_idAttribute($FieldName, $Options);
        $Return .= $this->_nameAttribute($FieldName, $Options);
        $Return .= $this->_attributesToString($Options);
        $Return .= ">\n";

        // Get value from attributes
        if ($Value === false) {
            $Value = $this->getValue($FieldName);
        }
        if (!is_array($Value)) {
            $Value = array($Value);
        }

        // Prevent default $Value from matching key of zero
        $HasValue = ($Value !== array(false) && $Value !== array('')) ? true : false;

        // Start with null option?
        $IncludeNull = val('IncludeNull', $Options);
        if ($IncludeNull === true) {
            $Return .= '<option value="">'.t('Select a category...').'</option>';
        } elseif (is_array($IncludeNull))
            $Return .= "<option value=\"{$IncludeNull[0]}\">{$IncludeNull[1]}</option>\n";
        elseif ($IncludeNull)
            $Return .= "<option value=\"\">$IncludeNull</option>\n";
        elseif (!$HasValue)
            $Return .= '<option value=""></option>';

        // Show root categories as headings (ie. you can't post in them)?
        $DoHeadings = val('Headings', $Options, C('Vanilla.Categories.DoHeadings'));

        // If making headings disabled and there was no default value for
        // selection, make sure to select the first non-disabled value, or the
        // browser will auto-select the first disabled option.
        $ForceCleanSelection = ($DoHeadings && !$HasValue && !$IncludeNull);

        // Write out the category options
        if (is_array($SafeCategoryData)) {
            foreach ($SafeCategoryData as $CategoryID => $Category) {
                $Depth = val('Depth', $Category, 0);
                $Disabled = (($Depth == 1 && $DoHeadings) || !$Category['AllowDiscussions']);
                $Selected = in_array($CategoryID, $Value) && $HasValue;
                if ($ForceCleanSelection && $Depth > 1) {
                    $Selected = true;
                    $ForceCleanSelection = false;
                }

                if ($Category['AllowDiscussions']) {
                    $Disabled &= $Permission == 'add' && !$Category['PermsDiscussionsAdd'];
                }

                $Return .= '<option value="'.$CategoryID.'"';
                if ($Disabled) {
                    $Return .= ' disabled="disabled"';
                } elseif ($Selected) {
                    $Return .= ' selected="selected"'; // only allow selection if NOT disabled
                }
                $Name = htmlspecialchars(val('Name', $Category, 'Blank Category Name'));
                if ($Depth > 1) {
                    $Name = str_repeat('&#160;', 4 * ($Depth - 1)).$Name;
//               $Name = str_replace(' ', '&#160;', $Name);
                }

                $Return .= '>'.$Name."</option>\n";
            }
        }
        return $Return.'</select>';
    }

    /**
     * Returns XHTML for a checkbox input element.
     *
     * Cannot consider all checkbox values to be boolean. (2009-04-02 mosullivan)
     * Cannot assume checkboxes are stored in database as string 'TRUE'. (2010-07-28 loki_racer)
     *
     * @param string $FieldName Name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray.
     * @param string $Label Label to place next to the checkbox.
     * @param array $Attributes Associative array of attributes for the input. (e.g. onclick, class)\
     *    Setting 'InlineErrors' to FALSE prevents error message even if $this->InlineErrors is enabled.
     * @return string
     */
    public function checkBox($FieldName, $Label = '', $Attributes = false) {
        $Value = arrayValueI('value', $Attributes, true);
        $Attributes['value'] = $Value;
        $Display = val('display', $Attributes, 'wrap');
        unset($Attributes['display']);

        if (stringEndsWith($FieldName, '[]')) {
            if (!isset($Attributes['checked'])) {
                $GetValue = $this->getValue(substr($FieldName, 0, -2));
                if (is_array($GetValue) && in_array($Value, $GetValue)) {
                    $Attributes['checked'] = 'checked';
                } elseif ($GetValue == $Value)
                    $Attributes['checked'] = 'checked';
            }
        } else {
            if ($this->getValue($FieldName) == $Value) {
                $Attributes['checked'] = 'checked';
            }
        }

        // Show inline errors?
        $ShowErrors = ($this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults));

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        $Input = $this->input($FieldName, 'checkbox', $Attributes);
        if ($Label != '') {
            $LabelElement = '<label for="'.
                arrayValueI('id', $Attributes, $this->escapeID($FieldName, false)).
                '" class="'.val('class', $Attributes, 'CheckBoxLabel').'"'.
                attribute('title', val('title', $Attributes)).'>';

            if ($Display === 'wrap') {
                $Input = $LabelElement.$Input.' '.T($Label).'</label>';
            } elseif ($Display === 'before') {
                $Input = $LabelElement.T($Label).'</label> '.$Input;
            } else {
                $Input = $Input.' '.$LabelElement.T($Label).'</label>';
            }
        }

        // Append validation error message
        if ($ShowErrors && arrayValueI('InlineErrors', $Attributes, true)) {
            $Return .= $this->inlineError($FieldName);
        }

        return $Input;
    }

    /**
     * Returns the XHTML for a list of checkboxes.
     *
     * @param string $FieldName Name of the field being posted with this input.
     *
     * @param mixed $DataSet Data to fill the checkbox list. Either an associative
     * array or a database dataset. ex: RoleID, Name from GDN_Role.
     *
     * @param mixed $ValueDataSet Values to be pre-checked in $DataSet. Either an associative array
     * or a database dataset. ex: RoleID from GDN_UserRole for a single user.
     *
     * @param array $Attributes An associative array of attributes for the select. Here is a list of
     * "special" attributes and their default values:
     * Attribute   Options                        Default
     * ------------------------------------------------------------------------
     * ValueField  The name of the field in       'value'
     *             $DataSet that contains the
     *             option values.
     * TextField   The name of the field in       'text'
     *             $DataSet that contains the
     *             option text.
     *
     * @return string
     */
    public function checkBoxList($FieldName, $DataSet, $ValueDataSet = null, $Attributes = false) {
        // Never display individual inline errors for these CheckBoxes
        $Attributes['InlineErrors'] = false;

        $Return = '';
        // If the form hasn't been posted back, use the provided $ValueDataSet
        if ($this->isPostBack() === false) {
            if ($ValueDataSet === null) {
                $CheckedValues = $this->getValue($FieldName);
            } else {
                $CheckedValues = $ValueDataSet;
                if (is_object($ValueDataSet)) {
                    $CheckedValues = array_column($ValueDataSet->resultArray(), $FieldName);
                }
            }
        } else {
            $CheckedValues = $this->getFormValue($FieldName, array());
        }
        $i = 1;
        if (is_object($DataSet)) {
            $ValueField = ArrayValueI('ValueField', $Attributes, 'value');
            $TextField = ArrayValueI('TextField', $Attributes, 'text');
            foreach ($DataSet->result() as $Data) {
                $Instance = $Attributes;
                $Instance = removeKeyFromArray(
                    $Instance,
                    array('TextField', 'ValueField')
                );
                $Instance['value'] = $Data->$ValueField;
                $Instance['id'] = $FieldName.$i;
                if (is_array($CheckedValues) && in_array(
                    $Data->$ValueField,
                    $CheckedValues
                )
                ) {
                    $Instance['checked'] = 'checked';
                }

                $Return .= '<li>'.$this->checkBox(
                    $FieldName.'[]',
                    $Data->$TextField,
                    $Instance
                )."</li>\n";
                ++$i;
            }
        } elseif (is_array($DataSet)) {
            foreach ($DataSet as $Text => $ID) {
                // Set attributes for this instance
                $Instance = $Attributes;
                $Instance = removeKeyFromArray($Instance, array('TextField', 'ValueField'));

                $Instance['id'] = $FieldName.$i;

                if (is_array($ID)) {
                    $ValueField = arrayValueI('ValueField', $Attributes, 'value');
                    $TextField = arrayValueI('TextField', $Attributes, 'text');
                    $Text = val($TextField, $ID, '');
                    $ID = val($ValueField, $ID, '');
                } else {
                    if (is_numeric($Text)) {
                        $Text = $ID;
                    }
                }
                $Instance['value'] = $ID;

                if (is_array($CheckedValues) && in_array($ID, $CheckedValues)) {
                    $Instance['checked'] = 'checked';
                }

                $Return .= '<li>'.$this->checkBox($FieldName.'[]', $Text, $Instance)."</li>\n";
                ++$i;
            }
        }

        return '<ul class="'.concatSep(' ', 'CheckBoxList', val('listclass', $Attributes)).'">'.$Return.'</ul>';
    }

    /**
     * Returns the xhtml for a list of checkboxes; sorted into groups related to
     * the TextField value of the dataset.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     * should related directly to a field name in a user junction table.
     * ie. LUM_UserRole.RoleID
     *
     * @param mixed $DataSet The data to fill the options in the select list. Either an associative
     * array or a database dataset. ie. RoleID, Name from LUM_Role.
     *
     * @param mixed $ValueDataSet The data that should be checked in $DataSet. Either an associative array
     * or a database dataset. ie. RoleID from LUM_UserRole for a single user.
     *
     * @param array $Attributes An associative array of attributes for the select. Here is a list of
     * "special" attributes and their default values:
     *
     * Attribute   Options                        Default
     * ------------------------------------------------------------------------
     * ValueField  The name of the field in       'value'
     *             $DataSet that contains the
     *             option values.
     * TextField   The name of the field in       'text'
     *             $DataSet that contains the
     *             option text.
     *
     * @return string
     */
    public function checkBoxGrid($FieldName, $DataSet, $ValueDataSet, $Attributes) {
        // Never display individual inline errors for these CheckBoxes
        $Attributes['InlineErrors'] = false;

        $Return = '';
        $CheckedValues = $ValueDataSet;
        if (is_object($ValueDataSet)) {
            $CheckedValues = array_column($ValueDataSet->resultArray(), $FieldName);
        }

        $i = 1;
        if (is_object($DataSet)) {
            $ValueField = arrayValueI('ValueField', $Attributes, 'value');
            $TextField = arrayValueI('TextField', $Attributes, 'text');
            $LastGroup = '';
            $Group = array();
            $Rows = array();
            $Cols = array();
            $CheckBox = '';
            foreach ($DataSet->result() as $Data) {
                // Define the checkbox
                $Instance = $Attributes;
                $Instance = removeKeyFromArray($Instance, array('TextField', 'ValueField'));
                $Instance['value'] = $Data->$ValueField;
                $Instance['id'] = $FieldName.$i;
                if (is_array($CheckedValues) && in_array(
                    $Data->$ValueField,
                    $CheckedValues
                )
                ) {
                    $Instance['checked'] = 'checked';
                }
                $CheckBox = $this->checkBox($FieldName.'[]', '', $Instance);

                // Organize the checkbox into an array for this group
                $CurrentTextField = $Data->$TextField;
                $aCurrentTextField = explode('.', $CurrentTextField);
                $aCurrentTextFieldCount = count($aCurrentTextField);
                $GroupName = array_shift($aCurrentTextField);
                $ColName = array_pop($aCurrentTextField);
                if ($aCurrentTextFieldCount >= 3) {
                    $RowName = implode('.', $aCurrentTextField);
                    if ($GroupName != $LastGroup && $LastGroup != '') {
                        // Render the last group
                        $Return .= $this->getCheckBoxGridGroup(
                            $LastGroup,
                            $Group,
                            $Rows,
                            $Cols
                        );

                        // Clean out the $Group array & Rowcount
                        $Group = array();
                        $Rows = array();
                        $Cols = array();
                    }

                    if (array_key_exists($ColName, $Group) === false || is_array($Group[$ColName]) === false) {
                        $Group[$ColName] = array();
                        if (!in_array($ColName, $Cols)) {
                            $Cols[] = $ColName;
                        }

                    }

                    if (!in_array($RowName, $Rows)) {
                        $Rows[] = $RowName;
                    }

                    $Group[$ColName][$RowName] = $CheckBox;
                    $LastGroup = $GroupName;
                }
                ++$i;
            }
        }
        return $Return.$this->getCheckBoxGridGroup($LastGroup, $Group, $Rows, $Cols);
    }

    /**
     *
     *
     * @param $Data
     * @param $FieldName
     * @return string
     */
    public function checkBoxGridGroups($Data, $FieldName) {
        $Result = '';
        foreach ($Data as $GroupName => $GroupData) {
            $Result .= $this->checkBoxGridGroup($GroupName, $GroupData, $FieldName)."\n";
        }
        return $Result;
    }

    /**
     *
     *
     * @param $GroupName
     * @param $Data
     * @param $FieldName
     * @return string
     */
    public function checkBoxGridGroup($GroupName, $Data, $FieldName) {
        // Never display individual inline errors for these CheckBoxes
        $Attributes['InlineErrors'] = false;

        // Get the column and row info.
        $Columns = $Data['_Columns'];
        ksort($Columns);
        $Rows = $Data['_Rows'];
        ksort($Rows);
        unset($Data['_Columns'], $Data['_Rows']);

        if (array_key_exists('_Info', $Data)) {
            $GroupName = $Data['_Info']['Name'];
            unset($Data['_Info']);
        }

        $Result = '<table class="CheckBoxGrid">';
        // Append the header.
        $Result .= '<thead><tr><th>'.T($GroupName).'</th>';
        $Alt = true;
        foreach ($Columns as $ColumnName => $X) {
            $Result .=
                '<td'.($Alt ? ' class="Alt"' : '').'>'
                .T($ColumnName)
                .'</td>';

            $Alt = !$Alt;
        }
        $Result.'</tr></thead>';

        // Append the rows.
        $Result .= '<tbody>';
        $CheckCount = 0;
        foreach ($Rows as $RowName => $X) {
            $Result .= '<tr><th>';

            // If the row name is still seperated by dots then put those in spans.
            $RowNames = explode('.', $RowName);
            for ($i = 0; $i < count($RowNames) - 1; ++$i) {
                $Result .= '<span class="Parent">'.T($RowNames[$i]).'</span>';
            }
            $Result .= T(self::labelCode($RowNames[count($RowNames) - 1])).'</th>';
            // Append the columns within the rows.
            $Alt = true;
            foreach ($Columns as $ColumnName => $Y) {
                $Result .= '<td'.($Alt ? ' class="Alt"' : '').'>';
                // Check to see if there is a row corresponding to this area.
                if (array_key_exists($RowName.'.'.$ColumnName, $Data)) {
                    $CheckBox = $Data[$RowName.'.'.$ColumnName];
                    $Attributes = array('value' => $CheckBox['PostValue']);
                    if ($CheckBox['Value']) {
                        $Attributes['checked'] = 'checked';
                    }
//               $Attributes['id'] = "{$GroupName}_{$FieldName}_{$CheckCount}";
                    $CheckCount++;

                    $Result .= $this->checkBox($FieldName.'[]', '', $Attributes);
                } else {
                    $Result .= ' ';
                }
                $Result .= '</td>';

                $Alt = !$Alt;
            }
            $Result .= '</tr>';
        }
        $Result .= '</tbody></table>';
        return $Result;
    }

    /**
     * Returns the closing of the form tag with an optional submit button.
     *
     * @param string $ButtonCode
     * @param string $Xhtml
     * @return string
     */
    public function close($ButtonCode = '', $Xhtml = '', $Attributes = false) {
        $Return = "</div>\n</form>";
        if ($Xhtml != '') {
            $Return = $Xhtml.$Return;
        }

        if ($ButtonCode != '') {
            $Return = '<div class="Buttons">'.$this->button($ButtonCode, $Attributes).'</div>'.$Return;
        }

        return $Return;
    }

    /**
     * Returns the current image in a field.
     * This is meant to be used with image uploads so that users can see the current value.
     *
     * @param type $FieldName
     * @param type $Attributes
     * @since 2.1
     */
    public function currentImage($FieldName, $Attributes = array()) {
        $Result = $this->hidden($FieldName);

        $Value = $this->getValue($FieldName);
        if ($Value) {
            touchValue('class', $Attributes, 'CurrentImage');
            $Result .= img(Gdn_Upload::url($Value), $Attributes);
        }

        return $Result;
    }

    /**
     * Returns XHTML for a standard date input control.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     *    should related directly to a field name in $this->_DataArray.
     * @param array $Attributes An associative array of attributes for the input, e.g. onclick, class.
     *    Special attributes:
     *       YearRange, specified in yyyy-yyyy format. Default is 1900 to current year.
     *       Fields, array of month, day, year. Those are only valid values. Order matters.
     * @return string
     */
    public function date($FieldName, $Attributes = false) {
        $Return = '';
        $YearRange = arrayValueI('yearrange', $Attributes, false);
        $StartYear = 0;
        $EndYear = 0;
        if ($YearRange !== false) {
            if (preg_match("/^[\d]{4}-{1}[\d]{4}$/i", $YearRange) == 1) {
                $StartYear = substr($YearRange, 0, 4);
                $EndYear = substr($YearRange, 5);
            }
        }
        if ($YearRange === false || $StartYear > $EndYear) {
            $StartYear = 1900;
            $EndYear = date('Y');
        }

        $Months = array_map(
            'T',
            explode(',', 'Month,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec')
        );

        $Days = array();
        $Days[] = T('Day');
        for ($i = 1; $i < 32; ++$i) {
            $Days[] = $i;
        }

        $Years = array();
        $Years[0] = T('Year');
        for ($i = $StartYear; $i <= $EndYear; ++$i) {
            $Years[$i] = $i;
        }

        // Show inline errors?
        $ShowErrors = $this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults);

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        // Never display individual inline errors for these DropDowns
        $Attributes['InlineErrors'] = false;

        $CssClass = arrayValueI('class', $Attributes, '');

        $SubmittedTimestamp = ($this->getValue($FieldName) > 0) ? strtotime($this->getValue($FieldName)) : false;

        // Allow us to specify which fields to show & order
        $Fields = arrayValueI('fields', $Attributes, array('month', 'day', 'year'));
        if (is_array($Fields)) {
            foreach ($Fields as $Field) {
                switch ($Field) {
                    case 'month':
                        // Month select
                        $Attributes['class'] = trim($CssClass.' Month');
                        if ($SubmittedTimestamp) {
                            $Attributes['Value'] = date('n', $SubmittedTimestamp);
                        }
                        $Return .= $this->dropDown($FieldName.'_Month', $Months, $Attributes);
                        break;
                    case 'day':
                        // Day select
                        $Attributes['class'] = trim($CssClass.' Day');
                        if ($SubmittedTimestamp) {
                            $Attributes['Value'] = date('j', $SubmittedTimestamp);
                        }
                        $Return .= $this->dropDown($FieldName.'_Day', $Days, $Attributes);
                        break;
                    case 'year':
                        // Year select
                        $Attributes['class'] = trim($CssClass.' Year');
                        if ($SubmittedTimestamp) {
                            $Attributes['Value'] = date('Y', $SubmittedTimestamp);
                        }
                        $Return .= $this->dropDown($FieldName.'_Year', $Years, $Attributes);
                        break;
                }
            }
        }

        $Return .= '<input type="hidden" name="DateFields[]" value="'.$FieldName.'" />';

        // Append validation error message
        if ($ShowErrors) {
            $Return .= $this->inlineError($FieldName);
        }

        return $Return;
    }

    /**
     * Returns XHTML for a select list.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     *    should related directly to a field name in $this->_DataArray. ie. RoleID
     * @param mixed $DataSet The data to fill the options in the select list. Either an associative
     *    array or a database dataset.
     * @param array $Attributes An associative array of attributes for the select. Here is a list of
     *    "special" attributes and their default values:
     *
     *   Attribute   Options                        Default
     *   ------------------------------------------------------------------------
     *   ValueField  The name of the field in       'value'
     *               $DataSet that contains the
     *               option values.
     *   TextField   The name of the field in       'text'
     *               $DataSet that contains the
     *               option text.
     *   Value       A string or array of strings.  $this->_DataArray->$FieldName
     *   IncludeNull TRUE to include a blank row    FALSE
     *               String to create disabled
     *               first option.
     *   InlineErrors  Show inline error message?   TRUE
     *               Allows disabling per-dropdown
     *               for multi-fields like Date()
     *
     * @return string
     */
    public function dropDown($FieldName, $DataSet, $Attributes = false) {
        // Show inline errors?
        $ShowErrors = ($this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults));

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        // Opening select tag
        $Return = '<select';
        $Return .= $this->_idAttribute($FieldName, $Attributes);
        $Return .= $this->_nameAttribute($FieldName, $Attributes);
        $Return .= $this->_attributesToString($Attributes);
        $Return .= ">\n";

        // Get value from attributes and ensure it's an array
        $Value = arrayValueI('Value', $Attributes);
        if ($Value === false) {
            $Value = $this->getValue($FieldName, val('Default', $Attributes));
        }
        if (!is_array($Value)) {
            $Value = array($Value);
        }

        // Prevent default $Value from matching key of zero
        $HasValue = ($Value !== array(false) && $Value !== array('')) ? true : false;

        // Start with null option?
        $IncludeNull = arrayValueI('IncludeNull', $Attributes, false);
        if ($IncludeNull === true) {
            $Return .= "<option value=\"\"></option>\n";
        } elseif ($IncludeNull)
            $Return .= "<option value=\"\">$IncludeNull</option>\n";

        if (is_object($DataSet)) {
            $FieldsExist = false;
            $ValueField = arrayValueI('ValueField', $Attributes, 'value');
            $TextField = arrayValueI('TextField', $Attributes, 'text');
            $Data = $DataSet->firstRow();
            if (is_object($Data) && property_exists($Data, $ValueField) && property_exists(
                $Data,
                $TextField
            )
            ) {
                foreach ($DataSet->result() as $Data) {
                    $Return .= '<option value="'.$Data->$ValueField.
                        '"';
                    if (in_array($Data->$ValueField, $Value) && $HasValue) {
                        $Return .= ' selected="selected"';
                    }

                    $Return .= '>'.$Data->$TextField."</option>\n";
                }
            }
        } elseif (is_array($DataSet)) {
            foreach ($DataSet as $ID => $Text) {
                if (is_array($Text)) {
                    $Attribs = $Text;
                    $Text = val('Text', $Attribs, '');
                    unset($Attribs['Text']);
                } else {
                    $Attribs = array();
                }
                $Return .= '<option value="'.$ID.'"';
                if (in_array($ID, $Value) && $HasValue) {
                    $Return .= ' selected="selected"';
                }

                $Return .= attribute($Attribs).'>'.$Text."</option>\n";
            }
        }
        $Return .= '</select>';

        // Append validation error message
        if ($ShowErrors && arrayValueI('InlineErrors', $Attributes, true)) {
            $Return .= $this->inlineError($FieldName);
        }

        return $Return;
    }

    /**
     * Returns the xhtml for a dropdown list with option groups.
     * @param string $FieldName
     * @param array $Data
     * @param string $GroupField
     * @param string $TextField
     * @param string $ValueField
     * @param array $Attributes
     * @return string
     */
    public function dropDownGroup($FieldName, $Data, $GroupField, $TextField, $ValueField, $Attributes = array()) {
        $Return = '<select'
            .$this->_idAttribute($FieldName, $Attributes)
            .$this->_nameAttribute($FieldName, $Attributes)
            .$this->_attributesToString($Attributes)
            .">\n";

        // Get the current value.
        $CurrentValue = val('Value', $Attributes, false);
        if ($CurrentValue === false) {
            $CurrentValue = $this->getValue($FieldName, GetValue('Default', $Attributes));
        }

        // Add a null option?
        $IncludeNull = arrayValueI('IncludeNull', $Attributes, false);
        if ($IncludeNull === true) {
            $Return .= "<option value=\"\"></option>\n";
        } elseif ($IncludeNull)
            $Return .= "<option value=\"\">$IncludeNull</option>\n";

        $LastGroup = null;

        foreach ($Data as $Row) {
            $Group = $Row[$GroupField];

            // Check for a group header.
            if ($LastGroup !== $Group) {
                // Close off the last opt group.
                if ($LastGroup !== null) {
                    $Return .= '</optgroup>';
                }

                $Return .= '<optgroup label="'.htmlspecialchars($Group)."\">\n";
                $LastGroup = $Group;
            }

            $Value = $Row[$ValueField];

            if ($CurrentValue == $Value) {
                $Selected = ' selected="selected"';
            } else {
                $Selected = '';
            }

            $Return .= '<option value="'.htmlspecialchars($Value).'"'.$Selected.'>'.htmlspecialchars($Row[$TextField])."</option>\n";

        }

        if ($LastGroup) {
            $Return .= '</optgroup>';
        }

        $Return .= '</select>';

        return $Return;
    }

    /**
     * Returns XHTML for all form-related errors that have occurred.
     *
     * @return string
     */
    public function errors() {
        $Return = '';
        if (is_array($this->_ValidationResults) && count($this->_ValidationResults) > 0) {
            $Return = "<div class=\"Messages Errors\">\n<ul>\n";
            foreach ($this->_ValidationResults as $FieldName => $Problems) {
                $Count = count($Problems);
                for ($i = 0; $i < $Count; ++$i) {
                    if (substr($Problems[$i], 0, 1) == '@') {
                        $Return .= "<li>".substr($Problems[$i], 1)."</li>\n";
                    } else {
                        $Return .= '<li>'.sprintf(
                            t($Problems[$i]),
                            t($FieldName)
                        )."</li>\n";
                    }
                }
            }
            $Return .= "</ul>\n</div>\n";
        }
        return $Return;
    }

    public function errorString() {
        $Return = '';
        if (is_array($this->_ValidationResults) && count($this->_ValidationResults) > 0) {
            foreach ($this->_ValidationResults as $FieldName => $Problems) {
                $Count = count($Problems);
                for ($i = 0; $i < $Count; ++$i) {
                    if (substr($Problems[$i], 0, 1) == '@') {
                        $Return .= rtrim(substr($Problems[$i], 1), '.').'. ';
                    } else {
                        $Return .= rtrim(sprintf(
                            t($Problems[$i]),
                            t($FieldName)
                        ), '.').'. ';
                    }
                }
            }
        }
        return trim($Return);
    }

    /**
     * Encodes the string in a php-form safe-encoded format.
     *
     * @param string $String The string to encode.
     * @return string
     */
    public function escapeString($String) {
        $Array = false;
        if (substr($String, -2) == '[]') {
            $String = substr($String, 0, -2);
            $Array = true;
        }
        $Return = urlencode(str_replace(' ', '_', $String));
        if ($Array === true) {
            $Return .= '[]';
        }

        return str_replace('.', '-dot-', $Return);
    }

    /**
     * Returns a checkbox table.
     *
     * @param string $GroupName The name of the checkbox table (the text that appears in the top-left
     * cell of the table). This value will be passed through the T()
     * function before render.
     *
     * @param array $Group An array of $PermissionName => $CheckBoxXhtml to be rendered within the
     * grid. This represents the final (third) part of the permission name
     * string, as in the "Edit" part of "Garden.Roles.Edit".
     * ie. 'Edit' => '<input type="checkbox" id="PermissionID"
     * name="Role/PermissionID[]" value="20" />';
     *
     * @param array $Rows An array of rows to appear in the grid. This represents the middle part
     * of the permission name, as in the "Roles" part of "Garden.Roles.Edit".
     *
     * @param array $Cols An array of columns to appear in the grid for each row. This (again)
     * represents the final part of the permission name, as in the "Edit" part
     * of "Garden.Roles.Edit".
     * ie. Row1 = array('Add', 'Edit', 'Delete');
     */
    public function getCheckBoxGridGroup($GroupName, $Group, $Rows, $Cols) {
        $Return = '';
        $Headings = '';
        $Cells = '';
        $RowCount = count($Rows);
        $ColCount = count($Cols);
        for ($j = 0; $j < $RowCount; ++$j) {
            $Alt = 1;
            for ($i = 0; $i < $ColCount; ++$i) {
                $Alt = $Alt == 0 ? 1 : 0;
                $ColName = $Cols[$i];
                $RowName = $Rows[$j];

                if ($j == 0) {
                    $Headings .= '<td'.($Alt == 0 ? ' class="Alt"' : '').
                    '>'.T($ColName).'</td>';
                }

                if (array_key_exists($RowName, $Group[$ColName])) {
                    $Cells .= '<td'.($Alt == 0 ? ' class="Alt"' : '').
                        '>'.$Group[$ColName][$RowName].
                        '</td>';
                } else {
                    $Cells .= '<td'.($Alt == 0 ? ' class="Alt"' : '').
                        '>&#160;</td>';
                }
            }
            if ($Headings != '') {
                $Return .= "<thead><tr><th>".t($GroupName)."</th>".
                $Headings."</tr></thead>\r\n<tbody>";
            }

            $aRowName = explode('.', $RowName);
            $RowNameCount = count($aRowName);
            if ($RowNameCount > 1) {
                $RowName = '';
                for ($i = 0; $i < $RowNameCount; ++$i) {
                    if ($i < $RowNameCount - 1) {
                        $RowName .= '<span class="Parent">'.
                        T($aRowName[$i]).'</span>';
                    } else {
                        $RowName .= t($aRowName[$i]);
                    }
                }
            } else {
                $RowName = t($RowName);
            }
            $Return .= '<tr><th>'.$RowName.'</th>'.$Cells."</tr>\r\n";
            $Headings = '';
            $Cells = '';
        }
        return $Return == '' ? '' : '<table class="CheckBoxGrid">'.$Return.'</tbody></table>';
    }

    /**
     * Returns XHTML for all hidden fields.
     *
     * @return string
     */
    public function getHidden() {
        $Return = '';
        if (is_array($this->HiddenInputs)) {
            foreach ($this->HiddenInputs as $Name => $Value) {
                $Return .= $this->Hidden($Name, array('value' => $Value));
            }
            // Clean out the array
            // mosullivan - removed cleanout so that entry forms can all have the same hidden inputs added once on the entry/index view.
            // TODO - WATCH FOR BUGS BECAUSE OF THIS CHANGE.
            // $this->HiddenInputs = array();
        }
        return $Return;
    }

    /**
     * Returns the xhtml for a hidden input.
     *
     * @param string $FieldName The name of the field that is being hidden/posted with this input. It
     * should related directly to a field name in $this->_DataArray.
     * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
     * class, etc
     * @return string
     */
    public function hidden($FieldName, $Attributes = false) {
        $Return = '<input type="hidden"';
        $Return .= $this->_idAttribute($FieldName, $Attributes);
        $Return .= $this->_nameAttribute($FieldName, $Attributes);
        $Return .= $this->_valueAttribute($FieldName, $Attributes);
        $Return .= $this->_attributesToString($Attributes);
        $Return .= ' />';
        return $Return;
    }

    /**
     * Return a control for uploading images.
     *
     * @param string $FieldName
     * @param array $Attributes
     * @return string
     * @since 2.1
     */
    public function imageUpload($FieldName, $Attributes = array()) {
        $Result = '<div class="FileUpload ImageUpload">'.
            $this->currentImage($FieldName, $Attributes).
            '<div>'.
            $this->input($FieldName.'_New', 'file').
            '</div>'.
            '</div>';

        return $Result;
    }

    /**
     * Returns XHTML of inline error for specified field.
     *
     * @since 2.0.18
     * @access public
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @return string
     */
    public function inlineError($FieldName) {
        $AppendError = '<p class="'.$this->ErrorClass.'">';
        foreach ($this->_ValidationResults[$FieldName] as $ValidationError) {
            $AppendError .= sprintf(T($ValidationError), t($FieldName)).' ';
        }
        $AppendError .= '</p>';

        return $AppendError;
    }

    /**
     * Returns the xhtml for a standard input tag.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @param string $Type The type attribute for the input.
     * @param array $Attributes An associative array of attributes for the input. (e.g. maxlength, onclick, class)
     *    Setting 'InlineErrors' to FALSE prevents error message even if $this->InlineErrors is enabled.
     * @return string
     */
    public function input($FieldName, $Type = 'text', $Attributes = false) {
        if ($Type == 'text' || $Type == 'password') {
            $CssClass = arrayValueI('class', $Attributes);
            if ($CssClass == false) {
                $Attributes['class'] = 'InputBox';
            }
        }

        // Show inline errors?
        $ShowErrors = $this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults);

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        $Return = '';
        $Wrap = val('Wrap', $Attributes, false, true);
        $Strength = val('Strength', $Attributes, false, true);
        if ($Wrap) {
            $Return .= '<div class="TextBoxWrapper">';
        }

        if (strtolower($Type) == 'checkbox') {
            if (isset($Attributes['nohidden'])) {
                unset($Attributes['nohidden']);
            } else {
                $Return .= '<input type="hidden" name="Checkboxes[]" value="'.
                    (substr($FieldName, -2) === '[]' ? substr($FieldName, 0, -2) : $FieldName).
                    '" />';
            }
        }


        $Return .= '<input type="'.$Type.'"';
        $Return .= $this->_idAttribute($FieldName, $Attributes);
        if ($Type == 'file') {
            $Return .= attribute(
                'name',
                arrayValueI('Name', $Attributes, $FieldName)
            );
        } else {
            $Return .= $this->_nameAttribute($FieldName, $Attributes);
        }

        if ($Strength) {
            $Return .= ' data-strength="true"';
        }
        $Return .= $this->_valueAttribute($FieldName, $Attributes);
        $Return .= $this->_attributesToString($Attributes);
        $Return .= ' />';


        // Append validation error message
        if ($ShowErrors && arrayValueI('InlineErrors', $Attributes, true)) {
            $Return .= $this->inlineError($FieldName);
        }

        if ($Type == 'password' && $Strength) {
            $Return .= <<<PASSWORDMETER
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

        if ($Wrap) {
            $Return .= '</div>';
        }

        return $Return;
    }

    /**
     * Returns XHTML for a label element.
     *
     * @param string $TranslationCode Code to be translated and presented within the label tag.
     * @param string $FieldName Name of the field that the label is for.
     * @param array $Attributes Associative array of attributes for the input that the label is for.
     *    This is only available in case the related input has a custom id specified in the attributes array.
     *
     * @return string
     */
    public function label($TranslationCode, $FieldName = '', $Attributes = false) {
        // Assume we always want a 'for' attribute because it's Good & Proper.
        // Precedence: 'for' attribute, 'id' attribute, $FieldName, $TranslationCode
        $DefaultFor = ($FieldName == '') ? $TranslationCode : $FieldName;
        $For = arrayValueI('for', $Attributes, arrayValueI('id', $Attributes, $this->escapeID($DefaultFor, false)));

        return '<label for="'.$For.'"'.$this->_attributesToString($Attributes).'>'.t($TranslationCode)."</label>\n";
    }

    /**
     * Generate a friendly looking label translation code from a camel case variable name
     * @param string|array $Item The item to generate the label from.
     *  - string: Generate the label directly from the item.
     *  - array: Generate the label from the item as if it is a schema row passed to Gdn_Form::Simple().
     * @return string
     */
    public static function labelCode($Item) {
        if (is_array($Item)) {
            if (isset($Item['LabelCode'])) {
                return $Item['LabelCode'];
            }

            $LabelCode = $Item['Name'];
        } else {
            $LabelCode = $Item;
        }


        if (strpos($LabelCode, '.') !== false) {
            $LabelCode = trim(strrchr($LabelCode, '.'), '.');
        }

        // Split camel case labels into seperate words.
        $LabelCode = preg_replace('`(?<![A-Z0-9])([A-Z0-9])`', ' $1', $LabelCode);
        $LabelCode = preg_replace('`([A-Z0-9])(?=[a-z])`', ' $1', $LabelCode);
        $LabelCode = trim($LabelCode);

        return $LabelCode;
    }

    /**
     * Returns the xhtml for the opening of the form (the form tag and all
     * hidden elements).
     *
     * @param array $Attributes An associative array of attributes for the form tag. Here is a list of
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
    public function open($Attributes = array()) {
//      if ($this->InputPrefix)
//         Trace($this->InputPrefix, 'InputPrefix');

        if (!is_array($Attributes)) {
            $Attributes = array();
        }

        $Return = '<form';
        if ($this->InputPrefix != '' || array_key_exists('id', $Attributes)) {
            $Return .= $this->_idAttribute(
                $this->InputPrefix,
                $Attributes
            );
        }

        // Method
        $MethodFromAttributes = arrayValueI('method', $Attributes);
        $this->Method = $MethodFromAttributes === false ? $this->Method : $MethodFromAttributes;

        // Action
        $ActionFromAttributes = arrayValueI('action', $Attributes);
        if ($this->Action == '') {
            $this->Action = url();
        }

        $this->Action = $ActionFromAttributes === false ? $this->Action : $ActionFromAttributes;

        if (strcasecmp($this->Method, 'get') == 0) {
            // The path is not getting passed on get forms so put them in hidden fields.
            $Action = strrchr($this->Action, '?');
            $Exclude = val('Exclude', $Attributes, array());
            if ($Action !== false) {
                $this->Action = substr($this->Action, 0, -strlen($Action));
                parse_str(trim($Action, '?'), $Query);
                $Hiddens = '';
                foreach ($Query as $Key => $Value) {
                    if (in_array($Key, $Exclude)) {
                        continue;
                    }
                    $Key = Gdn_Format::form($Key);
                    $Value = Gdn_Format::form($Value);
                    $Hiddens .= "\n<input type=\"hidden\" name=\"$Key\" value=\"$Value\" />";
                }
            }
        }

        $Return .= ' method="'.$this->Method.'"'
            .' action="'.$this->Action.'"'
            .$this->_attributesToString($Attributes)
            .">\n<div>\n";

        if (isset($Hiddens)) {
            $Return .= $Hiddens;
        }

        // Postback Key - don't allow it to be posted in the url (prevents csrf attacks & hijacks)
        if ($this->Method != "get") {
            $Session = Gdn::session();
            $Return .= $this->hidden(
                'TransientKey',
                array('value' => $Session->transientKey())
            );
            // Also add a honeypot if Forms.HoneypotName has been defined
            $HoneypotName = Gdn::config(
                'Garden.Forms.HoneypotName'
            );
            if ($HoneypotName) {
                $Return .= $this->hidden(
                    $HoneypotName,
                    array('Name' => $HoneypotName, 'style' => "display: none;")
                );
            }
        }

        // Render all other hidden inputs that have been defined
        $Return .= $this->getHidden();
        return $Return;
    }

    /**
     * Returns XHTML for a radio input element.
     *
     * Provides way of wrapping Input() with a label.
     *
     * @param string $FieldName Name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray.
     * @param string $Label Label to place next to the radio.
     * @param array $Attributes Associative array of attributes for the input (e.g. onclick, class).
     *    Special values 'Value' and 'Default' (see RadioList).
     * @return string
     */
    public function radio($FieldName, $Label = '', $Attributes = false) {
        $Value = arrayValueI('Value', $Attributes, 'TRUE');
        $Attributes['value'] = $Value;
        $FormValue = $this->getValue($FieldName, arrayValueI('Default', $Attributes));
        $Display = val('display', $Attributes, 'wrap');
        unset($Attributes['display']);

        // Check for 'checked'
        if ($FormValue == $Value) {
            $Attributes['checked'] = 'checked';
        }

        // Never display individual inline errors for this Input
        $Attributes['InlineErrors'] = false;

        // Get standard radio Input
        $Input = $this->Input($FieldName, 'radio', $Attributes);

        // Wrap with label.
        if ($Label != '') {
            $LabelElement = '<label for="'.arrayValueI('id', $Attributes, $this->EscapeID($FieldName, false)).'" class="'.val('class', $Attributes, 'RadioLabel').'">';
            if ($Display === 'wrap') {
                $Input = $LabelElement.$Input.' '.t($Label).'</label>';
            } elseif ($Display === 'before') {
                $Input = $LabelElement.t($Label).'</label> '.$Input;
            } else {
                $Input = $Input.' '.$LabelElement.t($Label).'</label>';
            }
        }

        return $Input;
    }

    /**
     * Returns XHTML for an unordered list of radio button elements.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input.
     *    It should related directly to a field name in $this->_DataArray. ie. RoleID
     * @param mixed $DataSet The data to fill the options in the select list. Either an associative
     *    array or a database dataset.
     * @param array $Attributes An associative array of attributes for the list. Here is a list of
     *    "special" attributes and their default values:
     *
     *   Attribute   Options                        Default
     *   ------------------------------------------------------------------------
     *   ValueField  The name of the field in       'value'
     *               $DataSet that contains the
     *               option values.
     *   TextField   The name of the field in       'text'
     *               $DataSet that contains the
     *               option text.
     *   Value       A string or array of strings.  $this->_DataArray->$FieldName
     *   Default     The default value.             empty
     *   InlineErrors  Show inline error message?   TRUE
     *               Allows disabling per-dropdown
     *               for multi-fields like Date()
     *
     * @return string
     */
    public function radioList($FieldName, $DataSet, $Attributes = false) {
        $List = val('list', $Attributes);
        $Return = '';

        if ($List) {
            $Return .= '<ul'.(isset($Attributes['listclass']) ? " class=\"{$Attributes['listclass']}\"" : '').'>';
            $LiOpen = '<li>';
            $LiClose = '</li>';
        } else {
            $LiOpen = '';
            $LiClose = ' ';
        }

        // Show inline errors?
        $ShowErrors = ($this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults));

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        if (is_object($DataSet)) {
            $ValueField = arrayValueI('ValueField', $Attributes, 'value');
            $TextField = arrayValueI('TextField', $Attributes, 'text');
            $Data = $DataSet->firstRow();
            if (property_exists($Data, $ValueField) && property_exists(
                $Data,
                $TextField
            )
            ) {
                foreach ($DataSet->result() as $Data) {
                    $Attributes['value'] = $Data->$ValueField;

                    $Return .= $LiOpen.$this->radio($FieldName, $Data->$TextField, $Attributes).$LiClose;
                }
            }
        } elseif (is_array($DataSet)) {
            foreach ($DataSet as $ID => $Text) {
                $Attributes['value'] = $ID;
                $Return .= $LiOpen.$this->radio($FieldName, $Text, $Attributes).$LiClose;
            }
        }

        if ($List) {
            $Return .= '</ul>';
        }

        // Append validation error message
        if ($ShowErrors && arrayValueI('InlineErrors', $Attributes, true)) {
            $Return .= $this->inlineError($FieldName);
        }

        return $Return;
    }

    /**
     * Returns the xhtml for a text-based input.
     *
     * @param string $FieldName The name of the field that is being displayed/posted with this input. It
     *  should related directly to a field name in $this->_DataArray.
     * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *  class, etc
     * @return string
     */
    public function textBox($FieldName, $Attributes = false) {
        if (!is_array($Attributes)) {
            $Attributes = array();
        }

        $MultiLine = arrayValueI('MultiLine', $Attributes);

        if ($MultiLine) {
            $Attributes['rows'] = arrayValueI('rows', $Attributes, '6'); // For xhtml compliance
            $Attributes['cols'] = arrayValueI('cols', $Attributes, '100'); // For xhtml compliance
        }

        // Show inline errors?
        $ShowErrors = $this->_InlineErrors && array_key_exists($FieldName, $this->_ValidationResults);

        $CssClass = arrayValueI('class', $Attributes);
        if ($CssClass == false) {
            $Attributes['class'] = $MultiLine ? 'TextBox' : 'InputBox';
        }

        // Add error class to input element
        if ($ShowErrors) {
            $this->addErrorClass($Attributes);
        }

        $Return = '';
        $Wrap = val('Wrap', $Attributes, false, true);
        if ($Wrap) {
            $Return .= '<div class="TextBoxWrapper">';
        }

        $Return .= $MultiLine === true ? '<textarea' : '<input type="'.val('type', $Attributes, 'text').'"';
        $Return .= $this->_idAttribute($FieldName, $Attributes);
        $Return .= $this->_nameAttribute($FieldName, $Attributes);
        $Return .= $MultiLine === true ? '' : $this->_valueAttribute($FieldName, $Attributes);
        $Return .= $this->_attributesToString($Attributes);

        $Value = arrayValueI('value', $Attributes, $this->getValue($FieldName));

        $Return .= $MultiLine === true ? '>'.htmlentities($Value, ENT_COMPAT, 'UTF-8').'</textarea>' : ' />';

        // Append validation error message
        if ($ShowErrors) {
            $Return .= $this->inlineError($FieldName);
        }

        if ($Wrap) {
            $Return .= '</div>';
        }

        return $Return;
    }


    /// =========================================================================
    /// Methods for interfacing with the model & db.
    /// =========================================================================

    /**
     * Adds an error to the errors collection and optionally relates it to the
     * specified FieldName. Errors added with this method can be rendered with
     * $this->Errors().
     *
     * @param mixed $ErrorCode
     *  - <b>string</b>: The translation code that represents the error to display.
     *  - <b>Exception</b>: The exception to display the message for.
     * @param string $FieldName The name of the field to relate the error to.
     */
    public function addError($Error, $FieldName = '') {
        if (is_string($Error)) {
            $ErrorCode = $Error;
        } elseif (is_a($Error, 'Gdn_UserException')) {
            $ErrorCode = '@'.htmlspecialchars($Error->getMessage());
        } elseif (is_a($Error, 'Exception')) {
            // Strip the extra information out of the exception.
            $Parts = explode('|', $Error->getMessage());
            $Message = htmlspecialchars($Parts[0]);
            if (count($Parts) >= 3) {
                $FileSuffix = ": {$Parts[1]}->{$Parts[2]}(...)";
            } else {
                $FileSuffix = "";
            }

            if (debug()) {
                $ErrorCode = '@<pre>'.
                    $Message."\n".
                    '## '.$Error->getFile().'('.$Error->getLine().")".$FileSuffix."\n".
                    htmlspecialchars($Error->getTraceAsString()).
                    '</pre>';
            } else {
                $ErrorCode = '@'.htmlspecialchars(strip_tags($Error->getMessage()));
            }
        }

        if ($FieldName == '') {
            $FieldName = '<General Error>';
        }

        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = array();
        }

        if (!array_key_exists($FieldName, $this->_ValidationResults)) {
            $this->_ValidationResults[$FieldName] = array($ErrorCode);
        } else {
            if (!is_array($this->_ValidationResults[$FieldName])) {
                $this->_ValidationResults[$FieldName] = array(
                $this->_ValidationResults[$FieldName],
                $ErrorCode);
            } else {
                $this->_ValidationResults[$FieldName][] = $ErrorCode;
            }
        }
    }

    /**
     * Adds a hidden input value to the form.
     *
     * If the $ForceValue parameter remains FALSE, it will grab the value into the hidden input from the form
     * on postback. Otherwise it will always force the assigned value to the
     * input regardless of postback.
     *
     * @param string $FieldName The name of the field being added as a hidden input on the form.
     * @param string $Value The value being assigned in the hidden input. Unless $ForceValue is
     *  changed to TRUE, this field will be retrieved from the form upon
     *  postback.
     * @param bool $ForceValue
     */
    public function addHidden($FieldName, $Value = null, $ForceValue = false) {
        if ($this->isPostBack() && $ForceValue === false) {
            $Value = $this->getFormValue($FieldName, $Value);
        }

        $this->HiddenInputs[$FieldName] = $Value;
    }

    /**
     * Returns a boolean value indicating if the current page has an
     * authenticated postback. It validates the postback by looking at a
     * transient value that was rendered using $this->Open() and submitted with
     * the form. Ref: http://en.wikipedia.org/wiki/Cross-site_request_forgery
     *
     * @return bool
     */
    public function authenticatedPostBack() {
        // Commenting this out because, technically, a get request is not a "postback".
        // And since I typically use AuthenticatedPostBack to validate that a form has
        // been posted back a get request should not be considered an authenticated postback.
        //if ($this->Method == "get") {
        // forms sent with "get" method do not require authentication.
        //   return TRUE;
        //} else {
        $KeyName = $this->escapeFieldName('TransientKey');
        $PostBackKey = Gdn::request()->getValueFrom(Gdn_Request::INPUT_POST, $KeyName, false);

        // If this isn't a postback then return false if there isn't a transient key.
        if (!$PostBackKey && !Gdn::request()->isPostBack()) {
            return false;
        }

        // DEBUG:
        //$Result .= '<div>KeyName: '.$KeyName.'</div>';
        //echo '<div>PostBackKey: '.$PostBackKey.'</div>';
        //echo '<div>TransientKey: '.$Session->TransientKey().'</div>';
        //echo '<div>AuthenticatedPostBack: ' . ($Session->ValidateTransientKey($PostBackKey) ? 'Yes' : 'No');
        //die();
        return Gdn::session()->validateTransientKey($PostBackKey);
        //}
    }

    /**
     * Checks $this->FormValues() to see if the specified button translation
     * code was submitted with the form (helps figuring out what button was
     *  pressed to submit the form when there is more than one button available).
     *
     * @param string $ButtonCode The translation code of the button to check for.
     * @return boolean
     */
    public function buttonExists($ButtonCode) {
        $NameKey = $this->escapeString($ButtonCode);
        return array_key_exists($NameKey, $this->formValues()) ? true : false;
    }

    /**
     * Emptys the $this->_FormValues collection so that all form fields will load empty.
     */
    public function clearInputs() {
        $this->_FormValues = array();
    }

    /**
     * Returns a count of the number of errors that have occurred.
     *
     * @return int
     */
    public function errorCount() {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = array();
        }

        return count($this->_ValidationResults);
    }

    /**
     * Returns the provided fieldname with non-alpha-numeric values stripped.
     *
     * @param string $FieldName The field name to escape.
     * @return string
     */
    public function escapeFieldName($FieldName) {
        $Return = $this->InputPrefix;
        if ($Return != '') {
            $Return .= '/';
        }
        return $Return.$this->escapeString($FieldName);
    }

    /**
     * Returns the provided fieldname with non-alpha-numeric values stripped and
     * $this->IDPrefix prepended.
     *
     * @param string $FieldName
     * @param bool $ForceUniqueID
     * @return string
     */
    public function escapeID(
        $FieldName,
        $ForceUniqueID = true
    ) {
        $ID = $FieldName;
        if (substr($ID, -2) == '[]') {
            $ID = substr($ID, 0, -2);
        }

        $ID = $this->IDPrefix.Gdn_Format::alphaNumeric(str_replace('.', '-dot-', $ID));
        $tmp = $ID;
        $i = 1;
        if ($ForceUniqueID === true) {
            if (array_key_exists($ID, $this->_IDCollection)) {
                $tmp = $ID.$this->_IDCollection[$ID];
                $this->_IDCollection[$ID]++;
            } else {
                $tmp = $ID;
                $this->_IDCollection[$ID] = 1;

            }
        } else {
            // If not forcing unique (ie. getting the id for a label's "for" tag),
            // get the last used copy of the requested id.
            $Found = false;
            $Count = val($ID, $this->_IDCollection, 0);
            if ($Count <= 1) {
                $tmp = $ID;
            } else {
                $tmp = $ID.($Count - 1);
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

        $Result = array(array());
        foreach ($this->_FormValues as $Key => $Value) {
            if (is_array($Value)) {
                foreach ($Value as $RowIndex => $RowValue) {
                    if (!array_key_exists($RowIndex, $Result)) {
                        $Result[$RowIndex] = array($Key => $RowValue);
                    } else {
                        $Result[$RowIndex][$Key] = $RowValue;
                    }
                }
            } else {
                $Result[0][$Key] = $Value;
            }
        }

        return $Result;
    }

    /**
     * If the form has been posted back, this method return an associative
     * array of $FieldName => $Value pairs which were sent in the form.
     *
     * Note: these values are typically used by the model and it's validation object.
     *
     * @return array
     */
    public function formValues($NewValue = null) {
        if ($NewValue !== null) {
            $this->_FormValues = $NewValue;
            return;
        }

        $MagicQuotes = get_magic_quotes_gpc();

        if (!is_array($this->_FormValues)) {
            $TableName = $this->InputPrefix;
            if (strlen($TableName) > 0) {
                $TableName .= '/';
            }
            $TableNameLength = strlen($TableName);
            $this->_FormValues = array();
            $Collection = $this->Method == 'get' ? $_GET : $_POST; // TODO wtf globals
            $InputType = $this->Method == 'get' ? INPUT_GET : INPUT_POST;


            foreach ($Collection as $Field => $Value) {
                $FieldName = substr($Field, $TableNameLength);
                $FieldName = $this->_unescapeString($FieldName);
                if (substr($Field, 0, $TableNameLength) == $TableName) {
                    if ($MagicQuotes) {
                        if (is_array($Value)) {
                            foreach ($Value as $i => $v) {
                                $Value[$i] = stripcslashes($v);
                            }
                        } else {
                            $Value = stripcslashes($Value);
                        }
                    }

                    $this->_FormValues[$FieldName] = $Value;
                }
            }

            // Make sure that unchecked checkboxes get added to the collection
            if (array_key_exists('Checkboxes', $Collection)) {
                $UncheckedCheckboxes = $Collection['Checkboxes'];
                if (is_array($UncheckedCheckboxes) === true) {
                    $Count = count($UncheckedCheckboxes);
                    for ($i = 0; $i < $Count; ++$i) {
                        if (!array_key_exists($UncheckedCheckboxes[$i], $this->_FormValues)) {
                            $this->_FormValues[$UncheckedCheckboxes[$i]] = false;
                        }
                    }
                }
            }

            // Make sure that Date inputs (where the day, month, and year are
            // separated into their own dropdowns on-screen) get added to the
            // collection as a single field as well...
            if (array_key_exists(
                'DateFields',
                $Collection
            ) === true
            ) {
                $DateFields = $Collection['DateFields'];
                if (is_array($DateFields) === true) {
                    $Count = count($DateFields);
                    for ($i = 0; $i < $Count; ++$i) {
                        if (array_key_exists(
                            $DateFields[$i],
                            $this->_FormValues
                        ) ===
                            false
                        ) { // Saving dates in the format: YYYY-MM-DD
                            $Year = arrayValue(
                                $DateFields[$i].
                                '_Year',
                                $this->_FormValues,
                                0
                            );
                        }
                        $Month = arrayValue(
                            $DateFields[$i].
                            '_Month',
                            $this->_FormValues,
                            0
                        );
                        $Day = arrayValue(
                            $DateFields[$i].
                            '_Day',
                            $this->_FormValues,
                            0
                        );
                        $Month = str_pad(
                            $Month,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );
                        $Day = str_pad(
                            $Day,
                            2,
                            '0',
                            STR_PAD_LEFT
                        );
                        $this->_FormValues[$DateFields[$i]] = $Year.
                            '-'.
                            $Month.
                            '-'.
                            $Day;
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
     * Gets the value associated with $FieldName from the sent form fields.
     * If $FieldName isn't found in the form, it returns $Default.
     *
     * @param string $FieldName The name of the field to get the value of.
     * @param mixed $Default The default value to return if $FieldName isn't found.
     * @return unknown
     */
    public function getFormValue($FieldName, $Default = '') {
        return arrayValue($FieldName, $this->formValues(), $Default);
    }

    /**
     * Gets the value associated with $FieldName.
     *
     * If the form has been posted back, it will retrieve the value from the form.
     * If it hasn't been posted back, it gets the value from $this->_DataArray.
     * Failing either of those, it returns $Default.
     *
     * @param string $FieldName
     * @param mixed $Default
     * @return mixed
     *
     * @todo check returned value type
     */
    public function getValue($FieldName, $Default = false) {
        $Return = '';
        // Only retrieve values from the form collection if this is a postback.
        if ($this->isMyPostBack()) {
            $Return = $this->getFormValue($FieldName, $Default);
        } else {
            $Return = arrayValue($FieldName, $this->_DataArray, $Default);
        }
        return $Return;
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
     * Just like IsPostBack(), except auto populates FormValues and doesnt just check
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
        $SaveResult = false;
        if ($this->errorCount() == 0) {
            if (!isset($this->_Model)) {
                trigger_error(
                    ErrorMessage(
                        "You cannot call the form's save method if a model has not been defined.",
                        "Form",
                        "Save"
                    ),
                    E_USER_ERROR
                );
            }

            $Data = $this->formValues();
            if (method_exists($this->_Model, 'FilterForm')) {
                $Data = $this->_Model->filterForm($this->formValues());
            }

            $Args = array_merge(
                func_get_args(),
                array(
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null)
            );
            $SaveResult = $this->_Model->save(
                $Data,
                $Args[0],
                $Args[1],
                $Args[2],
                $Args[3],
                $Args[4],
                $Args[5],
                $Args[6],
                $Args[7],
                $Args[8],
                $Args[9]
            );
            if ($SaveResult === false) {
                // NOTE: THE VALIDATION FUNCTION NAMES ARE ALSO THE LANGUAGE
                // TRANSLATIONS OF THE ERROR MESSAGES. CHECK THEM OUT IN THE LOCALE
                // FILE.
                $this->setValidationResults($this->_Model->validationResults());
            }
        }
        return $SaveResult;
    }

    /**
     * Save an image from a field and delete any old image that's been uploaded.
     *
     * @param string $Field The name of the field. The image will be uploaded with the _New extension while the current image will be just the field name.
     * @param array $Options
     */
    public function saveImage($Field, $Options = array()) {
        $Upload = new Gdn_UploadImage();

        $FileField = str_replace('.', '_', $Field);

        if (!getValueR("{$FileField}_New.name", $_FILES)) {
            trace("$Field not uploaded, returning.");
            return false;
        }

        // First make sure the file is valid.
        try {
            $TmpName = $Upload->validateUpload($FileField.'_New', true);

            if (!$TmpName) {
                return false; // no file uploaded.
            }
        } catch (Exception $Ex) {
            $this->addError($Ex);
            return false;
        }

        // Get the file extension of the file.
            $Ext = val('OutputType', $Options, trim($Upload->getUploadedFileExtension(), '.'));
        if ($Ext == 'jpeg') {
            $Ext = 'jpg';
        }
            Trace($Ext, 'Ext');

        // The file is valid so let's come up with its new name.
        if (isset($Options['Name'])) {
            $Name = $Options['Name'];
        } elseif (isset($Options['Prefix']))
            $Name = $Options['Prefix'].md5(microtime()).'.'.$Ext;
        else {
            $Name = md5(microtime()).'.'.$Ext;
        }

        // We need to parse out the size.
            $Size = val('Size', $Options);
        if ($Size) {
            if (is_numeric($Size)) {
                touchValue('Width', $Options, $Size);
                touchValue('Height', $Options, $Size);
            } elseif (preg_match('`(\d+)x(\d+)`i', $Size, $M)) {
                touchValue('Width', $Options, $M[1]);
                touchValue('Height', $Options, $M[2]);
            }
        }

            trace($Options, "Saving image $Name.");
        try {
            $Parsed = $Upload->saveImageAs($TmpName, $Name, val('Height', $Options, ''), val('Width', $Options, ''), $Options);
            trace($Parsed, 'Saved Image');

            $Current = $this->getFormValue($Field);
            if ($Current && val('DeleteOriginal', $Options, true)) {
                // Delete the current image.
                trace("Deleting original image: $Current.");
                if ($Current) {
                    $Upload->delete($Current);
                }
            }

            // Set the current value.
            $this->setFormValue($Field, $Parsed['SaveName']);
        } catch (Exception $Ex) {
            $this->addError($Ex);
        }
    }

    /**
     * Assign a set of data to be displayed in the form elements.
     *
     * @param array $Data A result resource or associative array containing data to be filled in
     */
    public function setData($Data) {
        if (is_object($Data) === true) {
            // If this is a result object (/garden/library/database/class.dataset.php)
            // retrieve it's values as arrays
            if ($Data instanceof DataSet) {
                $ResultSet = $Data->resultArray();
                if (count($ResultSet) > 0) {
                    $this->_DataArray = $ResultSet[0];
                }

            } else {
                // Otherwise assume it is an object representation of a data row.
                $this->_DataArray = Gdn_Format::objectAsArray($Data);
            }
        } elseif (is_array($Data)) {
            $this->_DataArray = $Data;
        }
    }

    /**
     * Sets the value associated with $FieldName from the sent form fields.
     * Essentially overwrites whatever was retrieved from the form.
     *
     * @param string $FieldName The name of the field to set the value of.
     * @param mixed $Value The new value of $FieldName.
     */
    public function setFormValue($FieldName, $Value = null) {
        $this->formValues();
        if (is_array($FieldName)) {
            $this->_FormValues = array_merge($this->_FormValues, $FieldName);
        } else {
            $this->_FormValues[$FieldName] = $Value;
        }
    }

    /**
     * Remove an element from a form.
     *
     * @param string $FieldName
     */
    public function removeFormValue($FieldName) {
        $this->formValues();

        if (!is_array($FieldName)) {
            $FieldName = array($FieldName);
        }

        foreach ($FieldName as $Field) {
            unset($this->_FormValues[$Field]);
        }
    }

    /**
     * Set the name of the model that will enforce data rules on $this->_DataArray.
     *
     * This value is also used to identify fields in the $_POST or $_GET
     * (depending on the forms method) collection when the form is submitted.
     *
     * @param Gdn_Model $Model The Model that will enforce data rules on $this->_DataArray. This value
     *  is passed by reference so any changes made to the model outside this
     *  object apply when it is referenced here.
     * @param Ressource $DataSet A result resource containing data to be filled in the form.
     */
    public function setModel($Model, $DataSet = false) {
        $this->_Model = $Model;

        if ($this->InputPrefix) {
            $this->InputPrefix = $this->_Model->Name;
        }
        if ($DataSet !== false) {
            $this->SetData($DataSet);
        }
    }

    /**
     *
     *
     * @param $ValidationResults
     */
    public function setValidationResults($ValidationResults) {
        if (!is_array($this->_ValidationResults)) {
            $this->_ValidationResults = array();
        }

        $this->_ValidationResults = array_merge_recursive($this->_ValidationResults, $ValidationResults);
    }

    /**
     * Sets the value associated with $FieldName.
     *
     * It sets the value in $this->_DataArray rather than in $this->_FormValues.
     *
     * @param string $FieldName
     * @param mixed $Default
     */
    public function setValue($FieldName, $Value) {
        if (!is_array($this->_DataArray)) {
            $this->_DataArray = array();
        }

        $this->_DataArray[$FieldName] = $Value;
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
     * @param array $Schema An array where each item of the array is a row that identifies a form field with the following information:
     *  - Name: The name of the form field.
     *  - Control: The type of control used for the field. This is one of the control methods on the Gdn_Form object.
     *  - LabelCode: The translation code for the label. Optional.
     *  - Description: An optional description for the field.
     *  - Items: If the control is a list control then its items are specified here.
     *  - Options: Additional options to be passed into the control.
     * @param type $Options Additional options to pass into the form.
     *  - Wrap: A two item array specifying the text to wrap the form in.
     *  - ItemWrap: A two item array specifying the text to wrap each form item in.
     */
    public function simple($Schema, $Options = array()) {
        $Result = valr('Wrap.0', $Options, '<ul>');

        $ItemWrap = val('ItemWrap', $Options, array("<li>\n  ", "\n</li>\n"));

        foreach ($Schema as $Index => $Row) {
            if (is_string($Row)) {
                $Row = array('Name' => $Index, 'Control' => $Row);
            }

            if (!isset($Row['Name'])) {
                $Row['Name'] = $Index;
            }
            if (!isset($Row['Options'])) {
                $Row['Options'] = array();
            }

            $Result .= $ItemWrap[0];

            $LabelCode = self::labelCode($Row);

            $Description = val('Description', $Row, '');
            if ($Description) {
                $Description = '<div class="Info">'.$Description.'</div>';
            }

            touchValue('Control', $Row, 'TextBox');

            switch (strtolower($Row['Control'])) {
                case 'categorydropdown':
                    $Result .= $this->label($LabelCode, $Row['Name'])
                        .$Description
                        .$this->categoryDropDown($Row['Name'], $Row['Options']);
                    break;
                case 'checkbox':
                    $Result .= $Description
                        .$this->checkBox($Row['Name'], $LabelCode);
                    break;
                case 'dropdown':
                    $Result .= $this->label($LabelCode, $Row['Name'])
                        .$Description
                        .$this->dropDown($Row['Name'], $Row['Items'], $Row['Options']);
                    break;
                case 'radiolist':
                    $Result .= $Description
                        .$this->radioList($Row['Name'], $Row['Items'], $Row['Options']);
                    break;
                case 'checkboxlist':
                    $Result .= $this->label($LabelCode, $Row['Name'])
                        .$Description
                        .$this->checkBoxList($Row['Name'], $Row['Items'], null, $Row['Options']);
                    break;
                case 'textbox':
                    $Result .= $this->label($LabelCode, $Row['Name'])
                        .$Description
                        .$this->textBox($Row['Name'], $Row['Options']);
                    break;
                case 'callback':
                    $Row['DescriptionHtml'] = $Description;
                    $Row['LabelCode'] = $LabelCode;
                    $Result .= call_user_func($Row['Callback'], $this, $Row);
                    break;
                default:
                    $Result .= "Error a control type of {$Row['Control']} is not supported.";
                    break;
            }
            $Result .= $ItemWrap[1];
        }
        $Result .= valr('Wrap.1', $Options, '</ul>');
        return $Result;
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
     * @param string $FieldName The name of the field to validate.
     * @param string|array $Rule The rule to validate against.
     * @param string $CustomError A custom error string.
     * @return bool Whether or not the rule succeeded.
     *
     * @see Gdn_Validation::ValidateRule()
     */
    public function validateRule($FieldName, $Rule, $CustomError = '') {
        $Value = $this->getFormValue($FieldName);
        $Valid = Gdn_Validation::validateRule($Value, $FieldName, $Rule, $CustomError);

        if ($Valid === true) {
            return true;
        } else {
            $this->addError('@'.$Valid, $FieldName);
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
     * Takes an associative array of $Attributes and returns them as a string of
     * param="value" sets to be placed in an input, select, textarea, etc tag.
     *
     * @param array $Attributes An associative array of attribute key => value pairs to be converted to a
     *    string. A number of "reserved" keys will be ignored: 'id', 'name',
     *    'maxlength', 'value', 'method', 'action', 'type'.
     * @return string
     */
    protected function _attributesToString($Attributes) {
        $ReservedAttributes = array(
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
            'categorydata'
        );
        $Return = '';

        // Build string from array
        if (is_array($Attributes)) {
            foreach ($Attributes as $Attribute => $Value) {
                // Ignore reserved attributes
                if (!in_array(strtolower($Attribute), $ReservedAttributes)) {
                    $Return .= ' '.$Attribute.'="'.htmlspecialchars($Value, ENT_COMPAT, 'UTF-8').'"';
                }
            }
        }
        return $Return;
    }

    /**
     * Creates an ID attribute for a form input and returns it in this format: [ id="IDNAME"]
     *
     * @param string $FieldName The name of the field that is being converted to an ID attribute.
     * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $Attributes contains an 'id' key, it will override the
     *    one automatically generated by $FieldName.
     * @return string
     */
    protected function _idAttribute($FieldName, $Attributes) {
        // ID from attributes overrides the default.
        $ID = arrayValueI('id', $Attributes, false);
        if (!$ID) {
            $ID = $this->escapeID($FieldName);
        }

        return ' id="'.htmlspecialchars($ID).'"';
    }

    /**
     * Creates a NAME attribute for a form input and returns it in this format: [ name="NAME"]
     *
     * @param string $FieldName The name of the field that is being converted to a NAME attribute.
     * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $Attributes contains a 'name' key, it will override the
     *    one automatically generated by $FieldName.
     * @return string
     */
    protected function _nameAttribute($FieldName, $Attributes) {
        // Name from attributes overrides the default.
        $Name = $this->escapeFieldName(arrayValueI('name', $Attributes, $FieldName));
        return (empty($Name)) ? '' : ' name="'.$Name.'"';
    }

    /**
     * Decodes the encoded string from a php-form safe-encoded format to the
     * format it was in when presented to the form.
     *
     * @param string $EscapedString
     * @return unknown
     */
    protected function _unescapeString(
        $EscapedString
    ) {
        $Return = str_replace('-dot-', '.', $EscapedString);
        return urldecode($Return);
    }

    /**
     * Creates a VALUE attribute for a form input and returns it in this format: [ value="VALUE"]
     *
     * @param string $FieldName The name of the field that contains the value in $this->_DataArray.
     * @param array $Attributes An associative array of attributes for the input. ie. maxlength, onclick,
     *    class, etc. If $Attributes contains a 'value' key, it will override the
     *    one automatically generated by $FieldName.
     * @return string
     */
    protected function _valueAttribute($FieldName, $Attributes) {
        // Value from $Attributes overrides the datasource and the postback.
        return ' value="'.Gdn_Format::form(arrayValueI('value', $Attributes, $this->getValue($FieldName))).'"';
    }
}
