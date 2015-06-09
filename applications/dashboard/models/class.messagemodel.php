<?php
/**
 * Message model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles message data.
 */
class MessageModel extends Gdn_Model {

    /** @var array Non-standard message location allowed. */
    private $_SpecialLocations = array('[Base]', '[Admin]', '[NonAdmin]');

    /** @var array Current message data. */
    protected static $Messages;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Message');
    }

    /**
     * Delete a message.
     *
     * @param string $Where
     * @param bool $Limit
     * @param bool $ResetData
     */
    public function delete($Where = '', $Limit = false, $ResetData = false) {
        parent::delete($Where, $Limit, $ResetData);
        self::messages(null);
    }

    /**
     * Returns a single message object for the specified id or FALSE if not found.
     *
     * @param int $MessageID The MessageID to filter to.
     * @return array Requested message.
     */
    public function getID($MessageID) {
        if (Gdn::cache()->activeEnabled()) {
            return self::messages($MessageID);
        } else {
            return parent::getID($MessageID);
        }
    }

    /**
     * Build the Message's Location property and add it.
     *
     * @param array|object $Message Message data.
     * @return array|object Message data with Location property/key added.
     */
    public function defineLocation($Message) {
        $Controller = val('Controller', $Message);
        $Application = val('Application', $Message);
        $Method = val('Method', $Message);

        if (in_array($Controller, $this->_SpecialLocations)) {
            setValue('Location', $Message, $Controller);
        } else {
            setValue('Location', $Message, $Application);
            if (!stringIsNullOrEmpty($Controller)) {
                setValue('Location', $Message, val('Location', $Message).'/'.$Controller);
            }
            if (!stringIsNullOrEmpty($Method)) {
                setValue('Location', $Message, val('Location', $Message).'/'.$Method);
            }
        }

        return $Message;
    }

    /**
     * Whether we are in (or optionally below) a category.
     *
     * @param int $NeedleCategoryID
     * @param int $HaystackCategoryID
     * @param bool $IncludeSubcategories
     * @return bool
     */
    protected static function inCategory($NeedleCategoryID, $HaystackCategoryID, $IncludeSubcategories = false) {
        if (!$HaystackCategoryID) {
            return true;
        }

        if ($NeedleCategoryID == $HaystackCategoryID) {
            return true;
        }

        if ($IncludeSubcategories) {
            $Cat = CategoryModel::categories($NeedleCategoryID);
            for ($i = 0; $i < 10; $i++) {
                if (!$Cat) {
                    break;
                }

                if ($Cat['CategoryID'] == $HaystackCategoryID) {
                    return true;
                }

                $Cat = CategoryModel::categories($Cat['ParentCategoryID']);
            }
        }

        return false;
    }

    /**
     * Get what messages are active for a template location.
     *
     * @param $Location
     * @param array $Exceptions
     * @param null $CategoryID
     * @return array|null
     */
    public function getMessagesForLocation($Location, $Exceptions = array('[Base]'), $CategoryID = null) {
        $Session = Gdn::session();
        $Prefs = $Session->getPreference('DismissedMessages', array());
        if (count($Prefs) == 0) {
            $Prefs[] = 0;
        }

        $Exceptions = array_map('strtolower', $Exceptions);

        list($Application, $Controller, $Method) = explode('/', strtolower($Location));

        if (Gdn::cache()->activeEnabled()) {
            // Get the messages from the cache.
            $Messages = self::messages();
            $Result = array();
            foreach ($Messages as $MessageID => $Message) {
                if (in_array($MessageID, $Prefs) || !$Message['Enabled']) {
                    continue;
                }

                $MApplication = strtolower($Message['Application']);
                $MController = strtolower($Message['Controller']);
                $MMethod = strtolower($Message['Method']);

                $Visible = false;

                if (in_array($MController, $Exceptions)) {
                    $Visible = true;
                } elseif ($MApplication == $Application && $MController == $Controller && $MMethod == $Method)
                    $Visible = true;

                if ($Visible && !self::inCategory($CategoryID, val('CategoryID', $Message), val('IncludeSubcategories', $Message))) {
                    $Visible = false;
                }

                if ($Visible) {
                    $Result[] = $Message;
                }
            }
            return $Result;
        }

        $Result = $this->SQL
            ->select()
            ->from('Message')
            ->where('Enabled', '1')
            ->beginWhereGroup()
            ->whereIn('Controller', $Exceptions)
            ->orOp()
            ->beginWhereGroup()
            ->orWhere('Application', $Application)
            ->where('Controller', $Controller)
            ->where('Method', $Method)
            ->endWhereGroup()
            ->endWhereGroup()
            ->whereNotIn('MessageID', $Prefs)
            ->orderBy('Sort', 'asc')
            ->get()->resultArray();

        $Result = array_filter($Result, function ($Message) use ($CategoryID) {
            return MessageModel::inCategory($CategoryID, val('CategoryID', $Message), val('IncludeSubcategories', $Message));
        });

        return $Result;
    }

    /**
     * Returns a distinct array of controllers that have enabled messages.
     *
     * @return array Locations with enabled messages.
     */
    public function getEnabledLocations() {
        $Data = $this->SQL
            ->select('Application,Controller,Method')
            ->from('Message')
            ->where('Enabled', '1')
            ->groupBy('Application,Controller,Method')
            ->get();

        $Locations = array();
        foreach ($Data as $Row) {
            if (in_array($Row->Controller, $this->_SpecialLocations)) {
                $Locations[] = $Row->Controller;
            } else {
                $Location = $Row->Application;
                if ($Row->Controller != '') {
                    $Location .= '/'.$Row->Controller;
                }
                if ($Row->Method != '') {
                    $Location .= '/'.$Row->Method;
                }
                $Locations[] = $Location;
            }
        }
        return $Locations;
    }

    /**
     * Get all messages or one message.
     *
     * @param int|bool $ID ID of message to get.
     * @return array|null
     */
    public static function messages($ID = false) {
        if ($ID === null) {
            Gdn::cache()->remove('Messages');
            return;
        }

        $Messages = Gdn::cache()->get('Messages');
        if ($Messages === Gdn_Cache::CACHEOP_FAILURE) {
            $Messages = Gdn::sql()->get('Message', 'Sort')->resultArray();
            $Messages = Gdn_DataSet::index($Messages, array('MessageID'));
            Gdn::cache()->store('Messages', $Messages);
        }
        if ($ID === false) {
            return $Messages;
        } else {
            return val($ID, $Messages);
        }
    }

    /**
     * Save a message.
     *
     * @param array $FormPostValues Message data.
     * @param bool $Settings
     * @return int The MessageID.
     */
    public function save($FormPostValues, $Settings = false) {
        // The "location" is packed into a single input with a / delimiter. Need to explode it into three different fields for saving
        $Location = arrayValue('Location', $FormPostValues, '');
        if ($Location != '') {
            $Location = explode('/', $Location);
            $Application = val(0, $Location, '');
            if (in_array($Application, $this->_SpecialLocations)) {
                $FormPostValues['Application'] = null;
                $FormPostValues['Controller'] = $Application;
                $FormPostValues['Method'] = null;
            } else {
                $FormPostValues['Application'] = $Application;
                $FormPostValues['Controller'] = val(1, $Location, '');
                $FormPostValues['Method'] = val(2, $Location, '');
            }
        }
        Gdn::cache()->remove('Messages');

        return parent::save($FormPostValues, $Settings);
    }

    /**
     * Save our current message locations in the config.
     */
    public function setMessageCache() {
        // Retrieve an array of all controllers that have enabled messages associated
        saveToConfig('Garden.Messages.Cache', $this->getEnabledLocations());
    }
}
