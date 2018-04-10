<?php
/**
 * Message model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles message data.
 */
class MessageModel extends Gdn_Model {

    /** @var array Non-standard message location allowed. */
    private $_SpecialLocations = ['[Base]', '[NonAdmin]'];

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
     * @param array|int $where The where clause to delete or an integer value.
     * @param array|true $options An array of options to control the delete.
     * @return bool Returns **true** on success or **false** on failure.
     */
    public function delete($where = [], $options = []) {
        $result = parent::delete($where, $options);
        self::messages(null);
        return $result;
    }

    /**
     * Returns a single message object for the specified id or FALSE if not found.
     *
     * @param int $messageID The MessageID to filter to.
     * @param string|false $datasetType The format of the message.
     * @param array $options Options to modify the behavior of the get.
     * @return array Requested message.
     */
    public function getID($messageID, $datasetType = false, $options = []) {
        if (Gdn::cache()->activeEnabled()) {
            $message = self::messages($messageID);
            if (!$message) {
                return $message;
            }
            if ($message instanceof Gdn_DataSet) {
                $message->datasetType($datasetType);
            } elseif ($datasetType === DATASET_TYPE_OBJECT) {
                return (object)$message;
            } else {
                return (array)$message;
            }
        } else {
            return parent::getID($messageID, $datasetType, $options);
        }
    }

    /**
     * Build the Message's Location property and add it.
     *
     * @param array|object $message Message data.
     * @return array|object Message data with Location property/key added.
     */
    public function defineLocation($message) {
        $controller = val('Controller', $message);
        $application = val('Application', $message);
        $method = val('Method', $message);

        if (in_array($controller, $this->_SpecialLocations)) {
            setValue('Location', $message, $controller);
        } else {
            setValue('Location', $message, $application);
            if (!stringIsNullOrEmpty($controller)) {
                setValue('Location', $message, val('Location', $message).'/'.$controller);
            }
            if (!stringIsNullOrEmpty($method)) {
                setValue('Location', $message, val('Location', $message).'/'.$method);
            }
        }

        return $message;
    }

    /**
     * Whether we are in (or optionally below) a category.
     *
     * @param int $needleCategoryID
     * @param int $haystackCategoryID
     * @param bool $includeSubcategories
     * @return bool
     */
    protected static function inCategory($needleCategoryID, $haystackCategoryID, $includeSubcategories = false) {
        if (!$haystackCategoryID) {
            return true;
        }

        if ($needleCategoryID == $haystackCategoryID) {
            return true;
        }

        if ($includeSubcategories) {
            $cat = CategoryModel::categories($needleCategoryID);
            for ($i = 0; $i < 10; $i++) {
                if (!$cat) {
                    break;
                }

                if ($cat['CategoryID'] == $haystackCategoryID) {
                    return true;
                }

                $cat = CategoryModel::categories($cat['ParentCategoryID']);
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
    public function getMessagesForLocation($Location, $Exceptions = ['[Base]'], $CategoryID = null) {
        $Session = Gdn::session();
        $Prefs = $Session->getPreference('DismissedMessages', []);
        if (count($Prefs) == 0) {
            $Prefs[] = 0;
        }

        $category = null;
        if (!empty($CategoryID)) {
            $category = CategoryModel::categories($CategoryID);
        }

        $Exceptions = array_map('strtolower', $Exceptions);

        list($Application, $Controller, $Method) = explode('/', strtolower($Location));

        if (Gdn::cache()->activeEnabled()) {
            // Get the messages from the cache.
            $Messages = self::messages();
            $Result = [];
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
                } elseif ($MApplication == $Application && $MController == $Controller && $MMethod == $Method) {
                    $Visible = true;
                }

                $Visible = $Visible && self::inCategory($CategoryID, val('CategoryID', $Message), val('IncludeSubcategories', $Message));
                if ($category !== null) {
                    $Visible &= CategoryModel::checkPermission($category, 'Vanilla.Discussions.View');
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

        $Result = array_filter($Result, function($Message) use ($Session, $category) {
            $visible = MessageModel::inCategory(val('CategoryID', $category, null), val('CategoryID', $Message), val('IncludeSubcategories', $Message));
            if ($category !== null) {
                $visible = $visible && CategoryModel::checkPermission($category, 'Vanilla.Discussions.View');
            }
            return $visible;
        });

        return $Result;
    }

    /**
     * Returns a distinct array of controllers that have enabled messages.
     *
     * @return array Locations with enabled messages.
     */
    public function getEnabledLocations() {
        $data = $this->SQL
            ->select('Application,Controller,Method')
            ->from('Message')
            ->where('Enabled', '1')
            ->groupBy('Application,Controller,Method')
            ->get();

        $locations = [];
        foreach ($data as $row) {
            if (in_array($row->Controller, $this->_SpecialLocations)) {
                $locations[] = $row->Controller;
            } else {
                $location = $row->Application;
                if ($row->Controller != '') {
                    $location .= '/'.$row->Controller;
                }
                if ($row->Method != '') {
                    $location .= '/'.$row->Method;
                }
                $locations[] = $location;
            }
        }
        return $locations;
    }

    /**
     * Get all messages or one message.
     *
     * @param int|bool $iD ID of message to get.
     * @return array|null
     */
    public static function messages($iD = false) {
        if ($iD === null) {
            Gdn::cache()->remove('Messages');
            return;
        }

        $messages = Gdn::cache()->get('Messages');
        if ($messages === Gdn_Cache::CACHEOP_FAILURE) {
            $messages = Gdn::sql()->get('Message', 'Sort')->resultArray();
            $messages = Gdn_DataSet::index($messages, ['MessageID']);
            Gdn::cache()->store('Messages', $messages);
        }
        if ($iD === false) {
            return $messages;
        } else {
            return val($iD, $messages);
        }
    }

    /**
     * Save a message.
     *
     * @param array $formPostValues Message data.
     * @param bool $settings
     * @return int The MessageID.
     */
    public function save($formPostValues, $settings = false) {
        // The "location" is packed into a single input with a / delimiter. Need to explode it into three different fields for saving
        $location = val('Location', $formPostValues, '');
        if ($location != '') {
            $location = explode('/', $location);
            $application = val(0, $location, '');
            if (in_array($application, $this->_SpecialLocations)) {
                $formPostValues['Application'] = null;
                $formPostValues['Controller'] = $application;
                $formPostValues['Method'] = null;
            } else {
                $formPostValues['Application'] = $application;
                $formPostValues['Controller'] = val(1, $location, '');
                $formPostValues['Method'] = val(2, $location, '');
            }
        }
        Gdn::cache()->remove('Messages');

        return parent::save($formPostValues, $settings);
    }

    /**
     * Save our current message locations in the config.
     */
    public function setMessageCache() {
        // Retrieve an array of all controllers that have enabled messages associated
        saveToConfig('Garden.Messages.Cache', $this->getEnabledLocations());
    }
}
