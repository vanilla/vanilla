<?php
/**
 * Object for importing files created with VanillaPorter.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Manages imports.
 */
class ImportModel extends Gdn_Model {

    /** Comment character in the import file. */
    const COMMENT = '//';

    /** Delimiter character in the import file. */
    const DELIM = ',';

    /** Escape character in the import file. */
    const ESCAPE = '\\';

    /** Newline character in the import file. */
    const NEWLINE = "\n";

    /** Null character in the import file. */
    const NULL = '\N';

    /** Quote character in the import file. */
    const QUOTE = '"';

    /** Temporary table prefix for import data. */
    const TABLE_PREFIX = 'z';

    /** Padding to add to IDs that are incremented. */
    const ID_PADDING = 1000;

    /** @var int Track what step of this process we're on. */
    public $CurrentStep = 0;

    /** @var array  */
    public $Data = array();

    /** @var string Error to return. */
    public $ErrorType = '';

    /** @var string File location. */
    public $ImportPath = '';

    /** @var int Max seconds to run a single step. */
    public $MaxStepTime = 1;

    /** @var array Method names in order they are called during a merge. */
    protected $_MergeSteps = array(
        1 => 'Initialize',
        2 => 'ProcessImportFile',
        3 => 'DefineTables',
        4 => 'LoadTables',
        5 => 'DefineIndexes',
        6 => 'AssignUserIDs',
        7 => 'AssignOtherIDs',
        8 => 'InsertTables',
        9 => 'UpdateCounts',
        10 => 'CustomFinalization',
        11 => 'AddActivity',
        12 => 'VerifyImport'
    );

    /** @var array Method names in order they are called during a clean slate import from file. */
    protected $_OverwriteSteps = array(
        0 => 'Initialize',
        1 => 'ProcessImportFile',
        2 => 'DefineTables',
        3 => 'LoadUserTable',
        4 => 'AuthenticateAdminUser',
        5 => 'InsertUserTable',
        6 => 'LoadTables',
        7 => 'DeleteOverwriteTables',
        8 => 'InsertTables',
        9 => 'UpdateCounts',
        10 => 'CustomFinalization',
        11 => 'AddActivity',
        12 => 'VerifyImport'
    );

    /** @var array Steps for importing from database. */
    protected $_OverwriteStepsDb = array(
        0 => 'Initialize',
        1 => 'ProcessImportDb',
        2 => 'DefineTables',
        3 => 'InsertUserTable',
        4 => 'DeleteOverwriteTables',
        5 => 'InsertTables',
        6 => 'UpdateCounts',
        7 => 'CustomFinalization',
        8 => 'AddActivity',
        9 => 'VerifyImport'
    );

    /** @var Gdn_Timer Used for timing various long running methods to break them up into pieces. */
    public $Timer = null;

    /**
     * Set the import path.
     */
    public function __construct($ImportPath = '') {
        $this->ImportPath = $ImportPath;
        parent::__construct();
    }

