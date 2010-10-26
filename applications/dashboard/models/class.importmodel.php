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
      1 => 'Initialize',
   	2 => 'ProcessImportFile',
   	3 => 'DefineTables',
   	4 => 'LoadUserTable',
   	5 => 'AuthenticateAdminUser',
   	6 => 'InsertUserTable',
   	7 => 'LoadTables',
   	8 => 'DeleteOverwriteTables',
   	9 => 'InsertTables',
   	10 => 'UpdateCounts',
      11 => 'CustomFinalization',
      12 => 'AddActivity'
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
			$PasswordHash = new Gdn_PasswordHash();
         if (strcasecmp($this->GetPasswordHashMethod(), 'reset') == 0) {
            $Result = TRUE;
         } else {
            $Result = $PasswordHash->CheckPassword($OverwritePassword, GetValue('Password', $Data), $this->GetPasswordHashMethod());
         }
		}
		if(!$Result) {
			$this->Validation->AddValidationResult('Email', T('ErrorCredentials'));
         $this->ErrorType = 'Credentials';
		}
		return $Result;
	}

   public function CustomFinalization() {
      $Imp = $this->GetCustomImportModel();
      if ($Imp !== NULL)
         $Imp->AfterImport();

      return TRUE;
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
            } else {
					$StructureType = $Type;

					if(!$StructureType)
						$StructureType = 'int';

					// This is a new column so it needs to be added to the destination table too.
					$DestStructure->Column($Name, $StructureType, NULL);
					$DestModified = TRUE;
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
            $St->Set(TRUE, TRUE);
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
   		'Discussion', 'Draft', 'Invitation', 'Message', 'Photo', 'Permission', 'Role', 'UserAuthentication',
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
         $this->Data['CurrentStepMessage'] = $Table;
         
			if($Table == 'Permission')
				$Sql = "delete from :_$Table where RoleID <> 0";
			else
				$Sql = "truncate table :_$Table";
			$this->Query($Sql);
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
      if(isset($Post['Overwrite']))
         $this->Data['Overwrite'] = $Post['Overwrite'];
      if(isset($Post['Email']))
         $this->Data['OverwriteEmail'] = $Post['Email'];
      if(isset($Post['Password'])) {
         $this->Data['OverwritePassword'] = $Post['Password'];
      }
   }

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
      // This is just a dummy step so the ajax can get going right away.

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
               $RowCount = $this->_InsertTable($TableName);
            } else {
               switch($TableName) {
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

	protected function _InsertTable($TableName, $Sets = array()) {
		if(!array_key_exists($TableName, $this->Tables()))
			return;

		$TableInfo =& $this->Tables($TableName);
		$Columns = $TableInfo['Columns'];

		// Build the column insert list.
		$Insert = "insert ignore :_$TableName (\n  "
		.implode(",\n  ", array_keys(array_merge($Columns, $Sets)))
		."\n)";
		$From = "from :_z$TableName i";
		$Where = '';

		// Build the select list for the insert.
		$Select = array();
		foreach($Columns as $Column => $X) {
			if(strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
				// The data goes in raw.
				$Select[] = "i.$Column";
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
				$Select[] = "i.$Column";
			}
		}
		// Add the original table to prevent duplicates.
		$PK = $TableName.'ID';
		if(array_key_exists($PK, $Columns)) {
		if(strcasecmp($this->Overwrite(), 'Overwrite') == 0)
				$PK2 = $PK;
			else
				$PK2 = '_NewID';

			$From .= "\nleft join :_$TableName o0\n  on o0.$PK = i.$PK2";
			if($Where)
				$Where .=  "\n  and ";
			else
				$Where = "\nwhere ";
			$Where .= "o0.$PK is null";
		}
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
      if(is_numeric($RowCount) && $RowCount > 0) {
         return (int)$RowCount;
      } else {
         return FALSE;
      }
	}

	public function InsertUserTable() {
		// Delete the current user table.
		$this->Query('truncate table :_User');

		// Load the new user table.
		$UserTableInfo =& $this->Data['Tables']['User'];
		$this->_InsertTable('User', array('HashMethod' => $this->GetPasswordHashMethod()));
		$UserTableInfo['Inserted'] = TRUE;

		// Set the admin user flag.
		$AdminEmail = GetValue('OverwriteEmail', $this->Data);
		$this->Query('update :_User set Admin = 1 where Email = :Email', array(':Email' => $AdminEmail));

      // If doing a password reset, save out the new admin password:
      if (strcasecmp($this->GetPasswordHashMethod(), 'reset') == 0) {
         $PasswordHash = new Gdn_PasswordHash();
         $Hash = $PasswordHash->HashPassword(GetValue('OverwritePassword', $this->Data));

         // Write it out.
         $AdminEmail = GetValue('OverwriteEmail', $this->Data);
         $this->Query('update :_User set Admin = 1, Password = :Hash, HashMethod = "vanilla" where Email = :Email', array(':Hash' => $Hash, ':Email' => $AdminEmail));
      } else {
         // Set the admin user flag.
         $AdminEmail = GetValue('OverwriteEmail', $this->Data);
         $this->Query('update :_User set Admin = 1 where Email = :Email', array(':Email' => $AdminEmail));
      }

		// Authenticate the admin user as the current user.
		$PasswordAuth = Gdn::Authenticator()->AuthenticateWith('password');
		//$PasswordAuth->FetchData($PasswordAuth, array('Email' => GetValue('OverwriteEmail', $this->Data), 'Password' => GetValue('OverwritePassword', $this->Data)));
		$PasswordAuth->Authenticate(GetValue('OverwriteEmail', $this->Data), GetValue('OverwritePassword', $this->Data));
		Gdn::Session()->Start();

		return TRUE;
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
		if($Step > count($Steps)) {
			return 'COMPLETE';
		}
		if(!$this->Timer) {
			$NewTimer = TRUE;
			$this->Timer = new Gdn_Timer();
			$this->Timer->Start('');
		}

		$Method = $Steps[$Step];
		$Result = call_user_func(array($this, $Method));

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

		// Execute the query.
		$Result = $Db->Query($Sql, $Parameters);

		//$this->Timer->Split('Sql: '. str_replace("\n", "\n     ", $Sql));

		return $Result;
	}

	public function SaveState() {
		SaveToConfig(array(
		'Garden.Import.CurrentStep' => $this->CurrentStep,
		'Garden.Import.CurrentStepData' => $this->Data,
		'Garden.Import.ImportPath' => $this->ImportPath));
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
		if(strcasecmp($this->Overwrite(), 'Overwrite') == 0)
			return $this->_OverwriteSteps;
		else
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

      if(!$this->ImportExists('Discussion', 'CountComments'))
         $Sqls['Discussion.CountComments'] = $this->GetCountSQL('count', 'Discussion', 'Comment');
      if(!$this->ImportExists('Discussion', 'LastCommentID'))
         $Sqls['Discussion.LastCommentID'] = $this->GetCountSQL('max', 'Discussion', 'Comment');
      if(!$this->ImportExists('Discussion', 'DateLastComment')) {
         $Sqls['Discussion.DateLastComment'] = "update :_Discussion d
         join :_Comment c
            on d.LastCommentID = c.CommentID
         set d.DateLastComment = c.DateInserted";
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

         $Sqls['Comment.FirstComment.Delete'] = "delete :_Comment c
         from :_Comment c
         inner join :_Discussion d
           on d.FirstCommentID = c.CommentID";
      }

      if ($this->ImportExists('UserDiscussion') && !$this->ImportExists('UserDiscussion', 'CountComments') && $this->ImportExists('UserDiscussion', 'DateLastViewed')) {
         $Sqls['UserDiscussuion.CountComments'] = "update :_UserDiscussion ud
         set CountComments = (
           select count(c.CommentID)
           from :_Comment c
           where c.DiscussionID = ud.DiscussionID
             and c.DateInserted <= ud.DateLastViewed)";

      }

      $Sqls['Category.CountDiscussions'] = $this->GetCountSQL('count', 'Category', 'Discussion');

      if($this->ImportExists('Conversation') && $this->ImportExists('ConversationMessage')) {
         $Sqls['Conversation.FirstMessageID'] = $this->GetCountSQL('min', 'Conversation', 'ConversationMessage', 'FirstMessageID', 'MessageID');

         if(!$this->ImportExists('Conversation', 'CountMessages'))
            $Sqls['Conversation.CountMessages'] = $this->GetCountSQL('count', 'Conversation', 'ConversationMessage', 'CountMessages', 'MessageID');
         if(!$this->ImportExists('Conversation', 'LastMessageID'))
            $Sqls['Conversation.LastMessageID'] = $this->GetCountSQL('max', 'Conversation', 'ConversationMessage', 'LastMessageID', 'MessageID');

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
      if (!$this->ImportExists('User', 'CountUnreadConversations')) {
         $Sqls['User.CountUnreadConversations'] =
            'update :_User u
            set u.CountUnreadConversations = (
              select count(c.ConversationID)
              from :_Conversation c
              inner join :_UserConversation uc
                on c.ConversationID = uc.ConversationID
              where uc.UserID = u.UserID
                and uc.CountReadMessages < c.CountMessages
            )';
      }

      // The updates start here.
		$CurrentSubstep = GetValue('CurrentSubstep', $this->Data, 0);

      if($CurrentSubstep == 0) {
         // Add the FirstCommentID to the discussion table.
         Gdn::Structure()->Table('Discussion')->Column('FirstCommentID', 'int', NULL)->Set(FALSE, FALSE);
      }

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

		// Remove the FirstCommentID from the discussion table.
		Gdn::Structure()->Table('Discussion')->DropColumn('FirstCommentID');
		$this->Data['CurrentStepMessage'] = '';

      // Update the url codes of categories.
      if (!$this->ImportExists('Category', 'UrlCode')) {
         $Categories = Gdn::SQL()->Get('Category')->ResultArray();
         foreach ($Categories as $Category) {
            Gdn::SQL()->Put(
               'Category',
               array('UrlCode' => Gdn_Format::Url($Category['Name'])),
               array('CategoryID' => $Category['CategoryID']));
         }
      }

      return TRUE;
	}
}