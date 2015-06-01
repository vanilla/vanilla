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
     *
     *
     * @param string $Where
     * @param bool $Limit
     * @param bool $ResetData
     */
    public function Delete($Where = '', $Limit = FALSE, $ResetData = FALSE) {
        parent::Delete($Where, $Limit, $ResetData);
        self::Messages(NULL);
    }

    /**
     * Returns a single message object for the specified id or FALSE if not found.
     *
     * @param int The MessageID to filter to.
     */
    public function GetID($MessageID) {
        if (Gdn::Cache()->ActiveEnabled())
            return self::Messages($MessageID);
        else
            return parent::GetID($MessageID);
    }

    /**
     * Build the Message's Location property and add it.
     *
     * @param mixed $Message Array or object.
     * @return mixed Array or object given with Location property/key added.
     */
    public function DefineLocation($Message) {
        $Controller = GetValue('Controller', $Message);
        $Application = GetValue('Application', $Message);
        $Method = GetValue('Method', $Message);

        if (in_array($Controller, $this->_SpecialLocations)) {
            SetValue('Location', $Message, $Controller);
        } else {
            SetValue('Location', $Message, $Application);
            if (!StringIsNullOrEmpty($Controller))
                SetValue('Location', $Message, GetValue('Location', $Message).'/'.$Controller);
            if (!StringIsNullOrEmpty($Method))
                SetValue('Location', $Message, GetValue('Location', $Message).'/'.$Method);
        }

        return $Message;
    }

    /**
     *
     *
     * @param $NeedleCategoryID
     * @param $HaystackCategoryID
     * @param bool $IncludeSubcategories
     * @return bool
     */
    protected function InCategory($NeedleCategoryID, $HaystackCategoryID, $IncludeSubcategories = FALSE) {
        if (!$HaystackCategoryID)
            return TRUE;

        if ($NeedleCategoryID == $HaystackCategoryID)
            return TRUE;

        if ($IncludeSubcategories) {
            $Cat = CategoryModel::Categories($NeedleCategoryID);
            for ($i = 0; $i < 10; $i++) {
                if (!$Cat)
                    break;

                if ($Cat['CategoryID'] == $HaystackCategoryID)
                    return TRUE;

                $Cat = CategoryModel::Categories($Cat['ParentCategoryID']);
            }
        }

        return FALSE;
    }

    /**
     *
     *
     * @param $Location
     * @param array $Exceptions
     * @param null $CategoryID
     * @return array|null
     */
    public function GetMessagesForLocation($Location, $Exceptions = array('[Base]'), $CategoryID = NULL) {
        $Session = Gdn::Session();
        $Prefs = $Session->GetPreference('DismissedMessages', array());
        if (count($Prefs) == 0)
            $Prefs[] = 0;

        $Exceptions = array_map('strtolower', $Exceptions);

        list($Application, $Controller, $Method) = explode('/', strtolower($Location));

        if (Gdn::Cache()->ActiveEnabled()) {
            // Get the messages from the cache.
            $Messages = self::Messages();
            $Result = array();
            foreach ($Messages as $MessageID => $Message) {
                if (in_array($MessageID, $Prefs) || !$Message['Enabled'])
                    continue;

                $MApplication = strtolower($Message['Application']);
                $MController = strtolower($Message['Controller']);
                $MMethod = strtolower($Message['Method']);

                $Visible = FALSE;

                if (in_array($MController, $Exceptions))
                    $Visible = TRUE;
                elseif ($MApplication == $Application && $MController == $Controller && $MMethod == $Method)
                    $Visible = TRUE;

                if ($Visible && !$this->InCategory($CategoryID, GetValue('CategoryID', $Message), GetValue('IncludeSubcategories', $Message)))
                    $Visible = FALSE;

                if ($Visible)
                    $Result[] = $Message;
            }
            return $Result;
        }

        $Result = $this->SQL
            ->Select()
            ->From('Message')
            ->Where('Enabled', '1')
            ->BeginWhereGroup()
            ->WhereIn('Controller', $Exceptions)
            ->OrOp()
            ->BeginWhereGroup()
            ->OrWhere('Application', $Application)
            ->Where('Controller', $Controller)
            ->Where('Method', $Method)
            ->EndWhereGroup()
            ->EndWhereGroup()
            ->WhereNotIn('MessageID', $Prefs)
            ->OrderBy('Sort', 'asc')
            ->Get()->ResultArray();

        $Result = array_filter($Result, function ($Message) use ($CategoryID) {
            return $this->InCategory($CategoryID, GetValue('CategoryID', $Message), GetValue('IncludeSubcategories', $Message));
        });

        return $Result;
    }

    /**
     * Returns a distinct array of controllers that have enabled messages.
     */
    public function GetEnabledLocations() {
        $Data = $this->SQL
            ->Select('Application,Controller,Method')
            ->From('Message')
            ->Where('Enabled', '1')
            ->GroupBy('Application,Controller,Method')
            ->Get();

        $Locations = array();
        foreach ($Data as $Row) {
            if (in_array($Row->Controller, $this->_SpecialLocations)) {
                $Locations[] = $Row->Controller;
            } else {
                $Location = $Row->Application;
                if ($Row->Controller != '') $Location .= '/'.$Row->Controller;
                if ($Row->Method != '') $Location .= '/'.$Row->Method;
                $Locations[] = $Location;
            }
        }
        return $Locations;
    }

    /**
     *
     *
     * @param bool $ID
     * @return array|mixed|null|type|void
     */
    public static function Messages($ID = FALSE) {
        if ($ID === NULL) {
            Gdn::Cache()->Remove('Messages');
            return;
        }

        $Messages = Gdn::Cache()->Get('Messages');
        if ($Messages === Gdn_Cache::CACHEOP_FAILURE) {
            $Messages = Gdn::SQL()->Get('Message', 'Sort')->ResultArray();
            $Messages = Gdn_DataSet::Index($Messages, array('MessageID'));
            Gdn::Cache()->Store('Messages', $Messages);
        }
        if ($ID === FALSE)
            return $Messages;
        else
            return GetValue($ID, $Messages);
    }

    /**
     *
     *
     * @param array $FormPostValues
     * @param bool $Settings
     * @return unknown
     */
    public function Save($FormPostValues, $Settings = FALSE) {
        // The "location" is packed into a single input with a / delimiter. Need to explode it into three different fields for saving
        $Location = ArrayValue('Location', $FormPostValues, '');
        if ($Location != '') {
            $Location = explode('/', $Location);
            $Application = GetValue(0, $Location, '');
            if (in_array($Application, $this->_SpecialLocations)) {
                $FormPostValues['Application'] = NULL;
                $FormPostValues['Controller'] = $Application;
                $FormPostValues['Method'] = NULL;
            } else {
                $FormPostValues['Application'] = $Application;
                $FormPostValues['Controller'] = GetValue(1, $Location, '');
                $FormPostValues['Method'] = GetValue(2, $Location, '');
            }
        }
        Gdn::Cache()->Remove('Messages');

        return parent::Save($FormPostValues, $Settings);
    }

    /**
     *
     */
    public function SetMessageCache() {
        // Retrieve an array of all controllers that have enabled messages associated
        SaveToConfig('Garden.Messages.Cache', $this->GetEnabledLocations());
    }
}