    /**
     *
     *
     * @return bool
     * @throws Gdn_UserException
     */
    public function addActivity() {
        // Build the story for the activity.
        $Header = $this->getImportHeader();
        $PorterVersion = val('Vanilla Export', $Header, t('unknown'));
        $SourceData = val('Source', $Header, t('unknown'));
        $Story = sprintf(t('Vanilla Export: %s, Source: %s'), $PorterVersion, $SourceData);

        $ActivityModel = new ActivityModel();
        $ActivityModel->add(Gdn::session()->UserID, 'Import', $Story);
        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function assignUserIDs() {
        // Assign user IDs of email matches.
        $Sql = "update :_zUser i
         join :_User u
           on i.Email = u.Email
         set i._NewID = u.UserID, i._Action = 'Update'";
        $this->query($Sql);

        // Assign user IDs of name matches.
        $Sql = "update :_zUser i
         join :_User u
            on i.Name = u.Name
         left join :_zUser i2
            on i2._NewID = u.UserID /* make sure no duplicates */
         set i._NewID = u.UserID, i._Action = 'Update'
         where i._NewID is null and i2.UserID is null";
        $this->query($Sql);

        // Get the max UserID so we can increment new users.
        $MaxID = $this->query('select max(UserID) as MaxID from :_User')->value('MaxID', 0);
        $MinID = $this->query('select min(UserID) as MinID from :_zUser where _NewID is null')->value('MinID', null);

        if (is_null($MinID)) {
            //$this->Timer->Split('No more IDs to update');
            // No more IDs to update.
            return true;
        }

        $IDInc = $MaxID - $MinID + self::ID_PADDING;

        // Update the users to insert.
        $Sql = "update :_zUser i
         left join :_User u
            on u.Name = i.Name /* make sure no duplicates */
         set i._NewID = i.UserID + $IDInc, i._Action = 'Insert'
         where i._NewID is null
            and u.UserID is null";
        $this->query($Sql);

        // There still might be users that have overlapping usernames which must be changed.
        // Append a random suffix to the new username.
        $Sql = "update :_zUser i
         set i.Name = concat(i.Name, convert(floor(1000 + rand() * 8999), char)), i._NewID = i.UserID + $IDInc, i._Action = 'Insert'
         where i._NewID is null";
        $this->query($Sql);

        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function assignOtherIDs() {
        $this->_assignIDs('Role', 'RoleID', 'Name');
        $this->_assignIDs('Category', 'CategoryID', 'Name');
        $this->_assignIDs('Discussion');
        $this->_assignIDs('Comment');

        return true;
    }

    /**
     *
     *
     * @param $TableName
     * @param null $PrimaryKey
     * @param null $SecondaryKey
     * @return bool|void
     */
    protected function _assignIDs($TableName, $PrimaryKey = null, $SecondaryKey = null) {
        if (!array_key_exists($TableName, $this->Tables())) {
            return;
        }

        if (!$PrimaryKey) {
            $PrimaryKey = $TableName.'ID';
        }

        // Assign existing IDs.
        if ($SecondaryKey) {
            $Sql = "update :_z$TableName i
            join :_$TableName t
              on t.$SecondaryKey = i.$SecondaryKey
            set i._NewID = t.$PrimaryKey, i._Action = 'Update'";
            $this->query($Sql);
        }

        // Get new IDs.
        $MaxID = $this->query("select max($PrimaryKey) as MaxID from :_$TableName")->value('MaxID', 0);
        $MinID = $this->query("select min($PrimaryKey) as MinID from :_z$TableName where _NewID is null")->value('MinID', null);

        if (is_null($MinID)) {
            //$this->Timer->Split('No more IDs to update');
            // No more IDs to update.
            return true;
        }
        if ($MaxID == 0) {
            $IDInc = 0;
        } else {
            $IDInc = $MaxID - $MinID + self::ID_PADDING;
        }

        $Sql = "update :_z$TableName i
         set i._NewID = i.$PrimaryKey + $IDInc, i._Action = 'Insert'
         where i._NewID is null";
        $this->query($Sql);
    }

    /**
     *
     *
     * @return bool
     */
    public function authenticateAdminUser() {
        $OverwriteEmail = val('OverwriteEmail', $this->Data);
        $OverwritePassword = val('OverwritePassword', $this->Data);

        $Data = Gdn::sql()->getWhere('zUser', array('Email' => $OverwriteEmail));
        if ($Data->numRows() == 0) {
            $Result = false;
        } else {
            $Result = true;
        }
        if (!$Result) {
            $this->Validation->addValidationResult('Email', t('ErrorCredentials'));
            $this->ErrorType = 'Credentials';
        }
        return $Result;

    }

    /**
     *
     *
     * @return bool
     */
    public function customFinalization() {
        $this->setRoleDefaults();
        PermissionModel::resetAllRoles();

        $Imp = $this->getCustomImportModel();
        if ($Imp !== null) {
            $Imp->afterImport();
        }

        return true;
    }

    /**
     *
     *
     * @param $Key
     * @param null $Value
     * @return mixed
     */
    public function data($Key, $Value = null) {
        if ($Value === null) {
            return val($Key, $this->Data);
        }
        $this->Data[$Key] = $Value;
    }

    /**
     *
     *
     * @return bool
     * @throws Gdn_UserException
     */
    public function defineTables() {
        $St = Gdn::structure();
        $DestStructure = clone $St;

        $Tables =& $this->tables();

        foreach ($Tables as $Table => $TableInfo) {
            $Columns = $TableInfo['Columns'];
            if (!is_array($Columns) || count($Columns) == 0) {
                throw new Gdn_UserException(sprintf(t('The %s table is not in the correct format.', $Table)));
            }


            $St->table(self::TABLE_PREFIX.$Table);
            // Get the structure from the destination database to match types.
            try {
                $DestStructure->reset()->get($Table);
            } catch (Exception $Ex) {
                // Trying to import into a non-existant table.
                $Tables[$Table]['Skip'] = true;
                continue;
            }
            //$DestColumns = $DestStructure->Columns();
            $DestModified = false;

            foreach ($Columns as $Name => $Type) {
                if (!$Name) {
                    throw new Gdn_UserException(sprintf(t('The %s table is not in the correct format.'), $Table));
                }

                if ($DestStructure->columnExists($Name)) {
                    $StructureType = $DestStructure->columnTypeString($DestStructure->columns($Name));
                } elseif ($DestStructure->columnExists($Type)) {
                    // Fix the table definition.
                    unset($Tables[$Table]['Columns'][$Name]);
                    $Tables[$Table]['Columns'][$Type] = '';

                    $Name = $Type;
                    $StructureType = $DestStructure->columnTypeString($DestStructure->columns($Type));
                } elseif (!stringBeginsWith($Name, '_')) {
                    $StructureType = $Type;

                    if (!$StructureType) {
                        $StructureType = 'varchar(255)';
                    }

                    // This is a new column so it needs to be added to the destination table too.
                    $DestStructure->column($Name, $StructureType, null);
                    $DestModified = true;
                } elseif ($Type) {
                    $StructureType = $Type;
                } else {
                    $StructureType = 'varchar(255)';
                }

                $St->column($Name, $StructureType, null);
            }
            // Add a new ID column.
            if (array_key_exists($Table.'ID', $Columns)) {
                $St
                    ->column('_NewID', $DestStructure->columnTypeString($Table.'ID'), null)
                    ->column('_Action', array('Insert', 'Update'), null);
            }

            try {
                if (!$this->isDbSource()) {
                    $St->set(true, true);
                } else {
                    $St->reset();
                }

                if ($DestModified) {
                    $DestStructure->set();
                }
            } catch (Exception $Ex) {
                // Since these exceptions are likely caused by a faulty import file they should be considered user exceptions.
                throw new Gdn_UserException(sprintf(t('There was an error while trying to create the %s table (%s).'), $Table, $Ex->getMessage())); //, $Ex);
            }
        }
        return true;
    }

    /**
     *
     *
     * @return bool
     * @throws Exception
     */
    public function defineIndexes() {
        $St = Gdn::structure();
        $DestStructure = clone Gdn::structure();

        foreach ($this->tables() as $Table => $TableInfo) {
            if (val('Skip', $TableInfo)) {
                continue;
            }

            $St->table(self::TABLE_PREFIX.$Table);
            $Columns = $TableInfo['Columns'];

            $DestStructure->reset()->get($Table);
            $DestColumns = $DestStructure->Columns();

            // Check to index the primary key.
            $Col = $Table.'ID';
            if (array_key_exists($Col, $Columns)) {
                $St->column($Col, $Columns[$Col] ? $Columns[$Col] : $DestStructure->columnTypeString($Col), null, 'index');
            }

            if ($Table == 'User') {
                $St
                    ->column('Name', $DestStructure->columnTypeString('Name'), null, 'index')
                    ->column('Email', $DestStructure->columnTypeString('Email'), null, 'index')
                    ->column('_NewID', 'int', null, 'index');
            }

            if (count($St->Columns()) > 0) {
                $St->set();
            }
        }
        return true;
    }

    /**
     *
     */
    public function deleteFiles() {
        if (stringBeginsWith($this->ImportPath, 'Db:', true)) {
            return;
        }

        $St = Gdn::structure();
        foreach (val('Tables', $this->Data, array()) as $Table => $TableInfo) {
            $Path = val('Path', $TableInfo, '');
            if (file_exists($Path)) {
                unlink($Path);
            }

            // Drop the import table.
            $St->table("z$Table")->drop();
        }

        // Delete the uploaded files.
        $UploadedFiles = val('UploadedFiles', $this->Data, array());
        foreach ($UploadedFiles as $Path => $Name) {
            @unlink($Path);
        }
    }

    /**
     * Remove the data from the appropriate tables when we are overwriting the forum.
     */
    public function deleteOverwriteTables() {
        $Tables = array('Activity', 'Category', 'Comment', 'Conversation', 'ConversationMessage',
            'Discussion', 'Draft', 'Invitation', 'Media', 'Message', 'Photo', 'Permission', 'Rank', 'Poll', 'PollOption', 'PollVote', 'Role', 'UserAuthentication',
            'UserComment', 'UserConversation', 'UserDiscussion', 'UserMeta', 'UserRole');

        // Execute the SQL.
        $CurrentSubstep = val('CurrentSubstep', $this->Data, 0);
        for ($i = $CurrentSubstep; $i < count($Tables); $i++) {
            $Table = $Tables[$i];

            // Make sure the table exists.
            $Exists = Gdn::structure()->table($Table)->tableExists();
            Gdn::structure()->reset();
            if (!$Exists) {
                continue;
            }

            $this->Data['CurrentStepMessage'] = $Table;

            if ($Table == 'Permission') {
                $this->SQL->delete($Table, array('RoleID <>' => 0));
            } else {
                $this->SQL->truncate($Table);
            }
            if ($this->Timer->elapsedTime() > $this->MaxStepTime) {
                // The step's taken too long. Save the state and return.
                $this->Data['CurrentSubstep'] = $i + 1;
                return false;
            }
        }
        if (isset($this->Data['CurrentSubstep'])) {
            unset($this->Data['CurrentSubstep']);
        }

        $this->Data['CurrentStepMessage'] = '';
        return true;
    }

    /**
     *
     */
    public function deleteState() {
        removeFromConfig('Garden.Import');
    }

    /**
     *
     *
     * @param $Post
     */
    public function fromPost($Post) {
        if (isset($Post['Overwrite'])) {
            $this->Data['Overwrite'] = $Post['Overwrite'];
        }
        if (isset($Post['Email'])) {
            $this->Data['OverwriteEmail'] = $Post['Email'];
        }
        if (isset($Post['GenerateSQL'])) {
            $this->Data['GenerateSQL'] = $Post['GenerateSQL'];
        }
    }

    /**
     *
     *
     * @param null $Value
     * @return mixed
     */
    public function generateSQL($Value = null) {
        return $this->data('GenerateSQL', $Value);
    }

    /**
     * Return SQL for updating a count.
     *
     * @param string $Aggregate count, max, min, etc.
     * @param string $ParentTable The name of the parent table.
     * @param string $ChildTable The name of the child table
     * @param string $ParentColumnName
     * @param string $ChildColumnName
     * @param string $ParentJoinColumn
     * @param string $ChildJoinColumn
     * @return Gdn_DataSet
     */
    public function getCountSQL(
        $Aggregate,
        $ParentTable,
        $ChildTable,
        $ParentColumnName = '',
        $ChildColumnName = '',
        $ParentJoinColumn = '',
        $ChildJoinColumn = ''
    ) {

        if (!$ParentColumnName) {
            switch (strtolower($Aggregate)) {
                case 'count':
                    $ParentColumnName = "Count{$ChildTable}s";
                    break;
                case 'max':
                    $ParentColumnName = "Last{$ChildTable}ID";
                    break;
                case 'min':
                    $ParentColumnName = "First{$ChildTable}ID";
                    break;
                case 'sum':
                    $ParentColumnName = "Sum{$ChildTable}s";
                    break;
            }
        }

        if (!$ChildColumnName) {
            $ChildColumnName = $ChildTable.'ID';
        }

        if (!$ParentJoinColumn) {
            $ParentJoinColumn = $ParentTable.'ID';
        }
        if (!$ChildJoinColumn) {
            $ChildJoinColumn = $ParentJoinColumn;
        }

        $Result = "update :_$ParentTable p
                  set p.$ParentColumnName = (
                     select $Aggregate(c.$ChildColumnName)
                     from :_$ChildTable c
                     where p.$ParentJoinColumn = c.$ChildJoinColumn)";
        return $Result;
    }

    /**
     * Get a custom import model based on the import's source.
     */
    public function getCustomImportModel() {
        $Result = null;

        // Get import type name.
        $Header = $this->getImportHeader();
        $Source = str_replace(' ', '', val('Source', $Header, ''));

        // Figure out if we have a custom import model for it.
        $SourceModelName = $Source.'ImportModel';
        if (class_exists($SourceModelName)) {
            $Result = new $SourceModelName();
            $Result->ImportModel = $this;
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Post
     */
    public function toPost(&$Post) {
        $D = $this->Data;
        $Post['Overwrite'] = val('Overwrite', $D, 'Overwrite');
        $Post['Email'] = val('OverwriteEmail', $D, '');
        $Post['GenerateSQL'] = val('GenerateSQL', $D, false);
    }

    /**
     *
     *
     * @param $fp
     * @param string $Delim
     * @param string $Quote
     * @param string $Escape
     * @return array
     */
    public static function fGetCSV2($fp, $Delim = ',', $Quote = '"', $Escape = "\\") {
        // Get the full line, considering escaped returns.
        $Line = false;
        do {
            $s = fgets($fp);
            //echo "<fgets>$s</fgets><br/>\n";

            if ($s === false) {
                if ($Line === false) {
                    return false;
                }
            }

            if ($Line === false) {
                $Line = $s;
            } else {
                $Line .= $s;
            }
        } while (strlen($s) > 1 && substr($s, -2, 1) === $Escape);

        $Line = trim($Line, "\n");
        //echo "<Line>$Line</Line><br />\n";

        $Result = array();

        // Loop through the line and split on the delimiter.
        $Strlen = strlen($Line);
        $InEscape = false;
        $InQuote = false;
        $Token = '';
        for ($i = 0; $i < $Strlen; ++$i) {
            $c = $Line[$i];

            if ($InEscape) {
                // Check for an escaped null.
                if ($c == 'N' && strlen($Token) == 0) {
                    $Token = null;
                } else {
                    $Token .= $c;
                }
                $InEscape = false;
            } else {
                switch ($c) {
                    case $Escape:
                        $InEscape = true;
                        break;
                    case $Delim:
                        $Result[] = $Token;
                        $Token = '';
                        break;
                    case $Quote:
                        $InQuote = !$InQuote;
                        break;
                    default:
                        $Token .= $c;
                        break;
                }
            }
        }
        $Result[] = $Token;

        return $Result;
    }

    /**
     *
     *
     * @param null $fpin
     * @return array|mixed|string
     * @throws Gdn_UserException
     */
    public function getImportHeader($fpin = null) {
        $Header = val('Header', $this->Data);
        if ($Header) {
            return $Header;
        }

        if (is_null($fpin)) {
            if (!$this->ImportPath || !file_exists($this->ImportPath)) {
                return array();
            }
            ini_set('auto_detect_line_endings', true);

            if (!is_readable($this->ImportPath)) {
                throw new Gdn_UserException(t('The input file is not readable.', 'The input file is not readable.  Please check permissions and try again.'));
            }

            $fpin = gzopen($this->ImportPath, 'rb');
            $fpopened = true;
        }

        $Header = fgets($fpin);
        if (!$Header || strlen($Header) < 7 || substr_compare('Vanilla', $Header, 0, 7) != 0) {
            if (isset($fpopened)) {
                fclose($fpin);
            }
            throw new Gdn_UserException(t('The import file is not in the correct format.'));
        }
        $Header = $this->ParseInfoLine($Header);
        if (isset($fpopened)) {
            fclose($fpin);
        }
        $this->Data['Header'] = $Header;
        return $Header;
    }

    /**
     *
     *
     * @return mixed|string
     * @throws Gdn_UserException
     */
    public function getPasswordHashMethod() {
        $HashMethod = val('HashMethod', $this->GetImportHeader());
        if ($HashMethod) {
            return $HashMethod;
        }

        $Source = val('Source', $this->GetImportHeader());
        if (!$Source) {
            return 'Unknown';
        }
        if (substr_compare('Vanilla', $Source, 0, 7, false) == 0) {
            return 'Vanilla';
        }
        if (substr_compare('vBulletin', $Source, 0, 9, false) == 0) {
            return 'vBulletin';
        }
        if (substr_compare('phpBB', $Source, 0, 5, false) == 0) {
            return 'phpBB';
        }
        return 'Unknown';
    }

    /** Checks to see of a table and/or column exists in the import data.
     *
     * @param string $Table The name of the table to check for.
     * @param string $Column
     * @return bool
     */
    public function importExists($Table, $Column = '') {
        if (!array_key_exists('Tables', $this->Data) || !array_key_exists($Table, $this->Data['Tables'])) {
            return false;
        }
        if (!$Column) {
            return true;
        }
        $Tables = $this->Data['Tables'];

        $Exists = valr("Tables.$Table.Columns.$Column", $this->Data, false);
        return $Exists !== false;
    }

    /**
     *
     *
     * @return bool
     */
    public function initialize() {
        if ($this->GenerateSQL()) {
            $this->SQL->CaptureModifications = true;
            Gdn::structure()->CaptureOnly = true;
            $this->Database->CapturedSql = array();

            $SQLPath = $this->data('SQLPath');
            if (!$SQLPath) {
                $SQLPath = 'import/import_'.date('Y-m-d_His').'.sql';
                $this->data('SQLPath', $SQLPath);
            }
        } else {
            // Importing will overwrite our System user record.
            // Our CustomFinalization step (e.g. vbulletinimportmodel) needs this to be regenerated.
            RemoveFromConfig('Garden.SystemUserID');
        }

        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function insertTables() {
        $InsertedCount = 0;
        $Timer = new Gdn_Timer();
        $Timer->start();
        $Tables =& $this->Tables();
        foreach ($Tables as $TableName => $TableInfo) {
            if (val('Inserted', $TableInfo) || val('Skip', $TableInfo)) {
                $InsertedCount++;
            } else {
                $this->Data['CurrentStepMessage'] = sprintf(t('%s of %s'), $InsertedCount, count($Tables));

                if (strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
                    switch ($TableName) {
                        case 'Permission':
                            $this->InsertPermissionTable();
                            break;
                        default:
                            $RowCount = $this->_InsertTable($TableName);
                            break;
                    }

                } else {
                    switch ($TableName) {
                        case 'Permission':
                            $this->InsertPermissionTable();
                            break;
                        case 'UserDiscussion':
                            $Sql = "insert ignore :_UserDiscussion ( UserID, DiscussionID, DateLastViewed, Bookmarked )
                        select zUserID._NewID, zDiscussionID._NewID, max(i.DateLastViewed) as DateLastViewed, max(i.Bookmarked) as Bookmarked
                        from :_zUserDiscussion i
                        left join :_zUser zUserID
                          on i.UserID = zUserID.UserID
                        left join :_zDiscussion zDiscussionID
                          on i.DiscussionID = zDiscussionID.DiscussionID
                        left join :_UserDiscussion ud
                          on ud.UserID = zUserID._NewID and ud.DiscussionID = zDiscussionID._NewID
                        where ud.UserID is null
                        group by zUserID._NewID, zDiscussionID._NewID";
                            $this->query($Sql);
                            break;
                        case 'UserMeta':
                            $Sql = "insert ignore :_UserMeta ( UserID, Name, Value )
                           select zUserID._NewID, i.Name, max(i.Value) as Value
                           from :_zUserMeta i
                           left join :_zUser zUserID
                             on i.UserID = zUserID.UserID
                           left join :_UserMeta um
                             on zUserID._NewID = um.UserID and i.Name = um.Name
                           where um.UserID is null
                           group by zUserID._NewID, i.Name";
                            $this->query($Sql);
                            break;
                        case 'UserRole':
                            $Sql = "insert ignore :_UserRole ( UserID, RoleID )
                           select zUserID._NewID, zRoleID._NewID
                           from :_zUserRole i
                           left join :_zUser zUserID
                             on i.UserID = zUserID.UserID
                           left join :_zRole zRoleID
                             on i.RoleID = zRoleID.RoleID
                           left join :_UserRole ur
                              on zUserID._NewID = ur.UserID and zRoleID._NewID = ur.RoleID
                           where i.UserID <> 0 and ur.UserID is null";
                            $this->query($Sql);
                            break;
                        default:
                            $RowCount = $this->_InsertTable($TableName);
                    }
                }

                $Tables[$TableName]['Inserted'] = true;
                if (isset($RowCount)) {
                    $Tables[$TableName]['RowCount'] = $RowCount;
                }
                $InsertedCount++;
                // Make sure the loading isn't taking too long.
                if ($Timer->ElapsedTime() > $this->MaxStepTime) {
                    break;
                }
            }
        }

        $Result = $InsertedCount == count($this->Tables());
        if ($Result) {
            $this->Data['CurrentStepMessage'] = '';
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Str
     * @return string
     */
    protected static function backTick($Str) {
        return '`'.str_replace('`', '\`', $Str).'`';
    }

    /**
     *
     *
     * @param $TableName
     * @param array $Sets
     * @return bool|int|void
     */
    protected function _InsertTable($TableName, $Sets = array()) {
        if (!array_key_exists($TableName, $this->Tables())) {
            return;
        }

        if (!Gdn::structure()->TableExists($TableName)) {
            return 0;
        }

        $TableInfo =& $this->Tables($TableName);
        $Columns = $TableInfo['Columns'];

        foreach ($Columns as $Key => $Value) {
            if (stringBeginsWith($Key, '_')) {
                unset($Columns[$Key]);
            }
        }

        // Build the column insert list.
        $Insert = "insert ignore :_$TableName (\n  "
            .implode(",\n  ", array_map(array('ImportModel', 'BackTick'), array_keys(array_merge($Columns, $Sets))))
            ."\n)";
        $From = "from :_z$TableName i";
        $Where = '';

        // Build the select list for the insert.
        $Select = array();
        foreach ($Columns as $Column => $X) {
            $BColumn = self::BackTick($Column);

            if (strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
                // The data goes in raw.
                $Select[] = "i.$BColumn";
            } elseif ($Column == $TableName.'ID') {
                // This is the primary key.
                $Select[] = "i._NewID as $Column";
                $Where = "\nwhere i._Action = 'Insert'";
            } elseif (substr_compare($Column, 'ID', -2, 2) == 0) {
                // This is an ID field. Check for a join.
                foreach ($this->Tables() as $StructureTableName => $TableInfo) {
                    $PK = $StructureTableName.'ID';
                    if (strlen($Column) >= strlen($PK) && substr_compare($Column, $PK, -strlen($PK), strlen($PK)) == 0) {
                        // This table joins and must update it's ID.
                        $From .= "\nleft join :_z$StructureTableName z$Column\n  on i.$Column = z$Column.$PK";
                        $Select[] = "z$Column._NewID";
                    }
                }
            } else {
                // This is a straight columns insert.
                $Select[] = "i.$BColumn";
            }
        }
        // Add the original table to prevent duplicates.
//      $PK = $TableName.'ID';
//      if(array_key_exists($PK, $Columns)) {
//      if(strcasecmp($this->Overwrite(), 'Overwrite') == 0)
//            $PK2 = $PK;
//         else
//            $PK2 = '_NewID';
//
//         $From .= "\nleft join :_$TableName o0\n  on o0.$PK = i.$PK2";
//         if($Where)
//            $Where .=  "\n  and ";
//         else
//            $Where = "\nwhere ";
//         $Where .= "o0.$PK is null";
//      }
        //}

        // Add the sets to the select list.
        foreach ($Sets as $Field => $Value) {
            $Select[] = Gdn::database()->connection()->quote($Value).' as '.$Field;
        }

        // Build the sql statement.
        $Sql = $Insert
            ."\nselect\n  ".implode(",\n  ", $Select)
            ."\n".$From
            .$Where;

        //$this->query($Sql);

        $RowCount = $this->query($Sql);
        if ($RowCount > 0) {
            return (int)$RowCount;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function insertPermissionTable() {
//      $this->LoadState();

        // Clear the permission table in case the step was only half done before.
        $this->SQL->delete('Permission', array('RoleID <>' => 0));

        // Grab all of the permission columns.
        $PM = new PermissionModel();
        $GlobalColumns = array_filter($PM->permissionColumns());
        unset($GlobalColumns['PermissionID']);
        $JunctionColumns = array_filter($PM->permissionColumns('Category', 'PermissionCategoryID'));
        unset($JunctionColumns['PermissionID']);
        $JunctionColumns = array_merge(array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => -1), $JunctionColumns);

        if ($this->importExists('Permission', 'JunctionTable')) {
            $ColumnSets = array(array_merge($GlobalColumns, $JunctionColumns));
            $ColumnSets[0]['JunctionTable'] = null;
            $ColumnSets[0]['JunctionColumn'] = null;
            $ColumnSets[0]['JunctionID'] = null;
        } else {
            $ColumnSets = array($GlobalColumns, $JunctionColumns);
        }

        $Data = $this->SQL->get('zPermission')->resultArray();
        foreach ($Data as $Row) {
            $Presets = array_map('trim', explode(',', val('_Permissions', $Row)));

            foreach ($ColumnSets as $ColumnSet) {
                $Set = array();
                $Set['RoleID'] = $Row['RoleID'];

                foreach ($Presets as $Preset) {
                    if (strpos($Preset, '.') !== false) {
                        // This preset is a specific permission.

                        if (array_key_exists($Preset, $ColumnSet)) {
                            $Set["`$Preset`"] = 1;
                        }
                        continue;
                    }
                    $Preset = strtolower($Preset);


                    foreach ($ColumnSet as $ColumnName => $Default) {
                        if (isset($Row[$ColumnName])) {
                            $Value = $Row[$ColumnName];
                        } elseif (strpos($ColumnName, '.') === false)
                            $Value = $Default;
                        elseif ($Preset == 'all')
                            $Value = 1;
                        elseif ($Preset == 'view')
                            $Value = StringEndsWith($ColumnName, 'View', true) && !in_array($ColumnName, array('Garden.Settings.View'));
                        elseif ($Preset == $ColumnName)
                            $Value = 1;
                        else {
                            $Value = $Default & 1;
                        }

                        $Set["`$ColumnName`"] = $Value;
                    }
                }
                $this->SQL->insert('Permission', $Set);
                unset($Set);
            }
        }
        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function insertUserTable() {
        $CurrentUser = $this->SQL->getWhere('User', array('UserID' => Gdn::session()->UserID))->firstRow(DATASET_TYPE_ARRAY);
        $CurrentPassword = $CurrentUser['Password'];
        $CurrentHashMethod = $CurrentUser['HashMethod'];

        // Delete the current user table.
        $this->SQL->Truncate('User');

        // Load the new user table.
        $UserTableInfo =& $this->Data['Tables']['User'];
        if (!$this->importExists('User', 'HashMethod')) {
            $this->_InsertTable('User', array('HashMethod' => $this->GetPasswordHashMethod()));
        } else {
            $this->_InsertTable('User');
        }
        $UserTableInfo['Inserted'] = true;

        $AdminEmail = val('OverwriteEmail', $this->Data);
        $SqlArgs = array(':Email' => $AdminEmail);
        $SqlSet = '';

        $SqlArgs[':Password'] = $CurrentPassword;
        $SqlArgs[':HashMethod'] = $CurrentHashMethod;
        $SqlSet = ', Password = :Password, HashMethod = :HashMethod';

        // If doing a password reset, save out the new admin password:
        if (strcasecmp($this->GetPasswordHashMethod(), 'reset') == 0) {
            if (!isset($SqlArgs[':Password'])) {
                $PasswordHash = new Gdn_PasswordHash();
                $Hash = $PasswordHash->HashPassword(val('OverwritePassword', $this->Data));
                $SqlSet .= ', Password = :Password, HashMethod = :HashMethod';
                $SqlArgs[':Password'] = $Hash;
                $SqlArgs[':HashMthod'] = 'Vanilla';
            }

            // Write it out.
            $this->query("update :_User set Admin = 1{$SqlSet} where Email = :Email", $SqlArgs);
        } else {
            // Set the admin user flag.
            $this->query("update :_User set Admin = 1{$SqlSet} where Email = :Email", $SqlArgs);
        }

        // Start the new session.
        $User = Gdn::userModel()->GetByEmail(val('OverwriteEmail', $this->Data));
        if (!$User) {
            $User = Gdn::userModel()->GetByUsername(val('OverwriteEmail', $this->Data));
        }

        Gdn::session()->start(val('UserID', $User), true);

        return true;
    }

    /**
     *
     *
     * @return bool|string
     */
    public function isDbSource() {
        return stringBeginsWith($this->ImportPath, 'Db:', true);
    }

    /**
     *
     *
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function loadUserTable() {
        if (!$this->importExists('User')) {
            throw new Gdn_UserException(t('The user table was not in the import file.'));
        }
        $UserTableInfo =& $this->Data['Tables']['User'];
        $Result = $this->LoadTable('User', $UserTableInfo['Path']);
        if ($Result) {
            $UserTableInfo['Loaded'] = true;
        }

        return $Result;
    }

    /**
     *
     */
    public function loadState() {
        $this->CurrentStep = c('Garden.Import.CurrentStep', 0);
        $this->Data = c('Garden.Import.CurrentStepData', array());
        $this->ImportPath = c('Garden.Import.ImportPath', '');
    }

    /**
     *
     *
     * @return bool
     * @throws Exception
     */
    public function loadTables() {
        $LoadedCount = 0;
        foreach ($this->Data['Tables'] as $Table => $TableInfo) {
            if (val('Loaded', $TableInfo) || val('Skip', $TableInfo)) {
                $LoadedCount++;
                continue;
            } else {
                $this->Data['CurrentStepMessage'] = $Table;
                $LoadResult = $this->LoadTable($Table, $TableInfo['Path']);
                if ($LoadResult) {
                    $this->Data['Tables'][$Table]['Loaded'] = true;
                    $LoadedCount++;
                } else {
                    break;
                }
            }
            // Make sure the loading isn't taking too long.
            if ($this->Timer->ElapsedTime() > $this->MaxStepTime) {
                break;
            }
        }
        $Result = $LoadedCount >= count($this->Data['Tables']);
        if ($Result) {
            $this->Data['CurrentStepMessage'] = '';
        }
        return $Result;
    }

    /**
     *
     *
     * @param $Tablename
     * @param $Path
     * @return bool
     * @throws Exception
     */
    public function loadTable($Tablename, $Path) {
        $Type = $this->LoadTableType();
        $Result = true;

        switch ($Type) {
            case 'LoadTableOnSameServer':
                $this->_LoadTableOnSameServer($Tablename, $Path);
                break;
            case 'LoadTableLocalInfile':
                $this->_LoadTableLocalInfile($Tablename, $Path);
                break;
            case 'LoadTableWithInsert':
                // This final option can be 15x slower than the other options.
                $Result = $this->_LoadTableWithInsert($Tablename, $Path);
                break;
            default:
                throw new Exception("@Error, unknown LoadTableType: $Type");
        }

        return $Result;
    }

    /**
     *
     *
     * @param $Tablename
     * @param $Path
     */
    protected function _LoadTableOnSameServer($Tablename, $Path) {
        $Tablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;
        $Path = Gdn::database()->connection()->quote($Path);

        Gdn::database()->query("truncate table $Tablename;");

        $Sql = "load data infile $Path into table $Tablename
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";
        $this->query($Sql);
    }

    /**
     *
     *
     * @param $Tablename
     * @param $Path
     */
    protected function _LoadTableLocalInfile($Tablename, $Path) {
        $Tablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;
        $Path = Gdn::database()->connection()->quote($Path);

        Gdn::database()->query("truncate table $Tablename;");

        $Sql = "load data local infile $Path into table $Tablename
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";

        // We've got to use the mysql_* functions because PDO doesn't support load data local infile well.
        $dblink = mysql_connect(c('Database.Host'), c('Database.User'), c('Database.Password'), false, 128);
        mysql_select_db(c('Database.Name'), $dblink);
        $Result = mysql_query($Sql, $dblink);
        if ($Result === false) {
            $Ex = new Exception(mysql_error($dblink));
            mysql_close($dblink);
            throw new $Ex;
        }
        mysql_close($dblink);
    }

    /**
     *
     * @param $Tablename
     * @param $Path
     * @return bool
     */
    protected function _LoadTableWithInsert($Tablename, $Path) {
        // This option could take a while so set the timeout.
        set_time_limit(60 * 5);

        // Get the column count of the table.
        $St = Gdn::structure();
        $St->get(self::TABLE_PREFIX.$Tablename);
        $ColumnCount = count($St->Columns());
        $St->reset();

        ini_set('auto_detect_line_endings', true);
        $fp = fopen($Path, 'rb');

        // Figure out the current position.
        $fPosition = val('CurrentLoadPosition', $this->Data, 0);
        if ($fPosition == 0) {
            // Skip the header row.
            $Row = self::FGetCSV2($fp);

            $Px = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX;
            Gdn::database()->query("truncate table {$Px}{$Tablename}");
        } else {
            fseek($fp, $fPosition);
        }

        $PDO = Gdn::database()->connection();
        $PxTablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;

        $Inserts = '';
        $Count = 0;
        while ($Row = self::FGetCSV2($fp)) {
            ++$Count;

            // Quote the values in the row.
            $Row = array_map(array($PDO, 'quote'), $Row);

            // Add any extra columns to the row.
            while (count($Row) < $ColumnCount) {
                $Row[] = 'null';
            }

            // Add the insert values to the sql.
            if (strlen($Inserts) > 0) {
                $Inserts .= ',';
            }
            $Inserts .= '('.implode(',', $Row).')';

            if ($Count >= 25) {
                // Insert in chunks.
                $Sql = "insert $PxTablename values $Inserts";
                $this->query($Sql);
                $Count = 0;
                $Inserts = '';

                // Check for a timeout.
                if ($this->Timer->ElapsedTime() > $this->MaxStepTime) {
                    // The step's taken too long. Save the file position.
                    $Pos = ftell($fp);
                    $this->Data['CurrentLoadPosition'] = $Pos;

                    $Filesize = filesize($Path);
                    if ($Filesize > 0) {
                        $PercentComplete = $Pos / filesize($Path);
                        $this->Data['CurrentStepMessage'] = $Tablename.' ('.round($PercentComplete * 100.0).'%)';
                    }

                    fclose($fp);
                    return false;
                }
            }
        }
        fclose($fp);

        if (strlen($Inserts) > 0) {
            $Sql = "insert $PxTablename values $Inserts";
            $this->query($Sql);
        }
        unset($this->Data['CurrentLoadPosition']);
        unset($this->Data['CurrentStepMessage']);
        return true;
    }

    /**
     *
     *
     * @param bool $Save
     * @return bool|mixed|string
     * @throws Exception
     */
    public function loadTableType($Save = true) {
        $Result = val('LoadTableType', $this->Data, false);

        if (is_string($Result)) {
            return $Result;
        }

        // Create a table to test loading.
        $St = Gdn::structure();
        $St->table(self::TABLE_PREFIX.'Test')->column('ID', 'int')->set(true, true);

        // Create a test file to load.
        if (!file_exists(PATH_UPLOADS.'/import')) {
            mkdir(PATH_UPLOADS.'/import');
        }

        $TestPath = PATH_UPLOADS.'/import/test.txt';
        $TestValue = 123;
        $TestContents = 'ID'.self::NEWLINE.$TestValue.self::NEWLINE;
        file_put_contents($TestPath, $TestContents, LOCK_EX);

        // Try LoadTableOnSameServer.
        try {
            $this->_LoadTableOnSameServer('Test', $TestPath);
            $Value = $this->SQL->get(self::TABLE_PREFIX.'Test')->value('ID');
            if ($Value == $TestValue) {
                $Result = 'LoadTableOnSameServer';
            }
        } catch (Exception $Ex) {
            $Result = false;
        }

        // Try LoadTableLocalInfile.
        if (!$Result) {
            try {
                $this->_LoadTableLocalInfile('Test', $TestPath);
                $Value = $this->SQL->get(self::TABLE_PREFIX.'Test')->value('ID');
                if ($Value == $TestValue) {
                    $Result = 'LoadTableLocalInfile';
                }
            } catch (Exception $Ex) {
                $Result = false;
            }
        }

        // If those two didn't work then default to LoadTableWithInsert.
        if (!$Result) {
            $Result = 'LoadTableWithInsert';
        }

        // Cleanup.
        @unlink($TestPath);
        $St->table(self::TABLE_PREFIX.'Test')->Drop();

        if ($Save) {
            $this->Data['LoadTableType'] = $Result;
        }
        return $Result;
    }

    /**
     *
     *
     * @return bool
     */
    public function localInfileSupported() {
        $Sql = "show variables like 'local_infile'";
        $Data = $this->query($Sql)->resultArray();
        if (strcasecmp(GetValueR('0.Value', $Data), 'ON') == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @param string $Overwrite
     * @param string $Email
     * @param string $Password
     * @return mixed
     */
    public function overwrite($Overwrite = '', $Email = '', $Password = '') {
        if ($Overwrite == '') {
            return val('Overwrite', $this->Data);
        }
        $this->Data['Overwrite'] = $Overwrite;
        if (strcasecmp($Overwrite, 'Overwrite') == 0) {
            $this->Data['OverwriteEmail'] = $Email;
            $this->Data['OverwritePassword'] = $Password;
        } else {
            if (isset($this->Data['OverwriteEmail'])) {
                unset($this->Data['OverwriteEmail']);
            }
            if (isset($this->Data['OverwritePassword'])) {
                unset($this->Data['OverwritePassword']);
            }
        }
    }

    /**
     *
     *
     * @param $Line
     * @return array
     */
    public function parseInfoLine($Line) {
        $Info = explode(',', $Line);
        $Result = array();
        foreach ($Info as $Item) {
            $PropVal = explode(':', $Item);
            if (array_key_exists(1, $PropVal)) {
                $Result[trim($PropVal[0])] = trim($PropVal[1]);
            } else {
                $Result[trim($Item)] = '';
            }
        }

        return $Result;
    }


    /**
     * Process the import tables from the database.
     */
    public function processImportDb() {
        // Grab a list of all of the tables.
        $TableNames = $this->SQL->FetchTables(':_z%');
        if (count($TableNames) == 0) {
            throw new Gdn_UserException('Your database does not contain any import tables.');
        }

        $Tables = array();
        foreach ($TableNames as $TableName) {
            $TableName = stringBeginsWith($TableName, $this->Database->DatabasePrefix, true, true);
            $DestTableName = stringBeginsWith($TableName, 'z', true, true);
            $TableInfo = array('Table' => $DestTableName);

            $ColumnInfos = $this->SQL->FetchTableSchema($TableName);
            $Columns = array();
            foreach ($ColumnInfos as $ColumnInfo) {
                $Columns[GetValue('Name', $ColumnInfo)] = Gdn::structure()->ColumnTypeString($ColumnInfo);
            }
            $TableInfo['Columns'] = $Columns;
            $Tables[$DestTableName] = $TableInfo;
        }
        $this->Data['Tables'] = $Tables;
        return true;
    }

    /**
     *
     * @return bool
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function processImportFile() {
        // This one step can take a while so give it more time.
        set_time_limit(60 * 5);

        $Path = $this->ImportPath;
        $BasePath = dirname($Path).DS.'import';
        $Tables = array();

        if (!is_readable($Path)) {
            throw new Gdn_UserException(t('The input file is not readable.', 'The input file is not readable.  Please check permissions and try again.'));
        }
        if (!is_writeable($BasePath)) {
            throw new Gdn_UserException(sprintf(t('Data file directory (%s) is not writable.'), $BasePath));
        }

        // Open the import file.
        ini_set('auto_detect_line_endings', true);
        $fpin = gzopen($Path, 'rb');
        $fpout = null;

        // Make sure it has the proper header.
        try {
            $Header = $this->GetImportHeader($fpin);
        } catch (Exception $Ex) {
            fclose($fpin);
            throw $Ex;
        }

        $RowCount = 0;
        $LineNumber = 0;
        while (($Line = fgets($fpin)) !== false) {
            $LineNumber++;

            if ($Line == "\n") {
                if ($fpout) {
                    // We are in a table so close it off.
                    fclose($fpout);
                    $fpout = 0;
                }
            } elseif ($fpout) {
                // We are in a table so dump the line.
                fputs($fpout, $Line);
            } elseif (substr_compare(self::COMMENT, $Line, 0, strlen(self::COMMENT)) == 0) {
                // This is a comment line so do nothing.
            } else {
                // This is the start of a table.
                $TableInfo = $this->ParseInfoLine($Line);

                if (!array_key_exists('Table', $TableInfo)) {
                    throw new Gdn_UserException(sprintf(t('Could not parse import file. The problem is near line %s.'), $LineNumber));
                }

                $Table = $TableInfo['Table'];
                $tableSanitized = preg_replace('#[^A-Z0-9\-_]#i', '_', $Table);
                $Path = $BasePath.DS.$tableSanitized.'.txt';
                $fpout = fopen($Path, 'wb');

                $TableInfo['Path'] = $Path;
                unset($TableInfo['Table']);

                // Get the column headers from the next line.
                if (($Line = fgets($fpin)) !== false) {
                    $LineNumber++;

                    // Strip \r out of line.
                    $Line = str_replace(array("\r\n", "\r"), array("\n", "\n"), $Line);
                    fwrite($fpout, $Line);
                    $Columns = $this->ParseInfoLine($Line);
                    $TableInfo['Columns'] = $Columns;

                    $Tables[$Table] = $TableInfo;
                }
            }
        }
        gzclose($fpin);
        if ($fpout) {
            fclose($fpout);
        }

        if (count($Tables) == 0) {
            throw new Gdn_UserException(t('The import file does not contain any data.'));
        }

        $this->Data['Tables'] = $Tables;

        return true;
    }

    /**
     * Run the step in the import.
     *
     * @param int $Step the step to run.
     * @return mixed Whether the step succeeded or an array of information.
     */
    public function runStep($Step = 1) {
        $Started = $this->stat('Started');
        if ($Started === null) {
            $this->stat('Started', microtime(true), 'time');
        }

        $Steps = $this->Steps();
        $LastStep = end(array_keys($Steps));
        if (!isset($Steps[$Step]) || $Step > $LastStep) {
            return 'COMPLETE';
        }
        if (!$this->Timer) {
            $NewTimer = true;
            $this->Timer = new Gdn_Timer();
            $this->Timer->start('');
        }

        // Run a standard step every time.
        if (isset($Steps[0])) {
            call_user_func(array($this, $Steps[0]));
        }

        $Method = $Steps[$Step];
        $Result = call_user_func(array($this, $Method));

        if ($this->generateSQL()) {
            $this->saveSQL($Method);
        }

        $ElapsedTime = $this->Timer->elapsedTime();
        $this->stat('Time Spent on Import', $ElapsedTime, 'add');

        if (isset($NewTimer)) {
            $this->Timer->finish('');
        }

        if ($Result && !array_key_exists($Step + 1, $this->steps())) {
            $this->stat('Finished', microtime(true), 'time');
        }

        return $Result;
    }

    /**
     * Run a query, replacing database prefixes.
     *
     * @param string $Sql The sql to execute.
     *  - :_z will be replaced by the import prefix.
     *  - :_ will be replaced by the database prefix.
     * @param array $Parameters PDO parameters to pass to the query.
     * @return Gdn_DataSet
     */
    public function query($Sql, $Parameters = null) {
        $Db = Gdn::database();

        // Replace db prefixes.
        $Sql = str_replace(array(':_z', ':_'), array($Db->DatabasePrefix.self::TABLE_PREFIX, $Db->DatabasePrefix), $Sql);

        // Figure out the type of the type of the query.
        if (stringBeginsWith($Sql, 'select')) {
            $Type = 'select';
        } elseif (stringBeginsWith($Sql, 'truncate'))
            $Type = 'truncate';
        elseif (stringBeginsWith($Sql, 'insert'))
            $Type = 'insert';
        elseif (stringBeginsWith($Sql, 'update'))
            $Type = 'update';
        elseif (stringBeginsWith($Sql, 'delete'))
            $Type = 'delete';
        else {
            $Type = 'select';
        }

        // Execute the query.
        if (is_array($Parameters)) {
            $this->SQL->namedParameters($Parameters);
        }

        $Result = $this->SQL->query($Sql, $Type);

        //$this->Timer->Split('Sql: '. str_replace("\n", "\n     ", $Sql));

        return $Result;
    }

    /**
     *
     */
    public function saveState() {
        saveToConfig(array(
            'Garden.Import.CurrentStep' => $this->CurrentStep,
            'Garden.Import.CurrentStepData' => $this->Data,
            'Garden.Import.ImportPath' => $this->ImportPath));
    }

    /**
     *
     *
     * @param $CurrentStep
     */
    public function saveSQL($CurrentStep) {
        $SQLPath = $this->data('SQLPath');

        $Queries = $this->Database->CapturedSql;
        foreach ($Queries as $Index => $Sql) {
            $Queries[$Index] = rtrim($Sql, ';').';';
        }
        $Queries = "\n\n/* $CurrentStep */\n\n".implode("\n\n", $Queries);


        file_put_contents(PATH_UPLOADS.'/'.$SQLPath, $Queries, FILE_APPEND | LOCK_EX);
    }

    /**
     * Set the category permissions based on the permission table.
     */
    public function setCategoryPermissionIDs() {
        // First build a list of category
        $Permissions = $this->SQL->getWhere('Permission', array('JunctionColumn' => 'PermissionCategoryID', 'JunctionID >' => 0))->resultArray();
        $CategoryIDs = array();
        foreach ($Permissions as $Row) {
            $CategoryIDs[$Row['JunctionID']] = $Row['JunctionID'];
        }

        // Update all of the child categories.
        $Root = CategoryModel::categories(-1);
        $this->_setCategoryPermissionIDs($Root, $Root['CategoryID'], $CategoryIDs);
    }

    /**
     *
     *
     * @param $Category
     * @param $PermissionID
     * @param $IDs
     */
    protected function _setCategoryPermissionIDs($Category, $PermissionID, $IDs) {
        static $CategoryModel;
        if (!isset($CategoryModel)) {
            $CategoryModel = new CategoryModel();
        }

        $CategoryID = $Category['CategoryID'];
        if (isset($IDs[$CategoryID])) {
            $PermissionID = $CategoryID;
        }

        if ($Category['PermissionCategoryID'] != $PermissionID) {
            $CategoryModel->setField($CategoryID, 'PermissionCategoryID', $PermissionID);
        }

        $ChildIDs = val('ChildIDs', $Category, array());
        foreach ($ChildIDs as $ChildID) {
            $ChildCategory = CategoryModel::categories($ChildID);
            if ($ChildCategory) {
                $this->_setCategoryPermissionIDs($ChildCategory, $PermissionID, $IDs);
            }
        }
    }

    /**
     *
     */
    public function setRoleDefaults() {
        if (!$this->importExists('Role', 'RoleID')) {
            return;
        }

        $Data = $this->SQL->get('zRole')->resultArray();

        $RoleDefaults = array(
            'Garden.Registration.ConfirmEmail' => false
        );
        $RoleTypes = array();

        foreach ($Data as $Row) {
            if ($this->importExists('Role', '_Default')) {
                $Name = $Row['_Default'];
            } else {
                $Name = val('Name', $Row);
            }

            $RoleID = $Row['RoleID'];

            if (preg_match('`anonymous`', $Name)) {
                $Name = 'guest';
            } elseif (preg_match('`admin`', $Name))
                $Name = 'administrator';

            switch (strtolower($Name)) {
                case 'email':
                case 'confirm email':
                case 'users awaiting email confirmation':
                case 'pending':
                    $RoleTypes[$RoleID] = RoleModel::TYPE_UNCONFIRMED;
                    $RoleDefaults['Garden.Registration.ConfirmEmail'] = true;
                    break;
                case 'member':
                case 'members':
                case 'registered':
                case 'registered users':
                    $RoleTypes[$RoleID] = RoleModel::TYPE_MEMBER;
                    break;
                case 'guest':
                case 'guests':
                case 'unauthenticated':
                case 'unregistered':
                case 'unregistered / not logged in':
                    $RoleTypes[$RoleID] = RoleModel::TYPE_GUEST;
                    break;
                case 'applicant':
                case 'applicants':
                    $RoleTypes[$RoleID] = RoleModel::TYPE_APPLICANT;
                    break;
            }
        }
        saveToConfig($RoleDefaults);
        $roleModel = new RoleModel();
        foreach ($RoleTypes as $RoleID => $Type) {
            $roleModel->setField($RoleID, 'Type', $Type);
        }
    }

    /**
     *
     *
     * @param $Key
     * @param null $Value
     * @param string $Op
     * @return mixed
     */
    public function stat($Key, $Value = null, $Op = 'set') {
        if (!isset($this->Data['Stats'])) {
            $this->Data['Stats'] = array();
        }

        $Stats =& $this->Data['Stats'];

        if ($Value !== null) {
            switch (strtolower($Op)) {
                case 'add':
                    $Value += val($Key, $Stats, 0);
                    $Stats[$Key] = $Value;
                    break;
                case 'set':
                    $Stats[$Key] = $Value;
                    break;
                case 'time':
                    $Stats[$Key] = date('Y-m-d H:i:s', $Value);
            }
            return $Stats[$Key];
        } else {
            return val($Key, $Stats, null);
        }
    }

    /**
     *
     *
     * @return array
     */
    public function steps() {
        if (strcasecmp($this->overwrite(), 'Overwrite') == 0) {
            if ($this->isDbSource()) {
                return $this->_OverwriteStepsDb;
            } else {
                return $this->_OverwriteSteps;
            }
        } else {
            return $this->_MergeSteps;
        }
    }

    /**
     *
     *
     * @param string $TableName
     * @return mixed
     */
    public function &tables($TableName = '') {
        if ($TableName) {
            return $this->Data['Tables'][$TableName];
        } else {
            return $this->Data['Tables'];
        }
    }

    /**
     *
     *
     * @return bool
     */
    public function updateCounts() {
        // This option could take a while so set the timeout.
        set_time_limit(60 * 5);

        // Define the necessary SQL.
        $Sqls = array();

        if (!$this->importExists('Discussion', 'LastCommentID')) {
            $Sqls['Discussion.LastCommentID'] = $this->GetCountSQL('max', 'Discussion', 'Comment');
        }

        if (!$this->importExists('Discussion', 'DateLastComment')) {
            $Sqls['Discussion.DateLastComment'] = "update :_Discussion d
         left join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.DateLastComment = coalesce(c.DateInserted, d.DateInserted)";
        }

        if (!$this->importExists('Discussion', 'LastCommentUserID')) {
            $Sqls['Discussion.LastCommentUseID'] = "update :_Discussion d
         join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.LastCommentUserID = c.InsertUserID";
        }

        if (!$this->importExists('Discussion', 'Body')) {
            // Update the body of the discussion if it isn't there.
            if (!$this->importExists('Discussion', 'FirstCommentID')) {
                $Sqls['Discussion.FirstCommentID'] = $this->GetCountSQL('min', 'Discussion', 'Comment', 'FirstCommentID', 'CommentID');
            }

            $Sqls['Discussion.Body'] = "update :_Discussion d
         join :_Comment c
            on d.FirstCommentID = c.CommentID
         set d.Body = c.Body, d.Format = c.Format";

            if ($this->importExists('Media') && Gdn::structure()->TableExists('Media')) {
                // Comment Media has to go onto the discussion.
                $Sqls['Media.Foreign'] = "update :_Media m
            join :_Discussion d
               on d.FirstCommentID = m.ForeignID and m.ForeignTable = 'comment'
            set m.ForeignID = d.DiscussionID, m.ForeignTable = 'discussion'";
            }

            $Sqls['Comment.FirstComment.Delete'] = "delete c.*
         from :_Comment c
         inner join :_Discussion d
           on d.FirstCommentID = c.CommentID";
        }

        if (!$this->importExists('Discussion', 'CountComments')) {
            $Sqls['Discussion.CountComments'] = $this->GetCountSQL('count', 'Discussion', 'Comment');
        }

        if ($this->importExists('UserDiscussion') && !$this->importExists('UserDiscussion', 'CountComments') && $this->importExists('UserDiscussion', 'DateLastViewed')) {
            $Sqls['UserDiscussuion.CountComments'] = "update :_UserDiscussion ud
         set CountComments = (
           select count(c.CommentID)
           from :_Comment c
           where c.DiscussionID = ud.DiscussionID
             and c.DateInserted <= ud.DateLastViewed)";

        }

        if ($this->importExists('Tag') && $this->importExists('TagDiscussion')) {
            $Sqls['Tag.CoundDiscussions'] = $this->GetCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
        }

        if ($this->importExists('Poll') && Gdn::structure()->TableExists('Poll')) {
            $Sqls['PollOption.CountVotes'] = $this->GetCountSQL('count', 'PollOption', 'PollVote', 'CountVotes', 'PollOptionID');

            $Sqls['Poll.CountOptions'] = $this->GetCountSQL('count', 'Poll', 'PollOption', 'CountOptions', 'PollID');
            $Sqls['Poll.CountVotes'] = $this->GetCountSQL('sum', 'Poll', 'PollOption', 'CountVotes', 'CountVotes', 'PollID');
        }

        if ($this->importExists('Activity', 'ActivityType')) {
            $Sqls['Activity.ActivityTypeID'] = "
            update :_Activity a
            join :_ActivityType t
               on a.ActivityType = t.Name
            set a.ActivityTypeID = t.ActivityTypeID";
        }

        if ($this->importExists('Tag') && $this->importExists('TagDiscussion')) {
            $Sqls['Tag.CoundDiscussions'] = $this->GetCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
        }

        $Sqls['Category.CountDiscussions'] = $this->GetCountSQL('count', 'Category', 'Discussion');
        $Sqls['Category.CountComments'] = $this->GetCountSQL('sum', 'Category', 'Discussion', 'CountComments', 'CountComments');
        if (!$this->importExists('Category', 'PermissionCategoryID')) {
            $Sqls['Category.PermissionCategoryID'] = "update :_Category set PermissionCategoryID = -1";
        }

        if ($this->importExists('Conversation') && $this->importExists('ConversationMessage')) {
            $Sqls['Conversation.FirstMessageID'] = $this->GetCountSQL('min', 'Conversation', 'ConversationMessage', 'FirstMessageID', 'MessageID');

            if (!$this->importExists('Conversation', 'CountMessages')) {
                $Sqls['Conversation.CountMessages'] = $this->GetCountSQL('count', 'Conversation', 'ConversationMessage', 'CountMessages', 'MessageID');
            }
            if (!$this->importExists('Conversation', 'LastMessageID')) {
                $Sqls['Conversation.LastMessageID'] = $this->GetCountSQL('max', 'Conversation', 'ConversationMessage', 'LastMessageID', 'MessageID');
            }

            if (!$this->importExists('Conversation', 'DateUpdated')) {
                $Sqls['Converstation.DateUpdated'] = "update :_Conversation c join :_ConversationMessage m on c.LastMessageID = m.MessageID set c.DateUpdated = m.DateInserted";
            }

            if ($this->importExists('UserConversation')) {
                if (!$this->importExists('UserConversation', 'LastMessageID')) {
                    if ($this->importExists('UserConversation', 'DateLastViewed')) {
                        // Get the value from the DateLastViewed.
                        $Sqls['UserConversation.LastMessageID'] =
                            "update :_UserConversation uc
                     set LastMessageID = (
                       select max(MessageID)
                       from :_ConversationMessage m
                       where m.ConversationID = uc.ConversationID
                         and m.DateInserted >= uc.DateLastViewed)";
                    } else {
                        // Get the value from the conversation.
                        // In this case just mark all of the messages read.
                        $Sqls['UserConversation.LastMessageID'] =
                            "update :_UserConversation uc
                     join :_Conversation c
                       on c.ConversationID = uc.ConversationID
                     set uc.CountReadMessages = c.CountMessages,
                       uc.LastMessageID = c.LastMessageID";
                    }
                } elseif (!$this->importExists('UserConversation', 'DateLastViewed')) {
                    // We have the last message so grab the date from that.
                    $Sqls['UserConversation.DateLastViewed'] =
                        "update :_UserConversation uc
                     join :_ConversationMessage m
                       on m.ConversationID = uc.ConversationID
                         and m.MessageID = uc.LastMessageID
                     set uc.DateLastViewed = m.DateInserted";
                }
            }
        }

        // User counts.
        if (!$this->importExists('User', 'DateFirstVisit')) {
            $Sqls['User.DateFirstVisit'] = 'update :_User set DateFirstVisit = DateInserted';
        }
        if (!$this->importExists('User', 'CountDiscussions')) {
            $Sqls['User.CountDiscussions'] = $this->GetCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID');
        }
        if (!$this->importExists('User', 'CountComments')) {
            $Sqls['User.CountComments'] = $this->GetCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID');
        }
        if (!$this->importExists('User', 'CountBookmarks')) {
            $Sqls['User.CountBookmarks'] = "update :_User u
            set CountBookmarks = (
               select count(ud.DiscussionID)
               from :_UserDiscussion ud
               where ud.Bookmarked = 1
                  and ud.UserID = u.UserID
            )";
        }
//      if (!$this->importExists('User', 'CountUnreadConversations')) {
//         $Sqls['User.CountUnreadConversations'] =
//            'update :_User u
//            set u.CountUnreadConversations = (
//              select count(c.ConversationID)
//              from :_Conversation c
//              inner join :_UserConversation uc
//                on c.ConversationID = uc.ConversationID
//              where uc.UserID = u.UserID
//                and uc.CountReadMessages < c.CountMessages
//            )';
//      }

        // The updates start here.
        $CurrentSubstep = val('CurrentSubstep', $this->Data, 0);

//      $Sqls2 = array();
//      $i = 1;
//      foreach ($Sqls as $Name => $Sql) {
//         $Sqls2[] = "/* $i. $Name */\n"
//            .str_replace(':_', $this->Database->DatabasePrefix, $Sql)
//            .";\n";
//         $i++;
//      }
//      throw new Exception(implode("\n", $Sqls2));

        // Execute the SQL.
        $Keys = array_keys($Sqls);
        for ($i = $CurrentSubstep; $i < count($Keys); $i++) {
            $this->Data['CurrentStepMessage'] = sprintf(t('%s of %s'), $CurrentSubstep + 1, count($Keys));
            $Sql = $Sqls[$Keys[$i]];
            $this->query($Sql);
            if ($this->Timer->ElapsedTime() > $this->MaxStepTime) {
                $this->Data['CurrentSubstep'] = $i + 1;
                return false;
            }
        }
        if (isset($this->Data['CurrentSubstep'])) {
            unset($this->Data['CurrentSubstep']);
        }

        $this->Data['CurrentStepMessage'] = '';

        // Update the url codes of categories.
        if (!$this->importExists('Category', 'UrlCode')) {
            $Categories = CategoryModel::categories();
            $TakenCodes = array();

            foreach ($Categories as $Category) {
                $UrlCode = urldecode(Gdn_Format::url($Category['Name']));
                if (strlen($UrlCode) > 50) {
                    $UrlCode = 'c'.$Category['CategoryID'];
                } elseif (is_numeric($UrlCode)) {
                    $UrlCode = 'c'.$UrlCode;
                }

                if (in_array($UrlCode, $TakenCodes)) {
                    $ParentCategory = CategoryModel::categories($Category['ParentCategoryID']);
                    if ($ParentCategory && $ParentCategory['CategoryID'] != -1) {
                        $UrlCode = Gdn_Format::url($ParentCategory['Name']).'-'.$UrlCode;
                    }
                    if (in_array($UrlCode, $TakenCodes)) {
                        $UrlCode = $Category['CategoryID'];
                    }
                }

                $TakenCodes[] = $UrlCode;
                Gdn::sql()->put(
                    'Category',
                    array('UrlCode' => $UrlCode),
                    array('CategoryID' => $Category['CategoryID'])
                );
            }
        }
        // Rebuild the category tree.
        $CategoryModel = new CategoryModel();
        $CategoryModel->RebuildTree();
        $this->SetCategoryPermissionIDs();

        return true;
    }

    /**
     * Verify imported data.
     */
    public function verifyImport() {
        // When was the latest discussion posted?
        $LatestDiscussion = $this->SQL->select('DateInserted', 'max', 'LatestDiscussion')->from('Discussion')->get();
        if ($LatestDiscussion->count()) {
            $this->stat(
                'Last Discussion',
                $LatestDiscussion->value('LatestDiscussion')
            );
        } else {
            $this->stat(
                'Last Discussion',
                '-'
            );
        }

        // Any discussions without a user associated with them?
        $this->stat(
            'Orphaned Discussions',
            $this->SQL->getCount('Discussion', array('InsertUserID' => '0'))
        );

        // When was the latest comment posted?
        $LatestComment = $this->SQL->select('DateInserted', 'max', 'LatestComment')->from('Comment')->get();
        if ($LatestComment->count()) {
            $this->stat(
                'Last Comment',
                $LatestComment->value('LatestComment')
            );
        } else {
            $this->stat(
                'Last Comment',
                '-'
            );
        }

        // Any comments without a user associated with them?
        $this->stat(
            'Orphaned Comments',
            $this->SQL->getCount('Comment', array('InsertUserID' => '0'))
        );

        // Any users without roles?
        $UsersWithoutRoles = $this->SQL
            ->from('User u')
            ->leftJoin('UserRole ur', 'u.UserID = ur.UserID')
            ->leftJoin('Role r', 'ur.RoleID = r.RoleID')
            ->where('r.Name', null)
            ->get()
            ->count();
        $this->stat(
            'Users Without a Valid Role',
            $UsersWithoutRoles
        );

        return true;
    }
}
