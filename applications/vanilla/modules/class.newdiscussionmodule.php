<?php
/**
 * New Discussion module
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public $Buttons = [];

    /** @var bool Whether to show button to all users & guests regardless of permissions. */
    public $ShowGuests = false;

    /** @var string Where to send users without permission when $SkipPermissions is enabled. */
    public $GuestUrl = '/entry/signin';

    /**
     * Set default button.
     *
     * @param string $sender
     * @param bool $applicationFolder Unused.
     */
    public function __construct($sender = '', $applicationFolder = false) {
        parent::__construct($sender, 'Vanilla');
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
     * @param $text
     * @param $url
     */
    public function addButton($text, $url) {
        $this->Buttons[] = ['Text' => $text, 'Url' => $url];
    }

    /**
     * Render the module.
     *
     * @return string
     */
    public function toString() {
        // Set CategoryID if we have one.
        if (c('Vanilla.Categories.Use', true) && $this->CategoryID === null) {
            $this->CategoryID = Gdn::controller()->data('Category.CategoryID', false);
        }

        // Allow plugins and themes to modify parameters.
        Gdn::controller()->EventArguments['NewDiscussionModule'] = &$this;
        Gdn::controller()->fireEvent('BeforeNewDiscussionButton');

        // Make sure the user has the most basic of permissions first.
        $permissionCategory = CategoryModel::permissionCategory($this->CategoryID);
        if ($this->CategoryID) {
            $category = CategoryModel::categories($this->CategoryID);
            $hasPermission = CategoryModel::checkPermission($this->CategoryID, 'Vanilla.Discussions.Add');
        } else {
            $hasPermission = Gdn::session()->checkPermission('Vanilla.Discussions.Add', true, 'Category', 'any');
        }

        // Determine if this is a guest & we're using "New Discussion" button as call to action.
        $privilegedGuest = ($this->ShowGuests && !Gdn::session()->isValid());

        // No module for you!
        if (!$hasPermission && !$privilegedGuest) {
            return '';
        }

        // Grab the allowed discussion types.
        $discussionTypes = CategoryModel::allowedDiscussionTypes($permissionCategory, isset($category) ? $category : []);

        foreach ($discussionTypes as $key => $type) {
            if (isset($type['AddPermission']) && !Gdn::session()->checkPermission($type['AddPermission'])) {
                unset($discussionTypes[$key]);
                continue;
            }

            $url = val('AddUrl', $type);
            if (!$url) {
                continue;
            }

            if (isset($category)) {
                $url .= '/'.rawurlencode(val('UrlCode', $category));
            }

            // Present a signin redirect for a $PrivilegedGuest.
            if (!$hasPermission) {
                $url = $this->GuestUrl.'?Target='.$url;
            }

            $this->addButton(t(val('AddText', $type)), $url);
        }

        // Add QueryString to URL if one is defined.
        if ($this->QueryString && $hasPermission) {
            foreach ($this->Buttons as &$row) {
                $row['Url'] .= (strpos($row['Url'], '?') !== false ? '&' : '?').$this->QueryString;
            }
        }

        return parent::toString();
    }
}
