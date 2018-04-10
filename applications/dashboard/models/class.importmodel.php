<?php
/**
 * Object for importing files created with VanillaPorter.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public $Data = [];

    /** @var string Error to return. */
    public $ErrorType = '';

    /** @var string File location. */
    public $ImportPath = '';

    /** @var int Max seconds to run a single step. */
    public $MaxStepTime = 1;

    /** @var array Method names in order they are called during a merge. */
    protected $_MergeSteps = [
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
    ];

    /** @var array Method names in order they are called during a clean slate import from file. */
    protected $_OverwriteSteps = [
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
    ];

    /** @var array Steps for importing from database. */
    protected $_OverwriteStepsDb = [
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
    ];

    /** @var Gdn_Timer Used for timing various long running methods to break them up into pieces. */
    public $Timer = null;

    /**
     * Set the import path.
     */
    public function __construct($importPath = '') {
        $this->ImportPath = $importPath;
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
        $header = $this->getImportHeader();
        $porterVersion = val('Vanilla Export', $header, t('unknown'));
        $sourceData = val('Source', $header, t('unknown'));
        $story = sprintf(t('Vanilla Export: %s, Source: %s'), $porterVersion, $sourceData);

        $activityModel = new ActivityModel();
        $activityModel->add(Gdn::session()->UserID, 'Import', $story);
        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function assignUserIDs() {
        // Assign user IDs of email matches.
        $sql = "update :_zUser i
         join :_User u
           on i.Email = u.Email
         set i._NewID = u.UserID, i._Action = 'Update'";
        $this->query($sql);

        // Assign user IDs of name matches.
        $sql = "update :_zUser i
         join :_User u
            on i.Name = u.Name
         left join :_zUser i2
            on i2._NewID = u.UserID /* make sure no duplicates */
         set i._NewID = u.UserID, i._Action = 'Update'
         where i._NewID is null and i2.UserID is null";
        $this->query($sql);

        // Get the max UserID so we can increment new users.
        $maxID = $this->query('select max(UserID) as MaxID from :_User')->value('MaxID', 0);
        $minID = $this->query('select min(UserID) as MinID from :_zUser where _NewID is null')->value('MinID', null);

        if (is_null($minID)) {
            //$this->Timer->split('No more IDs to update');
            // No more IDs to update.
            return true;
        }

        $iDInc = $maxID - $minID + self::ID_PADDING;

        // Update the users to insert.
        $sql = "update :_zUser i
         left join :_User u
            on u.Name = i.Name /* make sure no duplicates */
         set i._NewID = i.UserID + $iDInc, i._Action = 'Insert'
         where i._NewID is null
            and u.UserID is null";
        $this->query($sql);

        // There still might be users that have overlapping usernames which must be changed.
        // Append a random suffix to the new username.
        $sql = "update :_zUser i
         set i.Name = concat(i.Name, convert(floor(1000 + rand() * 8999), char)), i._NewID = i.UserID + $iDInc, i._Action = 'Insert'
         where i._NewID is null";
        $this->query($sql);

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
     * @param $tableName
     * @param null $primaryKey
     * @param null $secondaryKey
     * @return bool|void
     */
    protected function _assignIDs($tableName, $primaryKey = null, $secondaryKey = null) {
        if (!array_key_exists($tableName, $this->tables())) {
            return;
        }

        if (!$primaryKey) {
            $primaryKey = $tableName.'ID';
        }

        // Assign existing IDs.
        if ($secondaryKey) {
            $sql = "update :_z$tableName i
            join :_$tableName t
              on t.$secondaryKey = i.$secondaryKey
            set i._NewID = t.$primaryKey, i._Action = 'Update'";
            $this->query($sql);
        }

        // Get new IDs.
        $maxID = $this->query("select max($primaryKey) as MaxID from :_$tableName")->value('MaxID', 0);
        $minID = $this->query("select min($primaryKey) as MinID from :_z$tableName where _NewID is null")->value('MinID', null);

        if (is_null($minID)) {
            //$this->Timer->split('No more IDs to update');
            // No more IDs to update.
            return true;
        }
        if ($maxID == 0) {
            $iDInc = 0;
        } else {
            $iDInc = $maxID - $minID + self::ID_PADDING;
        }

        $sql = "update :_z$tableName i
         set i._NewID = i.$primaryKey + $iDInc, i._Action = 'Insert'
         where i._NewID is null";
        $this->query($sql);
    }

    /**
     *
     *
     * @return bool
     */
    public function authenticateAdminUser() {
        $overwriteEmail = val('OverwriteEmail', $this->Data);
        $overwritePassword = val('OverwritePassword', $this->Data);

        $data = Gdn::sql()->getWhere('zUser', ['Email' => $overwriteEmail]);
        if ($data->numRows() == 0) {
            $result = false;
        } else {
            $result = true;
        }
        if (!$result) {
            $this->Validation->addValidationResult('Email', t('ErrorCredentials'));
            $this->ErrorType = 'Credentials';
        }
        return $result;

    }

    /**
     *
     *
     * @return bool
     */
    public function customFinalization() {
        $this->setRoleDefaults();
        PermissionModel::resetAllRoles();
        // Remove invalid relation between non existing users/roles
        RoleModel::cleanUserRoles();

        $imp = $this->getCustomImportModel();
        if ($imp !== null) {
            $imp->afterImport();
        }

        return true;
    }

    /**
     *
     *
     * @param $key
     * @param null $value
     * @return mixed
     */
    public function data($key, $value = null) {
        if ($value === null) {
            return val($key, $this->Data);
        }
        $this->Data[$key] = $value;
    }

    /**
     *
     *
     * @return bool
     * @throws Gdn_UserException
     */
    public function defineTables() {
        $st = Gdn::structure();
        $destStructure = clone $st;

        $tables =& $this->tables();

        foreach ($tables as $table => $tableInfo) {
            $columns = $tableInfo['Columns'];
            if (!is_array($columns) || count($columns) == 0) {
                throw new Gdn_UserException(sprintf(t('The %s table is not in the correct format.', $table)));
            }


            $st->table(self::TABLE_PREFIX.$table);
            // Get the structure from the destination database to match types.
            try {
                $destStructure->reset()->get($table);
            } catch (Exception $ex) {
                // Trying to import into a non-existant table.
                $tables[$table]['Skip'] = true;
                continue;
            }
            //$DestColumns = $DestStructure->columns();
            $destModified = false;

            foreach ($columns as $name => $type) {
                if (!$name) {
                    throw new Gdn_UserException(sprintf(t('The %s table is not in the correct format.'), $table));
                }

                if ($destStructure->columnExists($name)) {
                    $structureType = $destStructure->columnTypeString($destStructure->columns($name));
                } elseif ($destStructure->columnExists($type)) {
                    // Fix the table definition.
                    unset($tables[$table]['Columns'][$name]);
                    $tables[$table]['Columns'][$type] = '';

                    $name = $type;
                    $structureType = $destStructure->columnTypeString($destStructure->columns($type));
                } elseif (!stringBeginsWith($name, '_')) {
                    $structureType = $type;

                    if (!$structureType) {
                        $structureType = 'varchar(255)';
                    }

                    // This is a new column so it needs to be added to the destination table too.
                    $destStructure->column($name, $structureType, null);
                    $destModified = true;
                } elseif ($type) {
                    $structureType = $type;
                } else {
                    $structureType = 'varchar(255)';
                }

                $st->column($name, $structureType, null);
            }
            // Add a new ID column.
            if (array_key_exists($table.'ID', $columns)) {
                $st
                    ->column('_NewID', $destStructure->columnTypeString($table.'ID'), null)
                    ->column('_Action', ['Insert', 'Update'], null);
            }

            try {
                if (!$this->isDbSource()) {
                    $st->set(true, true);
                } else {
                    $st->reset();
                }

                if ($destModified) {
                    $destStructure->set();
                }
            } catch (Exception $ex) {
                // Since these exceptions are likely caused by a faulty import file they should be considered user exceptions.
                throw new Gdn_UserException(sprintf(t('There was an error while trying to create the %s table (%s).'), $table, $ex->getMessage())); //, $Ex);
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
        $st = Gdn::structure();
        $destStructure = clone Gdn::structure();

        foreach ($this->tables() as $table => $tableInfo) {
            if (val('Skip', $tableInfo)) {
                continue;
            }

            $st->table(self::TABLE_PREFIX.$table);
            $columns = $tableInfo['Columns'];

            $destStructure->reset()->get($table);
            $destColumns = $destStructure->columns();

            // Check to index the primary key.
            $col = $table.'ID';
            if (array_key_exists($col, $columns)) {
                $st->column($col, $columns[$col] ? $columns[$col] : $destStructure->columnTypeString($col), null, 'index');
            }

            if ($table == 'User') {
                $st
                    ->column('Name', $destStructure->columnTypeString('Name'), null, 'index')
                    ->column('Email', $destStructure->columnTypeString('Email'), null, 'index')
                    ->column('_NewID', 'int', null, 'index');
            }

            if (count($st->columns()) > 0) {
                $st->set();
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

        $st = Gdn::structure();
        foreach (val('Tables', $this->Data, []) as $table => $tableInfo) {
            $path = val('Path', $tableInfo, '');
            if (file_exists($path)) {
                unlink($path);
            }

            // Drop the import table.
            $st->table("z$table")->drop();
        }

        // Delete the uploaded files.
        $uploadedFiles = val('UploadedFiles', $this->Data, []);
        foreach ($uploadedFiles as $path => $name) {
            safeUnlink($path);
        }
    }

    /**
     * Remove the data from the appropriate tables when we are overwriting the forum.
     */
    public function deleteOverwriteTables() {
        $tables = ['Activity', 'Category', 'Comment', 'Conversation', 'ConversationMessage',
            'Discussion', 'Draft', 'Invitation', 'Media', 'Message', 'Photo', 'Permission', 'Rank', 'Poll', 'PollOption', 'PollVote', 'Role', 'UserAuthentication',
            'UserComment', 'UserConversation', 'UserDiscussion', 'UserMeta', 'UserRole'];

        // Execute the SQL.
        $currentSubstep = val('CurrentSubstep', $this->Data, 0);
        for ($i = $currentSubstep; $i < count($tables); $i++) {
            $table = $tables[$i];

            // Make sure the table exists.
            $exists = Gdn::structure()->table($table)->tableExists();
            Gdn::structure()->reset();
            if (!$exists) {
                continue;
            }

            $this->Data['CurrentStepMessage'] = $table;

            if ($table == 'Permission') {
                $this->SQL->delete($table, ['RoleID <>' => 0]);
            } else {
                $this->SQL->truncate($table);
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
     * @param $post
     */
    public function fromPost($post) {
        if (isset($post['Overwrite'])) {
            $this->Data['Overwrite'] = $post['Overwrite'];
        }
        if (isset($post['Email'])) {
            $this->Data['OverwriteEmail'] = $post['Email'];
        }
        if (isset($post['GenerateSQL'])) {
            $this->Data['GenerateSQL'] = $post['GenerateSQL'];
        }
    }

    /**
     *
     *
     * @param null $value
     * @return mixed
     */
    public function generateSQL($value = null) {
        return $this->data('GenerateSQL', $value);
    }

    /**
     * Return SQL for updating a count.
     *
     * @param string $aggregate count, max, min, etc.
     * @param string $parentTable The name of the parent table.
     * @param string $childTable The name of the child table
     * @param string $parentColumnName
     * @param string $childColumnName
     * @param string $parentJoinColumn
     * @param string $childJoinColumn
     * @return Gdn_DataSet
     */
    public function getCountSQL(
        $aggregate,
        $parentTable,
        $childTable,
        $parentColumnName = '',
        $childColumnName = '',
        $parentJoinColumn = '',
        $childJoinColumn = ''
    ) {

        if (!$parentColumnName) {
            switch (strtolower($aggregate)) {
                case 'count':
                    $parentColumnName = "Count{$childTable}s";
                    break;
                case 'max':
                    $parentColumnName = "Last{$childTable}ID";
                    break;
                case 'min':
                    $parentColumnName = "First{$childTable}ID";
                    break;
                case 'sum':
                    $parentColumnName = "Sum{$childTable}s";
                    break;
            }
        }

        if (!$childColumnName) {
            $childColumnName = $childTable.'ID';
        }

        if (!$parentJoinColumn) {
            $parentJoinColumn = $parentTable.'ID';
        }
        if (!$childJoinColumn) {
            $childJoinColumn = $parentJoinColumn;
        }

        $result = "update :_$parentTable p
                  set p.$parentColumnName = (
                     select $aggregate(c.$childColumnName)
                     from :_$childTable c
                     where p.$parentJoinColumn = c.$childJoinColumn)";
        return $result;
    }

    /**
     * Get a custom import model based on the import's source.
     */
    public function getCustomImportModel() {
        $result = null;

        // Get import type name.
        $header = $this->getImportHeader();
        $source = str_replace(' ', '', val('Source', $header, ''));

        // Figure out if we have a custom import model for it.
        $sourceModelName = $source.'ImportModel';
        if (class_exists($sourceModelName)) {
            $result = new $sourceModelName();
            $result->ImportModel = $this;
        }

        return $result;
    }

    /**
     *
     *
     * @param $post
     */
    public function toPost(&$post) {
        $d = $this->Data;
        $post['Overwrite'] = val('Overwrite', $d, 'Overwrite');
        $post['Email'] = val('OverwriteEmail', $d, '');
        $post['GenerateSQL'] = val('GenerateSQL', $d, false);
    }

    /**
     *
     *
     * @param $fp
     * @param string $delim
     * @param string $quote
     * @param string $escape
     * @return array
     */
    public static function fGetCSV2($fp, $delim = ',', $quote = '"', $escape = "\\") {
        // Get the full line, considering escaped returns.
        $line = false;
        do {
            $s = fgets($fp);
            //echo "<fgets>$s</fgets><br/>\n";

            if ($s === false) {
                if ($line === false) {
                    return false;
                }
            }

            if ($line === false) {
                $line = $s;
            } else {
                $line .= $s;
            }
        } while (strlen($s) > 1 && substr($s, -2, 1) === $escape);

        $line = trim($line, "\n");
        //echo "<Line>$Line</Line><br />\n";

        $result = [];

        // Loop through the line and split on the delimiter.
        $strlen = strlen($line);
        $inEscape = false;
        $inQuote = false;
        $token = '';
        for ($i = 0; $i < $strlen; ++$i) {
            $c = $line[$i];

            if ($inEscape) {
                // Check for an escaped null.
                if ($c == 'N' && strlen($token) == 0) {
                    $token = null;
                } else {
                    $token .= $c;
                }
                $inEscape = false;
            } else {
                switch ($c) {
                    case $escape:
                        $inEscape = true;
                        break;
                    case $delim:
                        $result[] = $token;
                        $token = '';
                        break;
                    case $quote:
                        $inQuote = !$inQuote;
                        break;
                    default:
                        $token .= $c;
                        break;
                }
            }
        }
        $result[] = $token;

        return $result;
    }

    /**
     *
     *
     * @param null $fpin
     * @return array|mixed|string
     * @throws Gdn_UserException
     */
    public function getImportHeader($fpin = null) {
        $header = val('Header', $this->Data);
        if ($header) {
            return $header;
        }

        if (is_null($fpin)) {
            if (!$this->ImportPath || !file_exists($this->ImportPath)) {
                return [];
            }
            ini_set('auto_detect_line_endings', true);

            if (!is_readable($this->ImportPath)) {
                throw new Gdn_UserException(t('The input file is not readable.', 'The input file is not readable.  Please check permissions and try again.'));
            }

            $fpin = gzopen($this->ImportPath, 'rb');
            $fpopened = true;
        }

        $header = fgets($fpin);
        if (!$header || strlen($header) < 7 || substr_compare('Vanilla', $header, 0, 7) != 0) {
            if (isset($fpopened)) {
                fclose($fpin);
            }
            throw new Gdn_UserException(t('The import file is not in the correct format.'));
        }
        $header = $this->parseInfoLine($header);
        if (isset($fpopened)) {
            fclose($fpin);
        }
        $this->Data['Header'] = $header;
        return $header;
    }

    /**
     *
     *
     * @return mixed|string
     * @throws Gdn_UserException
     */
    public function getPasswordHashMethod() {
        $hashMethod = val('HashMethod', $this->getImportHeader());
        if ($hashMethod) {
            return $hashMethod;
        }

        $source = val('Source', $this->getImportHeader());
        if (!$source) {
            return 'Unknown';
        }
        if (substr_compare('Vanilla', $source, 0, 7, false) == 0) {
            return 'Vanilla';
        }
        if (substr_compare('vBulletin', $source, 0, 9, false) == 0) {
            return 'vBulletin';
        }
        if (substr_compare('phpBB', $source, 0, 5, false) == 0) {
            return 'phpBB';
        }
        return 'Unknown';
    }

    /** Checks to see of a table and/or column exists in the import data.
     *
     * @param string $table The name of the table to check for.
     * @param string $column
     * @return bool
     */
    public function importExists($table, $column = '') {
        if (!array_key_exists('Tables', $this->Data) || !array_key_exists($table, $this->Data['Tables'])) {
            return false;
        }
        if (!$column) {
            return true;
        }
        $tables = $this->Data['Tables'];

        $exists = valr("Tables.$table.Columns.$column", $this->Data, false);
        return $exists !== false;
    }

    /**
     *
     *
     * @return bool
     */
    public function initialize() {
        if ($this->generateSQL()) {
            $this->SQL->CaptureModifications = true;
            Gdn::structure()->CaptureOnly = true;
            $this->Database->CapturedSql = [];

            $sQLPath = $this->data('SQLPath');
            if (!$sQLPath) {
                $sQLPath = 'import/import_'.date('Y-m-d_His').'.sql';
                $this->data('SQLPath', $sQLPath);
            }
        } else {
            // Importing will overwrite our System user record.
            // Our CustomFinalization step (e.g. vbulletinimportmodel) needs this to be regenerated.
            removeFromConfig('Garden.SystemUserID');
        }

        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public function insertTables() {
        $insertedCount = 0;
        $timer = new Gdn_Timer();
        $timer->start();
        $tables =& $this->tables();
        foreach ($tables as $tableName => $tableInfo) {
            if (val('Inserted', $tableInfo) || val('Skip', $tableInfo)) {
                $insertedCount++;
            } else {
                $this->Data['CurrentStepMessage'] = sprintf(t('%s of %s'), $insertedCount, count($tables));

                if (strcasecmp($this->overwrite(), 'Overwrite') == 0) {
                    switch ($tableName) {
                        case 'Permission':
                            $this->insertPermissionTable();
                            break;
                        default:
                            $rowCount = $this->_InsertTable($tableName);
                            break;
                    }

                } else {
                    switch ($tableName) {
                        case 'Permission':
                            $this->insertPermissionTable();
                            break;
                        case 'UserDiscussion':
                            $sql = "insert ignore :_UserDiscussion ( UserID, DiscussionID, DateLastViewed, Bookmarked )
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
                            $this->query($sql);
                            break;
                        case 'UserMeta':
                            $sql = "insert ignore :_UserMeta ( UserID, Name, Value )
                           select zUserID._NewID, i.Name, max(i.Value) as Value
                           from :_zUserMeta i
                           left join :_zUser zUserID
                             on i.UserID = zUserID.UserID
                           left join :_UserMeta um
                             on zUserID._NewID = um.UserID and i.Name = um.Name
                           where um.UserID is null
                           group by zUserID._NewID, i.Name";
                            $this->query($sql);
                            break;
                        case 'UserRole':
                            $sql = "insert ignore :_UserRole ( UserID, RoleID )
                           select zUserID._NewID, zRoleID._NewID
                           from :_zUserRole i
                           left join :_zUser zUserID
                             on i.UserID = zUserID.UserID
                           left join :_zRole zRoleID
                             on i.RoleID = zRoleID.RoleID
                           left join :_UserRole ur
                              on zUserID._NewID = ur.UserID and zRoleID._NewID = ur.RoleID
                           where i.UserID <> 0 and ur.UserID is null";
                            $this->query($sql);
                            break;
                        default:
                            $rowCount = $this->_InsertTable($tableName);
                    }
                }

                $tables[$tableName]['Inserted'] = true;
                if (isset($rowCount)) {
                    $tables[$tableName]['RowCount'] = $rowCount;
                }
                $insertedCount++;
                // Make sure the loading isn't taking too long.
                if ($timer->elapsedTime() > $this->MaxStepTime) {
                    break;
                }
            }
        }

        $result = $insertedCount == count($this->tables());
        if ($result) {
            $this->Data['CurrentStepMessage'] = '';
        }
        return $result;
    }

    /**
     *
     *
     * @param $str
     * @return string
     */
    protected static function backTick($str) {
        return '`'.str_replace('`', '\`', $str).'`';
    }

    /**
     *
     *
     * @param $tableName
     * @param array $sets
     * @return bool|int|void
     */
    protected function _InsertTable($tableName, $sets = []) {
        if (!array_key_exists($tableName, $this->tables())) {
            return;
        }

        if (!Gdn::structure()->tableExists($tableName)) {
            return 0;
        }

        $tableInfo =& $this->tables($tableName);
        $columns = $tableInfo['Columns'];

        foreach ($columns as $key => $value) {
            if (stringBeginsWith($key, '_')) {
                unset($columns[$key]);
            }
        }

        // Build the column insert list.
        $insert = "insert ignore :_$tableName (\n  "
            .implode(",\n  ", array_map(['ImportModel', 'BackTick'], array_keys(array_merge($columns, $sets))))
            ."\n)";
        $from = "from :_z$tableName i";
        $where = '';

        // Build the select list for the insert.
        $select = [];
        foreach ($columns as $column => $x) {
            $bColumn = self::backTick($column);

            if (strcasecmp($this->overwrite(), 'Overwrite') == 0) {
                // The data goes in raw.
                $select[] = "i.$bColumn";
            } elseif ($column == $tableName.'ID') {
                // This is the primary key.
                $select[] = "i._NewID as $column";
                $where = "\nwhere i._Action = 'Insert'";
            } elseif (substr_compare($column, 'ID', -2, 2) == 0) {
                // This is an ID field. Check for a join.
                foreach ($this->tables() as $structureTableName => $tableInfo) {
                    $pK = $structureTableName.'ID';
                    if (strlen($column) >= strlen($pK) && substr_compare($column, $pK, -strlen($pK), strlen($pK)) == 0) {
                        // This table joins and must update it's ID.
                        $from .= "\nleft join :_z$structureTableName z$column\n  on i.$column = z$column.$pK";
                        $select[] = "z$column._NewID";
                    }
                }
            } else {
                // This is a straight columns insert.
                $select[] = "i.$bColumn";
            }
        }
        // Add the original table to prevent duplicates.
//      $PK = $TableName.'ID';
//      if(array_key_exists($PK, $Columns)) {
//      if(strcasecmp($this->overwrite(), 'Overwrite') == 0)
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
        foreach ($sets as $field => $value) {
            $select[] = Gdn::database()->connection()->quote($value).' as '.$field;
        }

        // Build the sql statement.
        $sql = $insert
            ."\nselect\n  ".implode(",\n  ", $select)
            ."\n".$from
            .$where;

        //$this->query($Sql);

        $rowCount = $this->query($sql);
        if ($rowCount > 0) {
            return (int)$rowCount;
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
//      $this->loadState();

        // Clear the permission table in case the step was only half done before.
        $this->SQL->delete('Permission', ['RoleID <>' => 0]);

        // Grab all of the permission columns.
        $pM = new PermissionModel();
        $globalColumns = array_filter($pM->permissionColumns());
        unset($globalColumns['PermissionID']);
        $junctionColumns = array_filter($pM->permissionColumns('Category', 'PermissionCategoryID'));
        unset($junctionColumns['PermissionID']);
        $junctionColumns = array_merge(['JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => -1], $junctionColumns);

        if ($this->importExists('Permission', 'JunctionTable')) {
            $columnSets = [array_merge($globalColumns, $junctionColumns)];
            $columnSets[0]['JunctionTable'] = null;
            $columnSets[0]['JunctionColumn'] = null;
            $columnSets[0]['JunctionID'] = null;
        } else {
            $columnSets = [$globalColumns, $junctionColumns];
        }

        $data = $this->SQL->get('zPermission')->resultArray();
        foreach ($data as $row) {
            $presets = array_map('trim', explode(',', val('_Permissions', $row)));

            foreach ($columnSets as $columnSet) {
                $set = [];
                $set['RoleID'] = $row['RoleID'];

                foreach ($presets as $preset) {
                    if (strpos($preset, '.') !== false) {
                        // This preset is a specific permission.

                        if (array_key_exists($preset, $columnSet)) {
                            $set["`$preset`"] = 1;
                        }
                        continue;
                    }
                    $preset = strtolower($preset);


                    foreach ($columnSet as $columnName => $default) {
                        if (isset($row[$columnName])) {
                            $value = $row[$columnName];
                        } elseif (strpos($columnName, '.') === false)
                            $value = $default;
                        elseif ($preset == 'all')
                            $value = 1;
                        elseif ($preset == 'view')
                            $value = stringEndsWith($columnName, 'View', true) && !in_array($columnName, ['Garden.Settings.View']);
                        elseif ($preset == $columnName)
                            $value = 1;
                        else {
                            $value = $default & 1;
                        }

                        $set["`$columnName`"] = $value;
                    }
                }
                $this->SQL->insert('Permission', $set);
                unset($set);
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
        $currentUser = $this->SQL->getWhere('User', ['UserID' => Gdn::session()->UserID])->firstRow(DATASET_TYPE_ARRAY);
        $currentPassword = $currentUser['Password'];
        $currentHashMethod = $currentUser['HashMethod'];
        $currentTransientKey = gdn::session()->transientKey();

        // Delete the current user table.
        $this->SQL->truncate('User');

        // Load the new user table.
        $userTableInfo =& $this->Data['Tables']['User'];
        if (!$this->importExists('User', 'HashMethod')) {
            $this->_InsertTable('User', ['HashMethod' => $this->getPasswordHashMethod()]);
        } else {
            $this->_InsertTable('User');
        }
        $userTableInfo['Inserted'] = true;

        $adminEmail = val('OverwriteEmail', $this->Data);
        $sqlArgs = [':Email' => $adminEmail];
        $sqlSet = '';

        $sqlArgs[':Password'] = $currentPassword;
        $sqlArgs[':HashMethod'] = $currentHashMethod;
        $sqlSet = ', Password = :Password, HashMethod = :HashMethod';

        // If doing a password reset, save out the new admin password:
        if (strcasecmp($this->getPasswordHashMethod(), 'reset') == 0) {
            if (!isset($sqlArgs[':Password'])) {
                $passwordHash = new Gdn_PasswordHash();
                $hash = $passwordHash->hashPassword(val('OverwritePassword', $this->Data));
                $sqlSet .= ', Password = :Password, HashMethod = :HashMethod';
                $sqlArgs[':Password'] = $hash;
                $sqlArgs[':HashMthod'] = 'Vanilla';
            }

            // Write it out.
            $this->query("update :_User set Admin = 1{$sqlSet} where Email = :Email", $sqlArgs);
        } else {
            // Set the admin user flag.
            $this->query("update :_User set Admin = 1{$sqlSet} where Email = :Email", $sqlArgs);
        }

        // Start the new session.
        $user = Gdn::userModel()->getByEmail(val('OverwriteEmail', $this->Data));
        if (!$user) {
            $user = Gdn::userModel()->getByUsername(val('OverwriteEmail', $this->Data));
        }

        Gdn::session()->start(val('UserID', $user), true);
        gdn::session()->transientKey($currentTransientKey);

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
        $userTableInfo =& $this->Data['Tables']['User'];
        $result = $this->loadTable('User', $userTableInfo['Path']);
        if ($result) {
            $userTableInfo['Loaded'] = true;
        }

        return $result;
    }

    /**
     *
     */
    public function loadState() {
        $this->CurrentStep = c('Garden.Import.CurrentStep', 0);
        $this->Data = c('Garden.Import.CurrentStepData', []);
        $this->ImportPath = c('Garden.Import.ImportPath', '');
    }

    /**
     *
     *
     * @return bool
     * @throws Exception
     */
    public function loadTables() {
        $loadedCount = 0;
        foreach ($this->Data['Tables'] as $table => $tableInfo) {
            if (val('Loaded', $tableInfo) || val('Skip', $tableInfo)) {
                $loadedCount++;
                continue;
            } else {
                $this->Data['CurrentStepMessage'] = $table;
                $loadResult = $this->loadTable($table, $tableInfo['Path']);
                if ($loadResult) {
                    $this->Data['Tables'][$table]['Loaded'] = true;
                    $loadedCount++;
                } else {
                    break;
                }
            }
            // Make sure the loading isn't taking too long.
            if ($this->Timer->elapsedTime() > $this->MaxStepTime) {
                break;
            }
        }
        $result = $loadedCount >= count($this->Data['Tables']);
        if ($result) {
            $this->Data['CurrentStepMessage'] = '';
        }
        return $result;
    }

    /**
     *
     *
     * @param $tablename
     * @param $path
     * @return bool
     * @throws Exception
     */
    public function loadTable($tablename, $path) {
        $type = $this->loadTableType();
        $result = true;

        switch ($type) {
            case 'LoadTableOnSameServer':
                $this->_LoadTableOnSameServer($tablename, $path);
                break;
            case 'LoadTableLocalInfile':
                $this->_LoadTableLocalInfile($tablename, $path);
                break;
            case 'LoadTableWithInsert':
                // This final option can be 15x slower than the other options.
                $result = $this->_LoadTableWithInsert($tablename, $path);
                break;
            default:
                throw new Exception("@Error, unknown LoadTableType: $type");
        }

        return $result;
    }

    /**
     *
     *
     * @param $tablename
     * @param $path
     */
    protected function _LoadTableOnSameServer($tablename, $path) {
        $tablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$tablename;
        $path = Gdn::database()->connection()->quote($path);

        Gdn::database()->query("truncate table $tablename;");

        $sql = "load data infile $path into table $tablename
         character set utf8mb4
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";
        $this->query($sql);
    }

    /**
     *
     *
     * @param $tablename
     * @param $path
     */
    protected function _LoadTableLocalInfile($tablename, $path) {
        if (extension_loaded('mysqli') === false) {
            throw new Exception('mysqli extension required for load data');
        }

        $tablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$tablename;
        $path = Gdn::database()->connection()->quote($path);

        Gdn::database()->query("truncate table $tablename;");

        $sql = "load data local infile $path into table $tablename
         character set utf8mb4
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";

        // We've got to use the mysqli_* functions because PDO doesn't support load data local infile well.
        $mysqli = new mysqli(c('Database.Host'), c('Database.User'), c('Database.Password'), c('Database.Name'), 128);
        $result = $mysqli->query($sql);
        if ($result === false) {
            $ex = new Exception($mysqli->error);
            $mysqli->close();
            throw new $ex;
        }
        $mysqli->close();
    }

    /**
     *
     * @param $tablename
     * @param $path
     * @return bool
     */
    protected function _LoadTableWithInsert($tablename, $path) {
        // This option could take a while so set the timeout.
        increaseMaxExecutionTime(60 * 10);
        $result = $this->loadTableInsert($tablename, $path);

        if ($result) {
            unset($this->Data['CurrentLoadPosition']);
            unset($this->Data['CurrentStepMessage']);
        }
        return $result;
    }

    /**
     *
     *
     * @param bool $save
     * @return bool|mixed|string
     * @throws Exception
     */
    public function loadTableType($save = true) {
        $result = val('LoadTableType', $this->Data, false);

        if (is_string($result)) {
            return $result;
        }

        // Create a table to test loading.
        $st = Gdn::structure();
        $st->table(self::TABLE_PREFIX.'Test')->column('ID', 'int')->set(true, true);

        // Create a test file to load.
        if (!file_exists(PATH_UPLOADS.'/import')) {
            mkdir(PATH_UPLOADS.'/import');
        }

        $testPath = PATH_UPLOADS.'/import/test.txt';
        $testValue = 123;
        $testContents = 'ID'.self::NEWLINE.$testValue.self::NEWLINE;
        file_put_contents($testPath, $testContents, LOCK_EX);

        // Try LoadTableOnSameServer.
        try {
            $this->_LoadTableOnSameServer('Test', $testPath);
            $value = $this->SQL->get(self::TABLE_PREFIX.'Test')->value('ID');
            if ($value == $testValue) {
                $result = 'LoadTableOnSameServer';
            }
        } catch (Exception $ex) {
            $result = false;
        }

        // Try LoadTableLocalInfile.
        if (!$result) {
            try {
                $this->_LoadTableLocalInfile('Test', $testPath);
                $value = $this->SQL->get(self::TABLE_PREFIX.'Test')->value('ID');
                if ($value == $testValue) {
                    $result = 'LoadTableLocalInfile';
                }
            } catch (Exception $ex) {
                $result = false;
            }
        }

        // If those two didn't work then default to LoadTableWithInsert.
        if (!$result) {
            $result = 'LoadTableWithInsert';
        }

        // Cleanup.
        safeUnlink($testPath);
        $st->table(self::TABLE_PREFIX.'Test')->drop();

        if ($save) {
            $this->Data['LoadTableType'] = $result;
        }
        return $result;
    }

    /**
     *
     *
     * @return bool
     */
    public function localInfileSupported() {
        $sql = "show variables like 'local_infile'";
        $data = $this->query($sql)->resultArray();
        if (strcasecmp(getValueR('0.Value', $data), 'ON') == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     *
     * @param string $overwrite
     * @param string $email
     * @param string $password
     * @return mixed
     */
    public function overwrite($overwrite = '', $email = '', $password = '') {
        if ($overwrite == '') {
            return val('Overwrite', $this->Data);
        }
        $this->Data['Overwrite'] = $overwrite;
        if (strcasecmp($overwrite, 'Overwrite') == 0) {
            $this->Data['OverwriteEmail'] = $email;
            $this->Data['OverwritePassword'] = $password;
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
     * @param $line
     * @return array
     */
    public function parseInfoLine($line) {
        $info = explode(',', $line);
        $result = [];
        foreach ($info as $item) {
            $propVal = explode(':', $item);
            if (array_key_exists(1, $propVal)) {
                $result[trim($propVal[0])] = trim($propVal[1]);
            } else {
                $result[trim($item)] = '';
            }
        }

        return $result;
    }


    /**
     * Process the import tables from the database.
     */
    public function processImportDb() {
        // Grab a list of all of the tables.
        $tableNames = $this->SQL->fetchTables(':_z%');
        if (count($tableNames) == 0) {
            throw new Gdn_UserException('Your database does not contain any import tables.');
        }

        $tables = [];
        foreach ($tableNames as $tableName) {
            $tableName = stringBeginsWith($tableName, $this->Database->DatabasePrefix, true, true);
            $destTableName = stringBeginsWith($tableName, 'z', true, true);
            $tableInfo = ['Table' => $destTableName];

            $columnInfos = $this->SQL->fetchTableSchema($tableName);
            $columns = [];
            foreach ($columnInfos as $columnInfo) {
                $columns[getValue('Name', $columnInfo)] = Gdn::structure()->columnTypeString($columnInfo);
            }
            $tableInfo['Columns'] = $columns;
            $tables[$destTableName] = $tableInfo;
        }
        $this->Data['Tables'] = $tables;
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
        increaseMaxExecutionTime(60 * 10);

        $path = $this->ImportPath;
        $basePath = dirname($path).DS.'import';
        $tables = [];

        if (!is_readable($path)) {
            throw new Gdn_UserException(t('The input file is not readable.', 'The input file is not readable.  Please check permissions and try again.'));
        }
        if (!is_writeable($basePath)) {
            throw new Gdn_UserException(sprintf(t('Data file directory (%s) is not writable.'), $basePath));
        }

        // Open the import file.
        ini_set('auto_detect_line_endings', true);
        $fpin = gzopen($path, 'rb');
        $fpout = null;

        // Make sure it has the proper header.
        try {
            $header = $this->getImportHeader($fpin);
        } catch (Exception $ex) {
            fclose($fpin);
            throw $ex;
        }

        $rowCount = 0;
        $lineNumber = 0;
        while (($line = fgets($fpin)) !== false) {
            $lineNumber++;

            if ($line == "\n") {
                if ($fpout) {
                    // We are in a table so close it off.
                    fclose($fpout);
                    $fpout = 0;
                }
            } elseif ($fpout) {
                // We are in a table so dump the line.
                fputs($fpout, $line);
            } elseif (substr_compare(self::COMMENT, $line, 0, strlen(self::COMMENT)) == 0) {
                // This is a comment line so do nothing.
            } else {
                // This is the start of a table.
                $tableInfo = $this->parseInfoLine($line);

                if (!array_key_exists('Table', $tableInfo)) {
                    throw new Gdn_UserException(sprintf(t('Could not parse import file. The problem is near line %s.'), $lineNumber));
                }

                $table = $tableInfo['Table'];
                $tableSanitized = preg_replace('#[^A-Z0-9\-_]#i', '_', $table);
                $path = $basePath.DS.$tableSanitized.'.txt';
                $fpout = fopen($path, 'wb');

                $tableInfo['Path'] = $path;
                unset($tableInfo['Table']);

                // Get the column headers from the next line.
                if (($line = fgets($fpin)) !== false) {
                    $lineNumber++;

                    // Strip \r out of line.
                    $line = str_replace(["\r\n", "\r"], ["\n", "\n"], $line);
                    fwrite($fpout, $line);
                    $columns = $this->parseInfoLine($line);
                    $tableInfo['Columns'] = $columns;

                    $tables[$table] = $tableInfo;
                }
            }
        }
        gzclose($fpin);
        if ($fpout) {
            fclose($fpout);
        }

        if (count($tables) == 0) {
            throw new Gdn_UserException(t('The import file does not contain any data.'));
        }

        $this->Data['Tables'] = $tables;

        return true;
    }

    /**
     * Run the step in the import.
     *
     * @param int $step the step to run.
     * @return mixed Whether the step succeeded or an array of information.
     */
    public function runStep($step = 1) {
        $started = $this->stat('Started');
        if ($started === null) {
            $this->stat('Started', microtime(true), 'time');
        }

        $steps = $this->steps();
        $lastStep = end(array_keys($steps));
        if (!isset($steps[$step]) || $step > $lastStep) {
            return 'COMPLETE';
        }
        if (!$this->Timer) {
            $newTimer = true;
            $this->Timer = new Gdn_Timer();
            $this->Timer->start('');
        }

        // Run a standard step every time.
        if (isset($steps[0])) {
            call_user_func([$this, $steps[0]]);
        }

        $method = $steps[$step];
        $result = call_user_func([$this, $method]);

        if ($this->generateSQL()) {
            $this->saveSQL($method);
        }

        $elapsedTime = $this->Timer->elapsedTime();
        $this->stat('Time Spent on Import', $elapsedTime, 'add');

        if (isset($newTimer)) {
            $this->Timer->finish('');
        }

        if ($result && !array_key_exists($step + 1, $this->steps())) {
            $this->stat('Finished', microtime(true), 'time');
        }

        return $result;
    }

    /**
     * Run a query, replacing database prefixes.
     *
     * @param string $sql The sql to execute.
     *  - :_z will be replaced by the import prefix.
     *  - :_ will be replaced by the database prefix.
     * @param array $parameters PDO parameters to pass to the query.
     * @return Gdn_DataSet
     */
    public function query($sql, $parameters = null) {
        $db = Gdn::database();

        // Replace db prefixes.
        $sql = str_replace([':_z', ':_'], [$db->DatabasePrefix.self::TABLE_PREFIX, $db->DatabasePrefix], $sql);

        // Figure out the type of the type of the query.
        if (stringBeginsWith($sql, 'select')) {
            $type = 'select';
        } elseif (stringBeginsWith($sql, 'truncate'))
            $type = 'truncate';
        elseif (stringBeginsWith($sql, 'insert'))
            $type = 'insert';
        elseif (stringBeginsWith($sql, 'update'))
            $type = 'update';
        elseif (stringBeginsWith($sql, 'delete'))
            $type = 'delete';
        else {
            $type = 'select';
        }

        // Execute the query.
        if (is_array($parameters)) {
            $this->SQL->namedParameters($parameters);
        }

        $result = $this->SQL->query($sql, $type);

        //$this->Timer->split('Sql: '. str_replace("\n", "\n     ", $Sql));

        return $result;
    }

    /**
     *
     */
    public function saveState() {
        saveToConfig([
            'Garden.Import.CurrentStep' => $this->CurrentStep,
            'Garden.Import.CurrentStepData' => $this->Data,
            'Garden.Import.ImportPath' => $this->ImportPath]);
    }

    /**
     *
     *
     * @param $currentStep
     */
    public function saveSQL($currentStep) {
        $sQLPath = $this->data('SQLPath');

        $queries = $this->Database->CapturedSql;
        foreach ($queries as $index => $sql) {
            $queries[$index] = rtrim($sql, ';').';';
        }
        $queries = "\n\n/* $currentStep */\n\n".implode("\n\n", $queries);


        file_put_contents(PATH_UPLOADS.'/'.$sQLPath, $queries, FILE_APPEND | LOCK_EX);
    }

    /**
     * Set the category permissions based on the permission table.
     */
    public function setCategoryPermissionIDs() {
        // First build a list of category
        $permissions = $this->SQL->getWhere('Permission', ['JunctionColumn' => 'PermissionCategoryID', 'JunctionID >' => 0])->resultArray();
        $categoryIDs = [];
        foreach ($permissions as $row) {
            $categoryIDs[$row['JunctionID']] = $row['JunctionID'];
        }

        // Update all of the child categories.
        $root = CategoryModel::categories(-1);
        $this->_setCategoryPermissionIDs($root, $root['CategoryID'], $categoryIDs);
    }

    /**
     *
     *
     * @param $category
     * @param $permissionID
     * @param $iDs
     */
    protected function _setCategoryPermissionIDs($category, $permissionID, $iDs) {
        static $categoryModel;
        if (!isset($categoryModel)) {
            $categoryModel = new CategoryModel();
        }

        $categoryID = $category['CategoryID'];
        if (isset($iDs[$categoryID])) {
            $permissionID = $categoryID;
        }

        if ($category['PermissionCategoryID'] != $permissionID) {
            $categoryModel->setField($categoryID, 'PermissionCategoryID', $permissionID);
        }

        $childIDs = val('ChildIDs', $category, []);
        foreach ($childIDs as $childID) {
            $childCategory = CategoryModel::categories($childID);
            if ($childCategory) {
                $this->_setCategoryPermissionIDs($childCategory, $permissionID, $iDs);
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

        $data = $this->SQL->get('zRole')->resultArray();

        $roleDefaults = [
            'Garden.Registration.ConfirmEmail' => false
        ];
        $roleTypes = [];

        foreach ($data as $row) {
            if ($this->importExists('Role', '_Default')) {
                $name = $row['_Default'];
            } else {
                $name = val('Name', $row);
            }

            $roleID = $row['RoleID'];

            if (preg_match('`anonymous`', $name)) {
                $name = 'guest';
            } elseif (preg_match('`admin`', $name))
                $name = 'administrator';

            switch (strtolower($name)) {
                case 'email':
                case 'confirm email':
                case 'users awaiting email confirmation':
                case 'pending':
                    $roleTypes[$roleID] = RoleModel::TYPE_UNCONFIRMED;
                    $roleDefaults['Garden.Registration.ConfirmEmail'] = true;
                    break;
                case 'member':
                case 'members':
                case 'registered':
                case 'registered users':
                    $roleTypes[$roleID] = RoleModel::TYPE_MEMBER;
                    break;
                case 'guest':
                case 'guests':
                case 'unauthenticated':
                case 'unregistered':
                case 'unregistered / not logged in':
                    $roleTypes[$roleID] = RoleModel::TYPE_GUEST;
                    break;
                case 'applicant':
                case 'applicants':
                    $roleTypes[$roleID] = RoleModel::TYPE_APPLICANT;
                    break;
            }
        }
        saveToConfig($roleDefaults);
        $roleModel = new RoleModel();
        foreach ($roleTypes as $roleID => $type) {
            $roleModel->setField($roleID, 'Type', $type);
        }
    }

    /**
     *
     *
     * @param $key
     * @param null $value
     * @param string $op
     * @return mixed
     */
    public function stat($key, $value = null, $op = 'set') {
        if (!isset($this->Data['Stats'])) {
            $this->Data['Stats'] = [];
        }

        $stats =& $this->Data['Stats'];

        if ($value !== null) {
            switch (strtolower($op)) {
                case 'add':
                    $value += val($key, $stats, 0);
                    $stats[$key] = $value;
                    break;
                case 'set':
                    $stats[$key] = $value;
                    break;
                case 'time':
                    $stats[$key] = date('Y-m-d H:i:s', $value);
            }
            return $stats[$key];
        } else {
            return val($key, $stats, null);
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
     * @param string $tableName
     * @return mixed
     */
    public function &tables($tableName = '') {
        if ($tableName) {
            return $this->Data['Tables'][$tableName];
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
        increaseMaxExecutionTime(60 * 10);

        // Define the necessary SQL.
        $sqls = [];

        if (!$this->importExists('Discussion', 'LastCommentID')) {
            $sqls['Discussion.LastCommentID'] = $this->getCountSQL('max', 'Discussion', 'Comment');
        }

        if (!$this->importExists('Discussion', 'DateLastComment')) {
            $sqls['Discussion.DateLastComment'] = "update :_Discussion d
         left join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.DateLastComment = coalesce(c.DateInserted, d.DateInserted)";
        }

        if (!$this->importExists('Discussion', 'LastCommentUserID')) {
            $sqls['Discussion.LastCommentUseID'] = "update :_Discussion d
         join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.LastCommentUserID = c.InsertUserID";
        }

        if (!$this->importExists('Discussion', 'Body')) {
            // Update the body of the discussion if it isn't there.
            if (!$this->importExists('Discussion', 'FirstCommentID')) {
                $sqls['Discussion.FirstCommentID'] = $this->getCountSQL('min', 'Discussion', 'Comment', 'FirstCommentID', 'CommentID');
            }

            $sqls['Discussion.Body'] = "update :_Discussion d
         join :_Comment c
            on d.FirstCommentID = c.CommentID
         set d.Body = c.Body, d.Format = c.Format";

            if ($this->importExists('Media') && Gdn::structure()->tableExists('Media')) {
                // Comment Media has to go onto the discussion.
                $sqls['Media.Foreign'] = "update :_Media m
            join :_Discussion d
               on d.FirstCommentID = m.ForeignID and m.ForeignTable = 'comment'
            set m.ForeignID = d.DiscussionID, m.ForeignTable = 'discussion'";
            }

            $sqls['Comment.FirstComment.Delete'] = "delete c.*
         from :_Comment c
         inner join :_Discussion d
           on d.FirstCommentID = c.CommentID";
        }

        if (!$this->importExists('Discussion', 'CountComments')) {
            $sqls['Discussion.CountComments'] = $this->getCountSQL('count', 'Discussion', 'Comment');
        }

        if ($this->importExists('UserDiscussion') && !$this->importExists('UserDiscussion', 'CountComments') && $this->importExists('UserDiscussion', 'DateLastViewed')) {
            $sqls['UserDiscussuion.CountComments'] = "update :_UserDiscussion ud
         set CountComments = (
           select count(c.CommentID)
           from :_Comment c
           where c.DiscussionID = ud.DiscussionID
             and c.DateInserted <= ud.DateLastViewed)";

        }

        if ($this->importExists('Tag') && $this->importExists('TagDiscussion')) {
            $sqls['Tag.CoundDiscussions'] = $this->getCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
        }

        if ($this->importExists('Poll') && Gdn::structure()->tableExists('Poll')) {
            $sqls['PollOption.CountVotes'] = $this->getCountSQL('count', 'PollOption', 'PollVote', 'CountVotes', 'PollOptionID');

            $sqls['Poll.CountOptions'] = $this->getCountSQL('count', 'Poll', 'PollOption', 'CountOptions', 'PollID');
            $sqls['Poll.CountVotes'] = $this->getCountSQL('sum', 'Poll', 'PollOption', 'CountVotes', 'CountVotes', 'PollID');
        }

        if ($this->importExists('Activity', 'ActivityType')) {
            $sqls['Activity.ActivityTypeID'] = "
            update :_Activity a
            join :_ActivityType t
               on a.ActivityType = t.Name
            set a.ActivityTypeID = t.ActivityTypeID";
        }

        if ($this->importExists('Tag') && $this->importExists('TagDiscussion')) {
            $sqls['Tag.CoundDiscussions'] = $this->getCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
        }

        $sqls['Category.CountDiscussions'] = $this->getCountSQL('count', 'Category', 'Discussion');
        $sqls['Category.CountComments'] = $this->getCountSQL('sum', 'Category', 'Discussion', 'CountComments', 'CountComments');
        if (!$this->importExists('Category', 'PermissionCategoryID')) {
            $sqls['Category.PermissionCategoryID'] = "update :_Category set PermissionCategoryID = -1";
        }

        if ($this->importExists('Conversation') && $this->importExists('ConversationMessage')) {
            $sqls['Conversation.FirstMessageID'] = $this->getCountSQL('min', 'Conversation', 'ConversationMessage', 'FirstMessageID', 'MessageID');

            if (!$this->importExists('Conversation', 'CountMessages')) {
                $sqls['Conversation.CountMessages'] = $this->getCountSQL('count', 'Conversation', 'ConversationMessage', 'CountMessages', 'MessageID');
            }
            if (!$this->importExists('Conversation', 'LastMessageID')) {
                $sqls['Conversation.LastMessageID'] = $this->getCountSQL('max', 'Conversation', 'ConversationMessage', 'LastMessageID', 'MessageID');
            }

            if (!$this->importExists('Conversation', 'DateUpdated')) {
                $sqls['Converstation.DateUpdated'] = "update :_Conversation c join :_ConversationMessage m on c.LastMessageID = m.MessageID set c.DateUpdated = m.DateInserted";
            }

            if ($this->importExists('UserConversation')) {
                if (!$this->importExists('UserConversation', 'LastMessageID')) {
                    if ($this->importExists('UserConversation', 'DateLastViewed')) {
                        // Get the value from the DateLastViewed.
                        $sqls['UserConversation.LastMessageID'] =
                            "update :_UserConversation uc
                     set LastMessageID = (
                       select max(MessageID)
                       from :_ConversationMessage m
                       where m.ConversationID = uc.ConversationID
                         and m.DateInserted >= uc.DateLastViewed)";
                    } else {
                        // Get the value from the conversation.
                        // In this case just mark all of the messages read.
                        $sqls['UserConversation.LastMessageID'] =
                            "update :_UserConversation uc
                     join :_Conversation c
                       on c.ConversationID = uc.ConversationID
                     set uc.CountReadMessages = c.CountMessages,
                       uc.LastMessageID = c.LastMessageID";
                    }
                } elseif (!$this->importExists('UserConversation', 'DateLastViewed')) {
                    // We have the last message so grab the date from that.
                    $sqls['UserConversation.DateLastViewed'] =
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
            $sqls['User.DateFirstVisit'] = 'update :_User set DateFirstVisit = DateInserted';
        }
        if (!$this->importExists('User', 'CountDiscussions')) {
            $sqls['User.CountDiscussions'] = $this->getCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID');
        }
        if (!$this->importExists('User', 'CountComments')) {
            $sqls['User.CountComments'] = $this->getCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID');
        }
        if (!$this->importExists('User', 'CountBookmarks')) {
            $sqls['User.CountBookmarks'] = "update :_User u
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
        $currentSubstep = val('CurrentSubstep', $this->Data, 0);

//      $Sqls2 = array();
//      $i = 1;
//      foreach ($Sqls as $Name => $Sql) {
//         $Sqls2[] = "/* $i. $Name */\n"
//            .str_replace(':_', $this->Database->DatabasePrefix, $Sql)
//            .";\n";
//         $i++;
//      }
//      throw new exception(implode("\n", $Sqls2));

        // Execute the SQL.
        $keys = array_keys($sqls);
        for ($i = $currentSubstep; $i < count($keys); $i++) {
            $this->Data['CurrentStepMessage'] = sprintf(t('%s of %s'), $currentSubstep + 1, count($keys));
            $sql = $sqls[$keys[$i]];
            $this->query($sql);
            if ($this->Timer->elapsedTime() > $this->MaxStepTime) {
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
            $categories = CategoryModel::categories();
            $takenCodes = [];

            foreach ($categories as $category) {
                $urlCode = urldecode(Gdn_Format::url($category['Name']));
                if (strlen($urlCode) > 50) {
                    $urlCode = 'c'.$category['CategoryID'];
                } elseif (is_numeric($urlCode)) {
                    $urlCode = 'c'.$urlCode;
                }

                if (in_array($urlCode, $takenCodes)) {
                    $parentCategory = CategoryModel::categories($category['ParentCategoryID']);
                    if ($parentCategory && $parentCategory['CategoryID'] != -1) {
                        $urlCode = Gdn_Format::url($parentCategory['Name']).'-'.$urlCode;
                    }
                    if (in_array($urlCode, $takenCodes)) {
                        $urlCode = $category['CategoryID'];
                    }
                }

                $takenCodes[] = $urlCode;
                Gdn::sql()->put(
                    'Category',
                    ['UrlCode' => $urlCode],
                    ['CategoryID' => $category['CategoryID']]
                );
            }
        }
        // Rebuild the category tree.
        $categoryModel = new CategoryModel();
        $categoryModel->rebuildTree();
        $this->setCategoryPermissionIDs();

        return true;
    }

    /**
     * Verify imported data.
     */
    public function verifyImport() {
        // When was the latest discussion posted?
        $latestDiscussion = $this->SQL->select('DateInserted', 'max', 'LatestDiscussion')->from('Discussion')->get();
        if ($latestDiscussion->count()) {
            $this->stat(
                'Last Discussion',
                $latestDiscussion->value('LatestDiscussion')
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
            $this->SQL->getCount('Discussion', ['InsertUserID' => '0'])
        );

        // When was the latest comment posted?
        $latestComment = $this->SQL->select('DateInserted', 'max', 'LatestComment')->from('Comment')->get();
        if ($latestComment->count()) {
            $this->stat(
                'Last Comment',
                $latestComment->value('LatestComment')
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
            $this->SQL->getCount('Comment', ['InsertUserID' => '0'])
        );

        // Any users without roles?
        $usersWithoutRoles = $this->SQL
            ->select('*', 'count', 'RowCount')
            ->from('User u')
            ->leftJoin('UserRole ur', 'u.UserID = ur.UserID')
            ->leftJoin('Role r', 'ur.RoleID = r.RoleID')
            ->where('r.Name', null)
            ->get()
            ->firstRow()->RowCount;
        $this->stat(
            'Users Without a Valid Role',
            $usersWithoutRoles
        );

        return true;
    }

    /**
     * Import a table from a CSV using SQL insert statements.
     *
     * @param string $tablename The name of the table to import to.
     * @param string $path The path to the CSV.
     * @param bool $skipHeader Whether the CSV contains a header row.
     * @param int $chunk The number of records to chunk the imports to.
     * @return bool Whether any records were found.
     */
    public function loadTableInsert($tablename, $path, $skipHeader = true, $chunk = 100) {
        $result = false;

        // Get the column count of the table.
        $st = Gdn::structure();
        $st->get(self::TABLE_PREFIX.$tablename);
        $columnCount = count($st->columns());
        $st->reset();

        ini_set('auto_detect_line_endings', true);
        $fp = fopen($path, 'rb');

        // Figure out the current position.
        $fPosition = val('CurrentLoadPosition', $this->Data, 0);

        if ($fPosition == 0 && $skipHeader) {
            // Skip the header row.
            $row = self::fGetCSV2($fp);
        }

        if ($fPosition == 0) {
            $px = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX;
            Gdn::database()->query("truncate table {$px}{$tablename}");
        } else {
            fseek($fp, $fPosition);
        }

        $pDO = Gdn::database()->connection();
        $pxTablename = Gdn::database()->DatabasePrefix.self::TABLE_PREFIX.$tablename;

        $inserts = '';
        $count = 0;
        while ($row = self::fGetCSV2($fp)) {
            ++$count;
            $result = true;
            $row = array_map('trim', $row);
            // Quote the values in the row.
            $row = array_map([$pDO, 'quote'], $row);

            // Add any extra columns to the row.
            while (count($row) < $columnCount) {
                $row[] = 'null';
            }

            // Add the insert values to the sql.
            if (strlen($inserts) > 0) {
                $inserts .= ',';
            }
            $inserts .= '('.implode(',', $row).')';

            if ($count >= $chunk) {
                // Insert in chunks.
                $sql = "insert $pxTablename values $inserts";
                $this->Database->query($sql);
                $count = 0;
                $inserts = '';

                // Check for a timeout.
                if ($this->Timer->elapsedTime() > $this->MaxStepTime) {
                    // The step's taken too long. Save the file position.
                    $pos = ftell($fp);
                    $this->Data['CurrentLoadPosition'] = $pos;

                    $filesize = filesize($path);
                    if ($filesize > 0) {
                        $percentComplete = $pos / filesize($path);
                        $this->Data['CurrentStepMessage'] = $tablename.' ('.round($percentComplete * 100.0).'%)';
                    }

                    fclose($fp);
                    return 0;
                }
            }
        }
        fclose($fp);

        if (strlen($inserts) > 0) {
            $sql = "insert $pxTablename values $inserts";
            $this->query($sql);
        }
        return $result;
    }
}
