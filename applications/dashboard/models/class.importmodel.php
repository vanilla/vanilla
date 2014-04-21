<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Object for importing files created with VanillaPorter.
 */
class ImportModel extends Gdn_Model {
	const COMMENT = '//';
	const DELIM = ',';
	const ESCAPE = '\\';
	const ID_PADDING = 1000; // padding to add to IDs that are incremented
	const NEWLINE = "\n";
	const NULL = '\N';
	const TABLE_PREFIX = 'z';
	const QUOTE = '"';

	public $CurrentStep = 0;

	public $Data = array();
   
   public $ErrorType = '';

	public $ImportPath = '';

	public $MaxStepTime = 1; // seconds

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
      11 => 'AddActivity'
	);

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
      11 => 'AddActivity'
   );

   protected $_OverwriteStepsDb = array(
      0 => 'Initialize',
   	1 => 'ProcessImportDb',
   	2 => 'DefineTables',
   	3 => 'InsertUserTable',
   	4 => 'DeleteOverwriteTables',
   	5 => 'InsertTables',
   	6 => 'UpdateCounts',
      7 => 'CustomFinalization',
      8 => 'AddActivity'
   );

	/**
	 * @var Gdn_Timer Used for timing various long running methods to break them up into pieces.
	 */
	public $Timer = NULL;

	public function __construct($ImportPath = '') {
		$this->ImportPath = $ImportPath;
		parent::__construct();
	}

   public function AddActivity() {
      // Build the story for the activity.
      $Header = $this->GetImportHeader();
      $PorterVersion = GetValue('Vanilla Export', $Header, T('unknown'));
      $SourceData = GetValue('Source', $Header, T('unknown'));
      $Story = sprintf(T('Vanilla Export: %s, Source: %s'), $PorterVersion, $SourceData);

      $ActivityModel = new ActivityModel();
      $ActivityModel->Add(Gdn::Session()->UserID, 'Import', $Story);
      return TRUE;
   }

	public function AssignUserIDs() {
		// Assign user IDs of email matches.
		$Sql = "update :_zUser i
         join :_User u
           on i.Email = u.Email
         set i._NewID = u.UserID, i._Action = 'Update'";
		$this->Query($Sql);

		// Assign user IDs of name matches.
		$Sql = "update :_zUser i
         join :_User u
         	on i.Name = u.Name
         left join :_zUser i2
         	on i2._NewID = u.UserID /* make sure no duplicates */
         set i._NewID = u.UserID, i._Action = 'Update'
         where i._NewID is null and i2.UserID is null";
		$this->Query($Sql);

		// Get the max UserID so we can increment new users.
		$MaxID = $this->Query('select max(UserID) as MaxID from :_User')->Value('MaxID', 0);
		$MinID = $this->Query('select min(UserID) as MinID from :_zUser where _NewID is null')->Value('MinID', NULL);

		if(is_null($MinID)) {
			//$this->Timer->Split('No more IDs to update');
			// No more IDs to update.
			return TRUE;
		}

		$IDInc = $MaxID - $MinID + self::ID_PADDING;

		// Update the users to insert.
		$Sql = "update :_zUser i
         left join :_User u
         	on u.Name = i.Name /* make sure no duplicates */
         set i._NewID = i.UserID + $IDInc, i._Action = 'Insert'
         where i._NewID is null
         	and u.UserID is null";
		$this->Query($Sql);

		// There still might be users that have overlapping usernames which must be changed.
		// Append a random suffix to the new username.
		$Sql = "update :_zUser i
         set i.Name = concat(i.Name, convert(floor(1000 + rand() * 8999), char)), i._NewID = i.UserID + $IDInc, i._Action = 'Insert'
         where i._NewID is null";
		$this->Query($Sql);

		return TRUE;
	}

	public function AssignOtherIDs() {
		$this->_AssignIDs('Role', 'RoleID', 'Name');
		$this->_AssignIDs('Category', 'CategoryID', 'Name');
		$this->_AssignIDs('Discussion');
		$this->_AssignIDs('Comment');

		return TRUE;
	}

	protected function _AssignIDs($TableName, $PrimaryKey = NULL, $SecondaryKey = NULL) {
		if(!array_key_exists($TableName, $this->Tables()))
			return;

		if(!$PrimaryKey)
			$PrimaryKey = $TableName.'ID';

		// Assign existing IDs.
		if($SecondaryKey) {
			$Sql = "update :_z$TableName i
            join :_$TableName t
              on t.$SecondaryKey = i.$SecondaryKey
            set i._NewID = t.$PrimaryKey, i._Action = 'Update'";
			$this->Query($Sql);
		}

		// Get new IDs.
		$MaxID = $this->Query("select max($PrimaryKey) as MaxID from :_$TableName")->Value('MaxID', 0);
		$MinID = $this->Query("select min($PrimaryKey) as MinID from :_z$TableName where _NewID is null")->Value('MinID', NULL);

		if(is_null($MinID)) {
			//$this->Timer->Split('No more IDs to update');
			// No more IDs to update.
			return TRUE;
		}
		if($MaxID == 0)
			$IDInc = 0;
		else
			$IDInc = $MaxID - $MinID + self::ID_PADDING;

		$Sql = "update :_z$TableName i
         set i._NewID = i.$PrimaryKey + $IDInc, i._Action = 'Insert'
         where i._NewID is null";
		$this->Query($Sql);
	}

	public function AuthenticateAdminUser() {
		$OverwriteEmail = GetValue('OverwriteEmail', $this->Data);
		$OverwritePassword = GetValue('OverwritePassword', $this->Data);

		$Data = Gdn::SQL()->GetWhere('zUser', array('Email' => $OverwriteEmail));
		if($Data->NumRows() == 0) {
			$Result = FALSE;
		} else {
			$Data = $Data->FirstRow();
         $HashMethod = GetValue('HashMethod', $Data);
         if (!$HashMethod)
            $HashMethod = $this->GetPasswordHashMethod();
         
			$PasswordHash = new Gdn_PasswordHash();
         if (strcasecmp($HashMethod, 'reset') == 0 || $this->Data('UseCurrentPassword')) {
            $Result = TRUE;
         } else {
            $Result = $PasswordHash->CheckPassword($OverwritePassword, GetValue('Password', $Data), $HashMethod, GetValue('Name',$Data));
         }
		}
		if(!$Result) {
			$this->Validation->AddValidationResult('Email', T('ErrorCredentials'));
         $this->ErrorType = 'Credentials';
		}
		return $Result;
      
	}

   public function CustomFinalization() {
      $this->SetRoleDefaults();
      
      $Imp = $this->GetCustomImportModel();
      if ($Imp !== NULL)
         $Imp->AfterImport();

      return TRUE;
   }

   public function Data($Key, $Value = NULL) {
      if ($Value === NULL) {
         return GetValue($Key, $this->Data);
      }
      $this->Data[$Key] = $Value;
   }

	public function DefineTables() {
		$St = Gdn::Structure();
		$DestStructure = clone $St;

		$Tables =& $this->Tables();

		foreach($Tables as $Table => $TableInfo) {
			$Columns = $TableInfo['Columns'];
         if(!is_array($Columns) || count($Columns) == 0)
            throw new Gdn_UserException(sprintf(T('The %s table is not in the correct format.', $Table)));


			$St->Table(self::TABLE_PREFIX.$Table);
			// Get the structure from the destination database to match types.
			try {
				$DestStructure->Reset()->Get($Table);
			} catch(Exception $Ex) {
				// Trying to import into a non-existant table.
				$Tables[$Table]['Skip'] = TRUE;
				continue;
			}
			//$DestColumns = $DestStructure->Columns();
			$DestModified = FALSE;

			foreach ($Columns as $Name => $Type) {
            if (!$Name)
               throw new Gdn_UserException(sprintf(T('The %s table is not in the correct format.'), $Table));

				if ($DestStructure->ColumnExists($Name)) {
					$StructureType = $DestStructure->ColumnTypeString($DestStructure->Columns($Name));
				} elseif ($DestStructure->ColumnExists($Type)) {
               // Fix the table definition.
               unset($Tables[$Table]['Columns'][$Name]);
               $Tables[$Table]['Columns'][$Type] = '';

               $Name = $Type;
               $StructureType = $DestStructure->ColumnTypeString($DestStructure->Columns($Type));
            } elseif (!StringBeginsWith($Name, '_')) {
					$StructureType = $Type;

					if(!$StructureType)
						$StructureType = 'varchar(255)';

					// This is a new column so it needs to be added to the destination table too.
					$DestStructure->Column($Name, $StructureType, NULL);
					$DestModified = TRUE;
            } elseif ($Type) {
               $StructureType = $Type;
            } else {
               $StructureType = 'varchar(255)';
            }

				$St->Column($Name, $StructureType, NULL);
			}
			// Add a new ID column.
			if(array_key_exists($Table.'ID', $Columns)) {
				$St
				->Column('_NewID', $DestStructure->ColumnTypeString($Table.'ID'), NULL)
				->Column('_Action', array('Insert', 'Update'), NULL);
			}

         try {
            if (!$this->IsDbSource())
               $St->Set(TRUE, TRUE);
            else
               $St->Reset();
            
            if($DestModified)
               $DestStructure->Set();
         } catch(Exception $Ex) {
            // Since these exceptions are likely caused by a faulty import file they should be considered user exceptions.
            throw new Gdn_UserException(sprintf(T('There was an error while trying to create the %s table (%s).'), $Table, $Ex->getMessage())); //, $Ex);
         }
		}
		return TRUE;
	}

	public function DefineIndexes() {
		$St = Gdn::Structure();
      $DestStructure = clone Gdn::Structure();

		foreach($this->Tables() as $Table => $TableInfo) {
         if(GetValue('Skip', $TableInfo))
            continue;
         
			$St->Table(self::TABLE_PREFIX.$Table);
         $Columns = $TableInfo['Columns'];

         $DestStructure->Reset()->Get($Table);
         $DestColumns = $DestStructure->Columns();

			// Check to index the primary key.
			$Col = $Table.'ID';
			if(array_key_exists($Col, $Columns))
				$St->Column($Col, $Columns[$Col] ? $Columns[$Col] : $DestStructure->ColumnTypeString($Col), NULL, 'index');

			if($Table == 'User') {
				$St
				->Column('Name', $DestStructure->ColumnTypeString('Name'), NULL, 'index')
				->Column('Email', $DestStructure->ColumnTypeString('Email'), NULL, 'index')
				->Column('_NewID', 'int', NULL, 'index');
			}

			if(count($St->Columns()) > 0)
				$St->Set();
		}
		return TRUE;
	}

	public function DeleteFiles() {
      if (StringBeginsWith($this->ImportPath, 'Db:', TRUE))
         return;

      $St = Gdn::Structure();
		foreach (GetValue('Tables', $this->Data, array()) as $Table => $TableInfo) {
			$Path = GetValue('Path', $TableInfo, '');
			if (file_exists($Path))
				unlink($Path);

         // Drop the import table.
         $St->Table("z$Table")->Drop();
		}

		// Delete the uploaded files.
      $UploadedFiles = GetValue('UploadedFiles', $this->Data, array());
      foreach ($UploadedFiles as $Path => $Name) {
         @unlink($Path);
      }
	}

	/**
	 * Remove the data from the appropriate tables when we are overwriting the forum.
	 */
	public function DeleteOverwriteTables() {
		$Tables = array('Activity', 'Category', 'Comment', 'Conversation', 'ConversationMessage',
   		'Discussion', 'Draft', 'Invitation', 'Media', 'Message', 'Photo', 'Permission', 'Rank', 'Poll', 'PollOption', 'PollVote', 'Role', 'UserAuthentication',
   		'UserComment', 'UserConversation', 'UserDiscussion', 'UserMeta', 'UserRole');

      // Delete the default role settings.
      SaveToConfig(array(
         'Garden.Registration.DefaultRoles' => array(),
         'Garden.Registration.ApplicantRoleID' => 0
      ));

		// Execute the SQL.
		$CurrentSubstep = GetValue('CurrentSubstep', $this->Data, 0);
		for($i = $CurrentSubstep; $i < count($Tables); $i++) {
			$Table = $Tables[$i];

         // Make sure the table exists.
         $Exists = Gdn::Structure()->Table($Table)->TableExists();
         Gdn::Structure()->Reset();
         if (!$Exists)
            continue;

         $this->Data['CurrentStepMessage'] = $Table;
         
			if($Table == 'Permission')
            $this->SQL->Delete($Table, array('RoleID <>' => 0));
			else
				$this->SQL->Truncate($Table);
			if($this->Timer->ElapsedTime() > $this->MaxStepTime) {
				// The step's taken too long. Save the state and return.
				$this->Data['CurrentSubstep'] = $i + 1;
				return FALSE;
			}
		}
		if(isset($this->Data['CurrentSubstep']))
			unset($this->Data['CurrentSubstep']);

		$this->Data['CurrentStepMessage'] = '';
		return TRUE;
	}

	public function DeleteState() {
		RemoveFromConfig('Garden.Import');
	}

   public function FromPost($Post) {
      if (isset($Post['Overwrite']))
         $this->Data['Overwrite'] = $Post['Overwrite'];
      if (isset($Post['Email']))
         $this->Data['OverwriteEmail'] = $Post['Email'];
      if (isset($Post['Password'])) {
         $this->Data['OverwritePassword'] = $Post['Password'];
         $this->Data['UseCurrentPassword'] = GetValue('UseCurrentPassword', $Post);
      }
      if (isset($Post['GenerateSQL']))
         $this->Data['GenerateSQL'] = $Post['GenerateSQL'];
   }

   public function GenerateSQL($Value = NULL) {
      return $this->Data('GenerateSQL', $Value);
   }

   /**
    * Return SQL for updating a count.
    * @param string $Aggregate count, max, min, etc.
    * @param string $ParentTable The name of the parent table.
    * @param string $ChildTable The name of the child table
    * @param type $ParentColumnName
    * @param string $ChildColumnName
    * @param string $ParentJoinColumn
    * @param string $ChildJoinColumn
    * @return type 
    */
   public function GetCountSQL(
      $Aggregate, // count, max, min, etc.
      $ParentTable, $ChildTable, 
      $ParentColumnName = '', $ChildColumnName = '',
      $ParentJoinColumn = '', $ChildJoinColumn = '') {

      if(!$ParentColumnName) {
         switch(strtolower($Aggregate)) {
            case 'count': $ParentColumnName = "Count{$ChildTable}s"; break;
            case 'max': $ParentColumnName = "Last{$ChildTable}ID"; break;
            case 'min': $ParentColumnName = "First{$ChildTable}ID"; break;
            case 'sum': $ParentColumnName = "Sum{$ChildTable}s"; break;
         }
      }

      if(!$ChildColumnName)
         $ChildColumnName = $ChildTable.'ID';

      if(!$ParentJoinColumn)
         $ParentJoinColumn = $ParentTable.'ID';
      if(!$ChildJoinColumn)
         $ChildJoinColumn = $ParentJoinColumn;

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
   public function GetCustomImportModel() {
      $Header = $this->GetImportHeader();
      $Source = GetValue('Source', $Header, '');
      $Result = NULL;

      if (substr_compare('vbulletin', $Source, 0, 9, TRUE) == 0)
         $Result = new vBulletinImportModel();
      elseif (substr_compare('vanilla 1', $Source, 0, 9, TRUE) == 0)
         $Result = new Vanilla1ImportModel();

      if ($Result !== NULL)
         $Result->ImportModel = $this;

      return $Result;
   }

   public function ToPost(&$Post) {
      $D = $this->Data;
      $Post['Overwrite'] = GetValue('Overwrite', $D, 'Overwrite');
      $Post['Email'] = GetValue('OverwriteEmail', $D, '');
      $Post['Password'] = GetValue('OverwritePassword', $D, '');
      $Post['UseCurrentPassword'] = GetValue('UseCurrentPassword', $D, FALSE);
      $Post['GenerateSQL'] = GetValue('GenerateSQL', $D, FALSE);
   }

   public static function FGetCSV2($fp, $Delim = ',', $Quote = '"', $Escape = "\\") {
      // Get the full line, considering escaped returns.
      $Line = FALSE;
      do {
         $s = fgets($fp);
         //echo "<fgets>$s</fgets><br/>\n";

         if ($s === FALSE) {
            if ($Line === FALSE)
               return FALSE;
         }

         if ($Line === FALSE)
            $Line = $s;
         else
            $Line .= $s;
      } while(strlen($s) > 1 && substr($s, -2, 1) === $Escape);

      $Line = trim($Line, "\n");
      //echo "<Line>$Line</Line><br />\n";

      $Result = array();

      // Loop through the line and split on the delimiter.
      $Strlen = strlen($Line);
      $InEscape = FALSE;
      $InQuote = FALSE;
      $Token = '';
      for ($i = 0; $i < $Strlen; ++$i) {
         $c = $Line[$i];

         if ($InEscape) {
            // Check for an escaped null.
            if ($c == 'N' && strlen($Token) == 0) {
               $Token = NULL;
            } else {
               $Token .= $c;
            }
            $InEscape = FALSE;
         } else {
            switch ($c) {
               case $Escape:
                  $InEscape = TRUE;
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

	public function GetImportHeader($fpin = NULL) {
		$Header = GetValue('Header', $this->Data);
		if($Header)
			return $Header;

		if(is_null($fpin)) {
			if(!$this->ImportPath || !file_exists($this->ImportPath))
				return array();
         ini_set('auto_detect_line_endings', TRUE);
			$fpin = gzopen($this->ImportPath, 'rb');
			$fpopened = TRUE;
		}

		$Header = fgets($fpin);
		if(!$Header || strlen($Header) < 7 || substr_compare('Vanilla', $Header, 0, 7) != 0) {
			if(isset($fpopened))
				fclose($fpin);
			throw new Gdn_UserException(T('The import file is not in the correct format.'));
		}
		$Header = $this->ParseInfoLine($Header);
		if(isset($fpopened))
			fclose($fpin);
      $this->Data['Header'] = $Header;
		return $Header;
	}

	public function GetPasswordHashMethod() {
      $HashMethod = GetValue('HashMethod', $this->GetImportHeader());
      if ($HashMethod)
         return $HashMethod;

		$Source = GetValue('Source', $this->GetImportHeader());
		if (!$Source)
			return 'Unknown';
		if (substr_compare('Vanilla', $Source, 0, 7, FALSE) == 0)
			return 'Vanilla';
		if (substr_compare('vBulletin', $Source, 0,  9, FALSE) == 0)
			return 'vBulletin';
      if (substr_compare('phpBB', $Source, 0, 5, FALSE) == 0)
         return 'phpBB';
		return 'Unknown';
	}

   /** Checks to see of a table and/or column exists in the import data.
    *
    * @param string $Tablename The name of the table to check for.
    * @param string $Columnname
    * @return bool
    */
   public function ImportExists($Table, $Column = '') {
      if(!array_key_exists('Tables', $this->Data) || !array_key_exists($Table, $this->Data['Tables']))
         return false;
      if(!$Column)
         return TRUE;
      $Tables = $this->Data['Tables'];

      $Exists = GetValueR("Tables.$Table.Columns.$Column", $this->Data, FALSE);
      return $Exists !== FALSE;
   }

   public function Initialize() {
      if ($this->GenerateSQL()) {
         $this->SQL->CaptureModifications = TRUE;
         Gdn::Structure()->CaptureOnly = TRUE;
         $this->Database->CapturedSql = array();

         $SQLPath = $this->Data('SQLPath');
         if (!$SQLPath) {
            $SQLPath = 'import/import_'.date('Y-m-d_His').'.sql';
            $this->Data('SQLPath', $SQLPath);
         }
      }

      return TRUE;
   }

	public function InsertTables() {
		$InsertedCount = 0;
		$Timer = new Gdn_Timer();
		$Timer->Start();
		$Tables =& $this->Tables();
		foreach($Tables as $TableName => $TableInfo) {
			if(GetValue('Inserted', $TableInfo) || GetValue('Skip', $TableInfo)) {
				$InsertedCount++;
			} else {
				$this->Data['CurrentStepMessage'] = sprintf(T('%s of %s'), $InsertedCount, count($Tables));

            if(strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
               switch ($TableName) {
                  case 'Permission':
                     $this->InsertPermissionTable();
                     break;
                  default:
                     $RowCount = $this->_InsertTable($TableName);
                     break;
               }
               
            } else {
               switch($TableName) {
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
                     $this->Query($Sql);
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
                     $this->Query($Sql);
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
                     $this->Query($Sql);
                     break;
                  default:
                     $RowCount = $this->_InsertTable($TableName);
               }
            }

				$Tables[$TableName]['Inserted'] = TRUE;
            if(isset($RowCount))
               $Tables[$TableName]['RowCount'] = $RowCount;
				$InsertedCount++;
				// Make sure the loading isn't taking too long.
				if($Timer->ElapsedTime() > $this->MaxStepTime)
					break;
			}
		}

		$Result = $InsertedCount == count($this->Tables());
		if($Result)
			$this->Data['CurrentStepMessage'] = '';
		return $Result;
	}

   protected static function BackTick($Str) {
      return '`'.str_replace('`', '\`', $Str).'`';
   }

	protected function _InsertTable($TableName, $Sets = array()) {
		if(!array_key_exists($TableName, $this->Tables()))
			return;

      if (!Gdn::Structure()->TableExists($TableName))
         return 0;

		$TableInfo =& $this->Tables($TableName);
		$Columns = $TableInfo['Columns'];
      
      foreach ($Columns as $Key => $Value) {
         if (StringBeginsWith($Key, '_'))
            unset($Columns[$Key]);
      }

		// Build the column insert list.
		$Insert = "insert ignore :_$TableName (\n  "
		.implode(",\n  ", array_map(array('ImportModel', 'BackTick'), array_keys(array_merge($Columns, $Sets))))
		."\n)";
		$From = "from :_z$TableName i";
		$Where = '';

		// Build the select list for the insert.
		$Select = array();
		foreach($Columns as $Column => $X) {
         $BColumn = self::BackTick($Column);

			if(strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
				// The data goes in raw.
				$Select[] = "i.$BColumn";
			} elseif($Column == $TableName.'ID') {
				// This is the primary key.
				$Select[] = "i._NewID as $Column";
				$Where = "\nwhere i._Action = 'Insert'";
			} elseif(substr_compare($Column, 'ID', -2, 2) == 0) {
				// This is an ID field. Check for a join.
				foreach($this->Tables() as $StructureTableName => $TableInfo) {
					$PK = $StructureTableName.'ID';
					if(strlen($Column) >= strlen($PK) && substr_compare($Column, $PK, -strlen($PK), strlen($PK)) == 0) {
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
//		$PK = $TableName.'ID';
//		if(array_key_exists($PK, $Columns)) {
//		if(strcasecmp($this->Overwrite(), 'Overwrite') == 0)
//				$PK2 = $PK;
//			else
//				$PK2 = '_NewID';
//
//			$From .= "\nleft join :_$TableName o0\n  on o0.$PK = i.$PK2";
//			if($Where)
//				$Where .=  "\n  and ";
//			else
//				$Where = "\nwhere ";
//			$Where .= "o0.$PK is null";
//		}
		//}

		// Add the sets to the select list.
		foreach($Sets as $Field => $Value) {
			$Select[] = Gdn::Database()->Connection()->quote($Value).' as '.$Field;
		}

		// Build the sql statement.
		$Sql = $Insert
		."\nselect\n  ".implode(",\n  ", $Select)
		."\n".$From
		.$Where;

		//$this->Query($Sql);

		$RowCount = $this->Query($Sql);
      if($RowCount > 0) {
         return (int)$RowCount;
      } else {
         return FALSE;
      }
	}

   public function InsertPermissionTable() {
//      $this->LoadState();
      
      // Clear the permission table in case the step was only half done before.
      $this->SQL->Delete('Permission', array('RoleID <>' => 0));

      // Grab all of the permission columns.
      $PM = new PermissionModel();
      $GlobalColumns = array_filter($PM->PermissionColumns());
      unset($GlobalColumns['PermissionID']);
      $JunctionColumns = array_filter($PM->PermissionColumns('Category', 'PermissionCategoryID'));
      unset($JunctionColumns['PermissionID']);
      $JunctionColumns = array_merge(array('JunctionTable' => 'Category', 'JunctionColumn' => 'PermissionCategoryID', 'JunctionID' => -1), $JunctionColumns);
      
      if ($this->ImportExists('Permission', 'JunctionTable')) {
         $ColumnSets = array(array_merge($GlobalColumns, $JunctionColumns));
         $ColumnSets[0]['JunctionTable'] = NULL;
         $ColumnSets[0]['JunctionColumn'] = NULL;
         $ColumnSets[0]['JunctionID'] = NULL;
      } else {
         $ColumnSets = array($GlobalColumns, $JunctionColumns);
      }

      $Data = $this->SQL->Get('zPermission')->ResultArray();
      foreach ($Data as $Row) {
         $Presets = array_map('trim', explode(',', GetValue('_Permissions', $Row)));
         
         foreach ($ColumnSets as $ColumnSet) {
            $Set = array();
            $Set['RoleID'] = $Row['RoleID'];
            
            foreach ($Presets as $Preset) {
               if (strpos($Preset, '.') !== FALSE) {
                  // This preset is a specific permission.
                  
                  if (array_key_exists($Preset, $ColumnSet)) {   
                     $Set["`$Preset`"] = 1;
                  }
                  continue;
               }
               $Preset = strtolower($Preset);
               

               foreach ($ColumnSet as $ColumnName => $Default) {
                  if (isset($Row[$ColumnName]))
                     $Value = $Row[$ColumnName];
                  elseif (strpos($ColumnName, '.') === FALSE)
                     $Value = $Default;
                  elseif ($Preset == 'all')
                     $Value = 1;
                  elseif ($Preset == 'view')
                     $Value = StringEndsWith($ColumnName, 'View', TRUE) && !in_array($ColumnName, array('Garden.Settings.View'));
                  elseif ($Preset == $ColumnName)
                     $Value = 1;
                  else
                     $Value = $Default & 1;

                  $Set["`$ColumnName`"] = $Value;
               }
            }
            $this->SQL->Insert('Permission', $Set);
            unset($Set);
         }
      }
      return TRUE;
   }

	public function InsertUserTable() {
      $UseCurrentPassword = $this->Data('UseCurrentPassword');

      if ($UseCurrentPassword) {
         $CurrentUser = $this->SQL->GetWhere('User', array('UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
         $CurrentPassword = $CurrentUser['Password'];
         $CurrentHashMethod = $CurrentUser['HashMethod'];
      }

		// Delete the current user table.
		$this->SQL->Truncate('User');

		// Load the new user table.
		$UserTableInfo =& $this->Data['Tables']['User'];
      if (!$this->ImportExists('User', 'HashMethod'))
         $this->_InsertTable('User', array('HashMethod' => $this->GetPasswordHashMethod()));
      else
         $this->_InsertTable('User');
		$UserTableInfo['Inserted'] = TRUE;

      $AdminEmail = GetValue('OverwriteEmail', $this->Data);
      $SqlArgs = array(':Email' => $AdminEmail);
      $SqlSet = '';

      if ($UseCurrentPassword) {
         $SqlArgs[':Password'] = $CurrentPassword;
         $SqlArgs[':HashMethod'] = $CurrentHashMethod;
         $SqlSet = ', Password = :Password, HashMethod = :HashMethod';
      }

      // If doing a password reset, save out the new admin password:
      if (strcasecmp($this->GetPasswordHashMethod(), 'reset') == 0) {
         if (!isset($SqlArgs[':Password'])) {
            $PasswordHash = new Gdn_PasswordHash();
            $Hash = $PasswordHash->HashPassword(GetValue('OverwritePassword', $this->Data));
            $SqlSet .= ', Password = :Password, HashMethod = :HashMethod';
            $SqlArgs[':Password'] = $Hash;
            $SqlArgs[':HashMthod'] = 'Vanilla';
         }

         // Write it out.
         $this->Query("update :_User set Admin = 1{$SqlSet} where Email = :Email", $SqlArgs);
      } else {
         // Set the admin user flag.
         $this->Query("update :_User set Admin = 1{$SqlSet} where Email = :Email", $SqlArgs);
      }

		// Start the new session.
      $User = Gdn::UserModel()->GetByEmail(GetValue('OverwriteEmail', $this->Data));
      if (!$User)
         $User = Gdn::UserModel()->GetByUsername(GetValue('OverwriteEmail', $this->Data));

      $PasswordHash = new Gdn_PasswordHash();
      if ($this->Data('UseCurrentPassword') || $PasswordHash->CheckPassword(GetValue('OverwritePassword', $this->Data), GetValue('Password', $User), GetValue('HashMethod', $User))) {
         Gdn::Session()->Start(GetValue('UserID', $User), TRUE);
      }

		return TRUE;
	}

   public function IsDbSource() {
      return StringBeginsWith($this->ImportPath, 'Db:', TRUE);
   }

	public function LoadUserTable() {
      if (!$this->ImportExists('User'))
         throw new Gdn_UserException(T('The user table was not in the import file.'));
		$UserTableInfo =& $this->Data['Tables']['User'];
		$Result = $this->LoadTable('User', $UserTableInfo['Path']);
      if ($Result)
         $UserTableInfo['Loaded'] = TRUE;

		return $Result;
	}

	public function LoadState() {
		$this->CurrentStep = C('Garden.Import.CurrentStep', 0);
		$this->Data = C('Garden.Import.CurrentStepData', array());
		$this->ImportPath = C('Garden.Import.ImportPath', '');
	}

	public function LoadTables() {
		$LoadedCount = 0;
		foreach($this->Data['Tables'] as $Table => $TableInfo) {
			if(GetValue('Loaded', $TableInfo) || GetValue('Skip', $TableInfo)) {
				$LoadedCount++;
				continue;
			} else {
				$this->Data['CurrentStepMessage'] = $Table;
				$LoadResult = $this->LoadTable($Table, $TableInfo['Path']);
            if ($LoadResult) {
               $this->Data['Tables'][$Table]['Loaded'] = TRUE;
               $LoadedCount++;
            } else {
               break;
            }
			}
			// Make sure the loading isn't taking too long.
			if($this->Timer->ElapsedTime() > $this->MaxStepTime)
				break;
		}
		$Result = $LoadedCount >= count($this->Data['Tables']);
		if($Result)
			$this->Data['CurrentStepMessage'] = '';
		return $Result;
	}

	public function LoadTable($Tablename, $Path) {
      $Type = $this->LoadTableType();
      $Result = TRUE;

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

   protected function _LoadTableOnSameServer($Tablename, $Path) {
		$Tablename = Gdn::Database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;
		$Path = Gdn::Database()->Connection()->quote($Path);

      Gdn::Database()->Query("truncate table $Tablename;");

      $Sql = "load data infile $Path into table $Tablename
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";
      $this->Query($Sql);
   }

   protected function _LoadTableLocalInfile($Tablename, $Path) {
		$Tablename = Gdn::Database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;
		$Path = Gdn::Database()->Connection()->quote($Path);

      Gdn::Database()->Query("truncate table $Tablename;");

      $Sql = "load data local infile $Path into table $Tablename
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";

      // We've got to use the mysql_* functions because PDO doesn't support load data local infile well.
      $dblink = mysql_connect(C('Database.Host'), C('Database.User'), C('Database.Password'), FALSE, 128);
      mysql_select_db(C('Database.Name'), $dblink);
      $Result = mysql_query($Sql, $dblink);
      if ($Result === FALSE) {
         $Ex = new Exception(mysql_error($dblink));
         mysql_close($dblink);
         throw new $Ex;
      }
      mysql_close($dblink);
   }

   protected function _LoadTableWithInsert($Tablename, $Path) {
      // This option could take a while so set the timeout.
      set_time_limit(60*5);

      // Get the column count of the table.
      $St = Gdn::Structure();
      $St->Get(self::TABLE_PREFIX.$Tablename);
      $ColumnCount = count($St->Columns());
      $St->Reset();

      ini_set('auto_detect_line_endings', TRUE);
      $fp = fopen($Path, 'rb');

      // Figure out the current position.
      $fPosition = GetValue('CurrentLoadPosition', $this->Data, 0);
      if ($fPosition == 0) {
         // Skip the header row.
         $Row = self::FGetCSV2($fp);

         $Px = Gdn::Database()->DatabasePrefix.self::TABLE_PREFIX;
         Gdn::Database()->Query("truncate table {$Px}{$Tablename}");
      } else {
         fseek($fp, $fPosition);
      }

      $PDO = Gdn::Database()->Connection();
		$PxTablename = Gdn::Database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;

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
         if (strlen($Inserts) > 0)
            $Inserts .= ',';
         $Inserts .= '('.implode(',', $Row).')';

         if ($Count >= 25) {
            // Insert in chunks.
            $Sql = "insert $PxTablename values $Inserts";
            $this->Query($Sql);
            $Count = 0;
            $Inserts = '';

            // Check for a timeout.
            if($this->Timer->ElapsedTime() > $this->MaxStepTime) {
               // The step's taken too long. Save the file position.
               $Pos = ftell($fp);
               $this->Data['CurrentLoadPosition'] = $Pos;

               $Filesize = filesize($Path);
               if ($Filesize > 0) {
                  $PercentComplete = $Pos / filesize($Path);
                  $this->Data['CurrentStepMessage'] = $Tablename.' ('.round($PercentComplete * 100.0).'%)';
               }

               fclose($fp);
               return FALSE;
            }
         }
      }
      fclose($fp);

      if (strlen($Inserts) > 0) {
         $Sql = "insert $PxTablename values $Inserts";
         $this->Query($Sql);
      }
      unset($this->Data['CurrentLoadPosition']);
      unset($this->Data['CurrentStepMessage']);
      return TRUE;
   }

   public function LoadTableType($Save = TRUE) {
      $Result = GetValue('LoadTableType', $this->Data, FALSE);

      if (is_string($Result))
         return $Result;

      // Create a table to test loading.
      $St = Gdn::Structure();
      $St->Table(self::TABLE_PREFIX.'Test')->Column('ID', 'int')->Set(TRUE, TRUE);

      // Create a test file to load.
      if (!file_exists(PATH_UPLOADS.'/import'))
         mkdir(PATH_UPLOADS.'/import');

      $TestPath = PATH_UPLOADS.'/import/test.txt';
      $TestValue = 123;
      $TestContents = 'ID'.self::NEWLINE.$TestValue.self::NEWLINE;
      file_put_contents($TestPath, $TestContents, LOCK_EX);

      // Try LoadTableOnSameServer.
      try {
         $this->_LoadTableOnSameServer('Test', $TestPath);
         $Value = $this->SQL->Get(self::TABLE_PREFIX.'Test')->Value('ID');
         if ($Value == $TestValue)
            $Result = 'LoadTableOnSameServer';
      } catch (Exception $Ex) {
         $Result = FALSE;
      }

      // Try LoadTableLocalInfile.
      if (!$Result) {
         try {
            $this->_LoadTableLocalInfile('Test', $TestPath);
            $Value = $this->SQL->Get(self::TABLE_PREFIX.'Test')->Value('ID');
            if ($Value == $TestValue)
               $Result = 'LoadTableLocalInfile';
         } catch (Exception $Ex) {
            $Result = FALSE;
         }
      }

      // If those two didn't work then default to LoadTableWithInsert.
      if (!$Result)
         $Result = 'LoadTableWithInsert';

      // Cleanup.
      @unlink($TestPath);
      $St->Table(self::TABLE_PREFIX.'Test')->Drop();

      if ($Save)
         $this->Data['LoadTableType'] = $Result;
      return $Result;
   }

   public function LocalInfileSupported() {
      $Sql = "show variables like 'local_infile'";
      $Data = $this->Query($Sql)->ResultArray();
      if (strcasecmp(GetValueR('0.Value', $Data), 'ON') == 0)
         return TRUE;
      else
         return FALSE;
   }

	public function Overwrite($Overwrite = '', $Email = '', $Password = '') {
		if($Overwrite == '')
			return GetValue('Overwrite', $this->Data);
		$this->Data['Overwrite'] = $Overwrite;
		if(strcasecmp($Overwrite, 'Overwrite') == 0) {
			$this->Data['OverwriteEmail'] = $Email;
			$this->Data['OverwritePassword'] = $Password;
		} else {
			if(isset($this->Data['OverwriteEmail']))
				unset($this->Data['OverwriteEmail']);
			if(isset($this->Data['OverwritePassword']))
				unset($this->Data['OverwritePassword']);
		}
	}

	public function ParseInfoLine($Line) {
		$Info = explode(',', $Line);
		$Result = array();
		foreach($Info as $Item) {
			$PropVal = explode(':', $Item);
			if(array_key_exists(1, $PropVal))
				$Result[trim($PropVal[0])] = trim($PropVal[1]);
			else
				$Result[trim($Item)] = '';
		}

		return $Result;
	}



   /**
    * Process the import tables from the database.
    */
   public function ProcessImportDb() {
      // Grab a list of all of the tables.
      $TableNames = $this->SQL->FetchTables(':_z%');
      if (count($TableNames) == 0) {
         throw new Gdn_UserException('Your database does not contain any import tables.');
      }

      $Tables = array();
      foreach ($TableNames as $TableName) {
         $TableName = StringBeginsWith($TableName, $this->Database->DatabasePrefix, TRUE, TRUE);
         $DestTableName = StringBeginsWith($TableName, 'z', TRUE, TRUE);
         $TableInfo = array('Table' => $DestTableName);

         $ColumnInfos = $this->SQL->FetchTableSchema($TableName);
         $Columns = array();
         foreach ($ColumnInfos as $ColumnInfo) {
            $Columns[GetValue('Name', $ColumnInfo)] = Gdn::Structure()->ColumnTypeString($ColumnInfo);
         }
         $TableInfo['Columns'] = $Columns;
         $Tables[$DestTableName] = $TableInfo;
      }
      $this->Data['Tables'] = $Tables;
      return TRUE;
   }
	
	public function ProcessImportFile() {
      // This one step can take a while so give it more time.
      set_time_limit(60 * 5);

		$Path = $this->ImportPath;
		$Tables = array();

		// Open the import file.
      ini_set('auto_detect_line_endings', TRUE);
		$fpin = gzopen($Path, 'rb');
		$fpout = NULL;

		// Make sure it has the proper header.
		try {
			$Header = $this->GetImportHeader($fpin);
		} catch(Exception $Ex) {
			fclose($fpin);
			throw $Ex;
		}

      $RowCount = 0;
      $LineNumber = 0;
		while (($Line = fgets($fpin)) !== FALSE) {
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
               throw new Gdn_UserException(sprintf(T('Could not parse import file. The problem is near line %s.'), $LineNumber));
            }
				$Table = $TableInfo['Table'];
				$Path = dirname($Path).DS.$Table.'.txt';
				$fpout = fopen($Path, 'wb');

				$TableInfo['Path'] = $Path;
				unset($TableInfo['Table']);

				// Get the column headers from the next line.
				if (($Line = fgets($fpin)) !== FALSE) {
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
		if ($fpout)
			fclose ($fpout);

      if (count($Tables) == 0) {
         throw new Gdn_UserException(T('The import file does not contain any data.'));
      }

		$this->Data['Tables'] = $Tables;

		return TRUE;
	}

	/**
	 * Run the step in the import.
	 * @param int $Step the step to run.
	 * @return mixed Whether the step succeeded or an array of information.
	 */
	public function RunStep($Step = 1) {
      $Started = $this->Stat('Started');
      if($Started === NULL)
         $this->Stat('Started', microtime(TRUE), 'time');

		$Steps = $this->Steps();
      $LastStep = end(array_keys($Steps));
		if(!isset($Steps[$Step]) || $Step > $LastStep) {
			return 'COMPLETE';
		}
		if(!$this->Timer) {
			$NewTimer = TRUE;
			$this->Timer = new Gdn_Timer();
			$this->Timer->Start('');
		}

      // Run a standard step every time.
      if (isset($Steps[0])) {
         call_user_func(array($this, $Steps[0]));
      }

		$Method = $Steps[$Step];
		$Result = call_user_func(array($this, $Method));

      if ($this->GenerateSQL()) {
         $this->SaveSQL($Method);
      }

      $ElapsedTime = $this->Timer->ElapsedTime();
      $this->Stat('Time Spent on Import', $ElapsedTime, 'add');

		if(isset($NewTimer))
			$this->Timer->Finish('');

      if($Result && !array_key_exists($Step + 1, $this->Steps()))
         $this->Stat('Finished', microtime(TRUE), 'time');

		return $Result;
	}

	/**
	 * Run a query, replacing database prefixes.
	 * @param string $Sql The sql to execute.
	 *  - :_z will be replaced by the import prefix.
	 *  - :_ will be replaced by the database prefix.
	 * @param array $Parameters PDO parameters to pass to the query.
	 * @return Gdn_DataSet
	 */
	public function Query($Sql, $Parameters = NULL) {
		$Db = Gdn::Database();

		// Replace db prefixes.
		$Sql = str_replace(array(':_z', ':_'), array($Db->DatabasePrefix.self::TABLE_PREFIX, $Db->DatabasePrefix), $Sql);

      // Figure out the type of the type of the query.
      if (StringBeginsWith($Sql, 'select'))
         $Type = 'select';
      elseif (StringBeginsWith($Sql, 'truncate'))
         $Type = 'truncate';
      elseif (StringBeginsWith($Sql, 'insert'))
         $Type = 'insert';
      elseif (StringBeginsWith($Sql, 'update'))
         $Type = 'update';
      elseif (StringBeginsWith($Sql, 'delete'))
         $Type = 'delete';
      else
         $Type = 'select';

		// Execute the query.
      if (is_array($Parameters))
         $this->SQL->NamedParameters($Parameters);

		$Result = $this->SQL->Query($Sql, $Type);

		//$this->Timer->Split('Sql: '. str_replace("\n", "\n     ", $Sql));

		return $Result;
	}

	public function SaveState() {
		SaveToConfig(array(
		'Garden.Import.CurrentStep' => $this->CurrentStep,
		'Garden.Import.CurrentStepData' => $this->Data,
		'Garden.Import.ImportPath' => $this->ImportPath));
	}

   public function SaveSQL($CurrentStep) {
      $SQLPath = $this->Data('SQLPath');

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
   public function SetCategoryPermissionIDs() {
      // First build a list of category
      $Permissions = $this->SQL->GetWhere('Permission', array('JunctionColumn' => 'PermissionCategoryID', 'JunctionID >' => 0))->ResultArray();
      $CategoryIDs = array();
      foreach ($Permissions as $Row) {
         $CategoryIDs[$Row['JunctionID']] = $Row['JunctionID'];
      }
      
      // Update all of the child categories.
      $Root = CategoryModel::Categories(-1);
      $this->_SetCategoryPermissionIDs($Root, $Root['CategoryID'], $CategoryIDs);
   }
   
   protected function _SetCategoryPermissionIDs($Category, $PermissionID, $IDs) {
      static $CategoryModel;
      if (!isset($CategoryModel))
         $CategoryModel = new CategoryModel();
      
      $CategoryID = $Category['CategoryID'];
      if (isset($IDs[$CategoryID])) {
         $PermissionID = $CategoryID;
      }
      
      if ($Category['PermissionCategoryID'] != $PermissionID) {
         $CategoryModel->SetField($CategoryID, 'PermissionCategoryID', $PermissionID);
      }
      
      $ChildIDs = GetValue('ChildIDs', $Category, array());
      foreach ($ChildIDs as $ChildID) {
         $ChildCategory = CategoryModel::Categories($ChildID);
         if ($ChildCategory)
            $this->_SetCategoryPermissionIDs($ChildCategory, $PermissionID, $IDs);
      }
   }
   
   public function SetRoleDefaults() {
      if (!$this->ImportExists('Role', 'RoleID'))
         return;
      
      $Data = $this->SQL->Get('zRole')->ResultArray();
      
      $RoleDefaults = array(
          'Garden.Registration.DefaultRoles' => array(), 
          'Garden.Registration.ApplicantRoleID' => 0,
          'Garden.Registration.ConfirmEmail' => FALSE,
          'Garden.Registration.ConfirmEmailRole' => '');
      $GuestRoleID = FALSE;
      
      foreach ($Data as $Row) {
         if ($this->ImportExists('Role', '_Default'))
            $Name = $Row['_Default'];
         else
            $Name = GetValue('Name', $Row);
         
         $RoleID = $Row['RoleID'];
         
         if (preg_match('`anonymous`', $Name))
            $Name = 'guest';
         elseif (preg_match('`admin`', $Name))
            $Name = 'administrator';
         
         switch (strtolower($Name)) {
            case 'email':
            case 'confirm email':
            case 'users awaiting email confirmation':
            case 'pending':
               $RoleDefaults['Garden.Registration.ConfirmEmail'] = TRUE;
               $RoleDefaults['Garden.Registration.ConfirmEmailRole'] = $RoleID;
               break;
            case 'member':
            case 'members':
            case 'registered':
            case 'registered users':
               $RoleDefaults['Garden.Registration.DefaultRoles'][] = $RoleID;
               break;
            case 'guest':
            case 'guests':
            case 'unauthenticated':
            case 'unregistered':
            case 'unregistered':
            case 'unregistered / not logged in':
               $GuestRoleID = $RoleID;
               break;
            case 'applicant':
            case 'applicants':
               $RoleDefaults['Garden.Registration.ApplicantRoleID'] = $RoleID;
               break;
         }
      }
      SaveToConfig($RoleDefaults);
      if ($GuestRoleID) {
         $this->SQL->Replace('UserRole', array('UserID' => 0, 'RoleID' => $GuestRoleID), array('UserID' => 0, 'RoleID' => $GuestRoleID));
      }
   }

   public function Stat($Key, $Value = NULL, $Op = 'set') {
      if(!isset($this->Data['Stats']))
         $this->Data['Stats'] = array();

      $Stats =& $this->Data['Stats'];

      if($Value !== NULL) {
         switch(strtolower($Op)) {
            case 'add':
               $Value += GetValue($Key, $Stats, 0);
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
         return GetValue($Key, $Stats, NULL);
      }
   }

	public function Steps() {
		if(strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
         if ($this->IsDbSource())
            return $this->_OverwriteStepsDb;
         else
            return $this->_OverwriteSteps;
		} else
			return $this->_MergeSteps;
	}

	public function &Tables($TableName = '') {
		if($TableName)
			return $this->Data['Tables'][$TableName];
		else
			return $this->Data['Tables'];
	}

   public function UpdateCounts() {
      // This option could take a while so set the timeout.
      set_time_limit(60*5);
      
      // Define the necessary SQL.
      $Sqls = array();

      if(!$this->ImportExists('Discussion', 'LastCommentID'))
         $Sqls['Discussion.LastCommentID'] = $this->GetCountSQL('max', 'Discussion', 'Comment');
      if(!$this->ImportExists('Discussion', 'DateLastComment')) {
         $Sqls['Discussion.DateLastComment'] = "update :_Discussion d
         left join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.DateLastComment = coalesce(c.DateInserted, d.DateInserted)";
      }
      if (!$this->ImportExists('Discussion', 'CountBookmarks')) {
         $Sqls['Discussion.CountBookmarks'] = "update :_Discussion d
            set CountBookmarks = (
               select count(ud.DiscussionID)
               from :_UserDiscussion ud
               where ud.Bookmarked = 1
                  and ud.DiscussionID = d.DiscussionID
            )";
      }

      if(!$this->ImportExists('Discussion', 'LastCommentUserID')) {
         $Sqls['Discussion.LastCommentUseID'] = "update :_Discussion d
         join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.LastCommentUserID = c.InsertUserID";
      }

      if (!$this->ImportExists('Discussion', 'Body')) {
         // Update the body of the discussion if it isn't there.
         if (!$this->ImportExists('Discussion', 'FirstCommentID'))
            $Sqls['Discussion.FirstCommentID'] = $this->GetCountSQL('min', 'Discussion', 'Comment', 'FirstCommentID', 'CommentID');

         $Sqls['Discussion.Body'] = "update :_Discussion d
         join :_Comment c
            on d.FirstCommentID = c.CommentID
         set d.Body = c.Body, d.Format = c.Format";

         if ($this->ImportExists('Media') && Gdn::Structure()->TableExists('Media')) {
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
      
      if(!$this->ImportExists('Discussion', 'CountComments'))
         $Sqls['Discussion.CountComments'] = $this->GetCountSQL('count', 'Discussion', 'Comment');

      if ($this->ImportExists('UserDiscussion') && !$this->ImportExists('UserDiscussion', 'CountComments') && $this->ImportExists('UserDiscussion', 'DateLastViewed')) {
         $Sqls['UserDiscussuion.CountComments'] = "update :_UserDiscussion ud
         set CountComments = (
           select count(c.CommentID)
           from :_Comment c
           where c.DiscussionID = ud.DiscussionID
             and c.DateInserted <= ud.DateLastViewed)";

      }
      
      if ($this->ImportExists('Tag') && $this->ImportExists('TagDiscussion')) {
         $Sqls['Tag.CoundDiscussions'] = $this->GetCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
      }
      
      if ($this->ImportExists('Poll') && Gdn::Structure()->TableExists('Poll')) {
         $Sqls['PollOption.CountVotes'] = $this->GetCountSQL('count', 'PollOption', 'PollVote', 'CountVotes', 'PollOptionID');
         
         $Sqls['Poll.CountOptions'] = $this->GetCountSQL('count', 'Poll', 'PollOption', 'CountOptions', 'PollID');
         $Sqls['Poll.CountVotes'] = $this->GetCountSQL('sum', 'Poll', 'PollOption', 'CountVotes', 'CountVotes', 'PollID');
      }
      
      if ($this->ImportExists('Activity', 'ActivityType')) {
         $Sqls['Activity.ActivityTypeID'] = "
            update :_Activity a
            join :_ActivityType t
               on a.ActivityType = t.Name
            set a.ActivityTypeID = t.ActivityTypeID";
      }

      if ($this->ImportExists('Tag') && $this->ImportExists('TagDiscussion')) {
         $Sqls['Tag.CoundDiscussions'] = $this->GetCountSQL('count', 'Tag', 'TagDiscussion', 'CountDiscussions', 'TagID');
      }
      
      $Sqls['Category.CountDiscussions'] = $this->GetCountSQL('count', 'Category', 'Discussion');
      $Sqls['Category.CountComments'] = $this->GetCountSQL('sum', 'Category', 'Discussion', 'CountComments', 'CountComments');
      if (!$this->ImportExists('Category', 'PermissionCategoryID')) {
         $Sqls['Category.PermissionCategoryID'] = "update :_Category set PermissionCategoryID = -1";
      }
      
      if($this->ImportExists('Conversation') && $this->ImportExists('ConversationMessage')) {
         $Sqls['Conversation.FirstMessageID'] = $this->GetCountSQL('min', 'Conversation', 'ConversationMessage', 'FirstMessageID', 'MessageID');

         if(!$this->ImportExists('Conversation', 'CountMessages'))
            $Sqls['Conversation.CountMessages'] = $this->GetCountSQL('count', 'Conversation', 'ConversationMessage', 'CountMessages', 'MessageID');
         if(!$this->ImportExists('Conversation', 'LastMessageID'))
            $Sqls['Conversation.LastMessageID'] = $this->GetCountSQL('max', 'Conversation', 'ConversationMessage', 'LastMessageID', 'MessageID');

         if (!$this->ImportExists('Conversation', 'DateUpdated'))
            $Sqls['Converstation.DateUpdated'] = "update :_Conversation c join :_ConversationMessage m on c.LastMessageID = m.MessageID set c.DateUpdated = m.DateInserted";

         if($this->ImportExists('UserConversation')) {
            if(!$this->ImportExists('UserConversation', 'LastMessageID')) {
               if($this->ImportExists('UserConversation', 'DateLastViewed')) {
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
            } elseif(!$this->ImportExists('UserConversation', 'DateLastViewed')) {
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
      if (!$this->ImportExists('User', 'DateFirstVisit')) {
         $Sqls['User.DateFirstVisit'] = 'update :_User set DateFirstVisit = DateInserted';
      }
      if (!$this->ImportExists('User', 'CountDiscussions')) {
         $Sqls['User.CountDiscussions'] = $this->GetCountSQL('count', 'User', 'Discussion', 'CountDiscussions', 'DiscussionID', 'UserID', 'InsertUserID');
      }
      if (!$this->ImportExists('User', 'CountComments')) {
         $Sqls['User.CountComments'] = $this->GetCountSQL('count', 'User', 'Comment', 'CountComments', 'CommentID', 'UserID', 'InsertUserID');
      }
      if (!$this->ImportExists('User', 'CountBookmarks')) {
         $Sqls['User.CountBookmarks'] = "update :_User u
            set CountBookmarks = (
               select count(ud.DiscussionID)
               from :_UserDiscussion ud
               where ud.Bookmarked = 1
                  and ud.UserID = u.UserID
            )";
      }
//      if (!$this->ImportExists('User', 'CountUnreadConversations')) {
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
		$CurrentSubstep = GetValue('CurrentSubstep', $this->Data, 0);

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
      for($i = $CurrentSubstep; $i < count($Keys); $i++) {
         $this->Data['CurrentStepMessage'] = sprintf(T('%s of %s'), $CurrentSubstep + 1, count($Keys));
			$Sql = $Sqls[$Keys[$i]];
			$this->Query($Sql);
			if($this->Timer->ElapsedTime() > $this->MaxStepTime) {
				$this->Data['CurrentSubstep'] = $i + 1;
				return FALSE;
			}
		}
		if(isset($this->Data['CurrentSubstep']))
			unset($this->Data['CurrentSubstep']);

		$this->Data['CurrentStepMessage'] = '';

      // Update the url codes of categories.
      if (!$this->ImportExists('Category', 'UrlCode')) {
         $Categories = CategoryModel::Categories();
         $TakenCodes = array();
         
         foreach ($Categories as $Category) {
            $UrlCode = urldecode(Gdn_Format::Url($Category['Name']));
            if (strlen($UrlCode) > 50)
               $UrlCode = $Category['CategoryID'];
            
            if (in_array($UrlCode, $TakenCodes)) {
               $ParentCategory = CategoryModel::Categories($Category['ParentCategoryID']);
               if ($ParentCategory && $ParentCategory['CategoryID'] != -1) {
                  $UrlCode = Gdn_Format::Url($ParentCategory['Name']).'-'.$UrlCode;
               }
               if (in_array($UrlCode, $TakenCodes))
                  $UrlCode = $Category['CategoryID'];
            }

            $TakenCodes[] = $UrlCode;
            Gdn::SQL()->Put(
               'Category',
               array('UrlCode' => $UrlCode),
               array('CategoryID' => $Category['CategoryID']));
         }
      }
      // Rebuild the category tree.
      $CategoryModel = new CategoryModel();
      $CategoryModel->RebuildTree();
      $this->SetCategoryPermissionIDs();

      return TRUE;
	}
}