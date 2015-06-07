<?php
/**
 * New Discussion module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the "Start a New Discussion" button.
 */
class NewDiscussionModule extends Gdn_Module {

    /** @var int Which category we are viewing (if any). */
    public $CategoryID = null;

    /** @var string Which button will be used as the default. */
    public $DefaultButton;

    /** @var string CSS classes to apply to ButtonGroup. */
    public $CssClass = 'Button Action Big Primary';

    /** @var string Query string to append to button URL. */
    public $QueryString = '';

    /** @var array Collection of buttons to display. */
    public $Buttons = array();

    /** @var bool Whether to show button to all users & guests regardless of permissions. */
    public $ShowGuests = false;

    /** @var string Where to send users without permission when $SkipPermissions is enabled. */
    public $GuestUrl = '/entry/register';

    /**
     * Set default button.
     *
     * @param string $Sender
     * @param bool $ApplicationFolder Unused.
     */
    public function __construct($Sender = '', $ApplicationFolder = false) {
        parent::__construct($Sender, 'Vanilla');
        // Customize main button by setting Vanilla.DefaultNewButton to URL code. Example: "post/question"
        $this->DefaultButton = c('Vanilla.DefaultNewButton', false);
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Add a button to the collection.
     *
     * @param $Text
     * @param $Url
     */
    public function addButton($Text, $Url) {
        $this->Buttons[] = array('Text' => $Text, 'Url' => $Url);
    }

    /**
     * Render the module.
     *
     * @return string
     */
    public function toString() {
        // Set CategoryID if we have one.
        if ($this->CategoryID === null) {
            $this->CategoryID = Gdn::controller()->data('Category.CategoryID', false);
        }

        // Allow plugins and themes to modify parameters.
        Gdn::controller()->EventArguments['NewDiscussionModule'] = &$this;
        Gdn::controller()->fireEvent('BeforeNewDiscussionButton');

        // Make sure the user has the most basic of permissions first.
        $PermissionCategory = CategoryModel::PermissionCategory($this->CategoryID);
        if ($this->CategoryID) {
            $Category = CategoryModel::categories($this->CategoryID);
            $HasPermission = Gdn::session()->checkPermission('Vanilla.Discussions.Add', true, 'Category', val('CategoryID', $PermissionCategory));
        } else {
            $HasPermission = Gdn::session()->checkPermission('Vanilla.Discussions.Add', true, 'Category', 'any');
        }

        // Determine if this is a guest & we're using "New Discussion" button as call to action.
        $PrivilegedGuest = ($this->ShowGuests && !Gdn::session()->isValid());

        // No module for you!
        if (!$HasPermission && !$PrivilegedGuest) {
            return '';
        }

        // Grab the allowed discussion types.
        $DiscussionTypes = CategoryModel::AllowedDiscussionTypes($PermissionCategory);

        foreach ($DiscussionTypes as $Key => $Type) {
            if (isset($Type['AddPermission']) && !Gdn::session()->checkPermission($Type['AddPermission'])) {
                unset($DiscussionTypes[$Key]);
                continue;
            }

            // If user !$HasPermission, they are $PrivilegedGuest so redirect to $GuestUrl.
            $Url = ($HasPermission) ? val('AddUrl', $Type) : $this->GuestUrl;
            if (!$Url) {
                continue;
            }

            if (isset($Category) && $HasPermission) {
                $Url .= '/'.rawurlencode(val('UrlCode', $Category));
            }

            $this->AddButton(t(val('AddText', $Type)), $Url);
        }

        // Add QueryString to URL if one is defined.
        if ($this->QueryString && $HasPermission) {
            foreach ($this->Buttons as &$Row) {
                $Row['Url'] .= (strpos($Row['Url'], '?') !== false ? '&' : '?').$this->QueryString;
            }
        }

        return parent::ToString();
    }
}
