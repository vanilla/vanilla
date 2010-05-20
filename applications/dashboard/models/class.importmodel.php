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
   	1 => 'ProcessImportFile',
   	2 => 'DefineTables',
   	3 => 'LoadTables',
   	4 => 'DefineIndexes',
   	5 => 'AssignUserIDs',
   	6 => 'AssignOtherIDs',
   	7 => 'InsertTables',
   	8 => 'UpdateCounts'
	);

	protected $_OverwriteSteps = array(
   	1 => 'ProcessImportFile',
   	2 => 'DefineTables',
   	3 => 'LoadUserTable',
   	4 => 'AuthenticateAdminUser',
   	5 => 'InsertUserTable',
   	6 => 'LoadTables',
   	7 => 'DeleteOverwriteTables',
   	8 => 'InsertTables',
   	9 => 'UpdateCounts'
   );

	/**
	 * @var Gdn_Timer Used for timing various long running methods to break them up into pieces.
	 */
	public $Timer = NULL;

	public function __construct($ImportPath = '') {
		$this->ImportPath = $ImportPath;
		parent::__construct();
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
			$Result = $PasswordHash->CheckPassword($OverwritePassword, GetValue('Password', $Data), $this->GetPasswordHashMethod());

			$Result;
		}
		if(!$Result) {
			$this->Validation->AddValidationResult('Email', T('ErrorCredentials'));
         $this->ErrorType = 'Credentials';
		}
		return $Result;
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
			$DestColumns = $DestStructure->Columns();
			$DestModified = FALSE;

			foreach($Columns as $Name => $Type) {
            if(!$Name)
               throw new Gdn_UserException(sprintf(T('The %s table is not in the correct format.'), $Table));

				if(array_key_exists($Name, $DestColumns))
					$StructureType = $DestStructure->ColumnTypeString($DestColumns[$Name]);
				else {
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
				->Column('_Action', array('Insert', 'Update'));
			}

         try {
            $St->Set(TRUE, TRUE);
            if($DestModified)
               $DestStructure->Set();
         } catch(Exception $Ex) {
            // Since these exceptions are likely caused by a faulty import file they should be considered user exceptions.
            throw new Gdn_UserException(sprintf(T('There was an error while trying to create the %s table.'), $Table), $Ex);
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
		foreach(GetValue('Tables', $this->Data, array()) as $Table => $TableInfo) {
			$Path = GetValue('Path', $TableInfo, '');
			if(file_exists($Path))
				unlink($Path);
		}

		// Delete the import file.
		if(GetValue('FileUploaded', $this->Data) && $this->ImportPath && file_exists($this->ImportPath)) {
			unlink($this->ImportPath);
		}
	}

	/**
	 * Remove the data from the appropriate tables when we are overwriting the forum.
	 */
	public function DeleteOverwriteTables() {
		$Tables = array('Activity', 'Category', 'Comment', 'CommentWatch', 'Conversation', 'ConversationMessage',
   		'Discussion', 'Draft', 'Invitation', 'Message', 'Photo', 'Permission', 'Role', 'UserAuthentication',
   		'UserConversation', 'UserDiscussion', 'UserMeta', 'UserRole');

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

   public function ToPost(&$Post) {
      $D = $this->Data;
      $Post['Overwrite'] = GetValue('Overwrite', $D, 'Overwrite');
      $Post['Email'] = GetValue('OverwriteEmail', $D, '');
      $Post['Password'] = GetValue('OverwritePassword', $D, '');
   }

	public function GetImportHeader($fpin = NULL) {
		$Header = GetValue('Header', $this->Data);
		if($Header)
			return $Header;

		if(is_null($fpin)) {
			if(!$this->ImportPath || !file_exists($this->ImportPath))
				return array();
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
		return $Header;
	}

	public function GetPasswordHashMethod() {
		$Source = GetValue('Source', $this->GetImportHeader());
		if(!$Source)
			return 'Unknown';
		if(substr_compare('Vanilla', $Source, 0, 7, FALSE) == 0)
			return 'Vanilla';
		if(substr_compare('vBulletin', $Source, 0,  9, FALSE) == 0)
			return 'vBulletin';
		return 'Unknown';
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
				$this->Data['CurrentStepMessage'] = $TableName;

            if(strcasecmp($this->Overwrite(), 'Overwrite') == 0) {
               $RowCount = $this->_InsertTable($TableName);
            } else {
               switch($TableName) {
                  case 'UserDiscussion':
                     $Sql = "insert :_UserDiscussion ( UserID, DiscussionID, DateLastViewed, Bookmarked )
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
                     $Sql = "insert :_UserMeta ( UserID, Name, Value )
                           select zUserID._NewID, i.Name, max(i.Value) as Value
                           from :_zUserMeta i
                           left join GDN_zUser zUserID
                             on i.UserID = zUserID.UserID
                           left join :_UserMeta um
                             on zUserID._NewID = um.UserID and i.Name = um.Name
                           where um.UserID is null
                           group by zUserID._NewID, i.Name";
                     $this->Query($Sql);
                     break;
                  case 'UserRole':
                     $Sql = "insert :_UserRole ( UserID, RoleID )
                           select distinct zUserID._NewID, zRoleID._NewID
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
		$Insert = "insert :_$TableName (\n  "
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

		// Authenticate the admin user as the current user.
		$Auth = new Gdn_PasswordAuthenticator();
		$Auth->Authenticate(array('Email' => GetValue('OverwriteEmail', $this->Data), 'Password' => GetValue('OverwritePassword', $this->Data)));
		Gdn::Session()->Start($Auth);

		return TRUE;
	}

	public function LoadUserTable() {
		$UserTableInfo =& $this->Data['Tables']['User'];
		$this->LoadTable('User', $UserTableInfo['Path']);
		$UserTableInfo['Loaded'] = TRUE;

		return TRUE;
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
				$this->LoadTable($Table, $TableInfo['Path']);
				$this->Data['Tables'][$Table]['Loaded'] = TRUE;
				$LoadedCount++;
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
		$Path = Gdn::Database()->Connection()->quote($Path);
		$Tablename = Gdn::Database()->DatabasePrefix.self::TABLE_PREFIX.$Tablename;

		Gdn::Database()->Query("truncate table $Tablename;");

		$Sql = "load data infile $Path into table $Tablename
         character set utf8
         columns terminated by ','
         optionally enclosed by '\"'
         escaped by '\\\\'
         lines terminated by '\\n'
         ignore 1 lines";

		$this->Query($Sql);

		return TRUE;
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
		$Path = $this->ImportPath;
		$Tables = array();

		// Open the import file.
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
		while(($Line = fgets($fpin)) !== FALSE) {
			if($Line == "\n") {
				if($fpout) {
					// We are in a table so close it off.
					fclose($fpout);
					$fpout = 0;
				}
			} elseif($fpout) {
				// We are in a table so dump the line.
				fputs($fpout, $Line);
			} elseif(substr_compare(self::COMMENT, $Line, 0, strlen(self::COMMENT)) == 0) {
				// This is a comment line so do nothing.
			} else {
				// This is the start of a table.
				$TableInfo = $this->ParseInfoLine($Line);
				$Table = $TableInfo['Table'];
				$Path = dirname($Path).DS.$Table.'.txt';
				$fpout = fopen($Path, 'wb');

				$TableInfo['Path'] = $Path;
				unset($TableInfo['Table']);

				// Get the column headers from the next line.
				if(($Line = fgets($fpin)) !== FALSE) {
					fputs($fpout, $Line);
					$Columns = $this->ParseInfoLine($Line);
					$TableInfo['Columns'] = $Columns;

					$Tables[$Table] = $TableInfo;
				}
			}
		}
		gzclose($fpin);
		if($fpout)
			gzclose($fpout);
		$this->Data['Tables'] = $Tables;

		return TRUE;
	}

	/**
	 * Run the step in the import.
	 * @param int $Step the step to run.
	 * @return mixed Whether the step succeeded or an array of information.
	 */
	public function RunStep($Step = 1) {
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
      $this->Stat('Time Importing', $ElapsedTime, 'add');

		if(isset($NewTimer))
			$this->Timer->Finish('');

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
		// Define the necessary SQL.
		$StepSql = array(
		// Set basic counts.
      'Basic Discussion Counts' =>
		"update :_Discussion d set
      LastCommentID = (select max(c.CommentID) from :_Comment c where c.DiscussionID = d.DiscussionID),
      CountComments = (select count(c.CommentID) from :_Comment c where c.DiscussionID = d.DiscussionID),
      DateLastComment = (select max(c.DateInserted) from :_Comment c where c.DiscussionID = d.DiscussionID)",

		// Set the body of the first comment when the forum doesn't put it in the discussion.
		'Discussion Bodies' =>
      "update :_Discussion d
      inner join :_Comment c
        on c.DiscussionID = d.DiscussionID
      inner join (
        select min(c2.CommentID) as CommentID
        from :_Comment c2
        group by c2.DiscussionID
      ) c2
        on c.CommentID = c2.CommentID
      set
        d.Body = c.Body,
        d.Format = c.Format,
        d.FirstCommentID = c.CommentID
      where d.Body is null",

		// Remove the first comment.
		'FirstComment' =>
      "delete :_Comment c
      from :_Comment c
      inner join :_Discussion d
        on d.FirstCommentID = c.CommentID",

		// Set the last comment user.
      'LastCommentUserID' =>
		"update :_Discussion d
      join :_Comment c
        on d.LastCommentID = c.CommentID
      set d.LastCommentUserID = c.InsertUserID",

		// Set the category counts.
      'Category Counts' =>
		"update :_Category c set
      c.CountDiscussions = (select count(d.DiscussionID) from :_Discussion d where d.CategoryID = c.CategoryID)");

		// Add the FirstCommentID to the discussion table.
		Gdn::Structure()->Table('Discussion')->Column('FirstCommentID', 'int', NULL)->Set(FALSE, FALSE);

		// Execute the SQL.
		$CurrentSubstep = GetValue('CurrentSubstep', $this->Data, 0);
      $Keys = array_keys($StepSql);
      for($i = $CurrentSubstep; $i < count($Keys); $i++) {
         $this->Data['CurrentStepMessage'] = $Keys[$i];
			$Sql = $StepSql[$Keys[$i]];
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
      return TRUE;
	}
}