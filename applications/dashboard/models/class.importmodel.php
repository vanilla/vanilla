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
 * Object for importing files created with ExportModel.
 * @see ImportModel
 */
class ImportModel {
	const COMMENT = '//';
	const DELIM = ',';
	const ESCAPE = '\\';
	const ID_PADDING = 1000; // padding to add to IDs that are incremented
	const NEWLINE = "\n";
	const NULL = '\N';
	const TABLE_PREFIX = 'z';
	const QUOTE = '"';
	
	public $Data = array();
	
	public $ImportPath = '';

	public $MaxStepTime = 10000;
	
	public $Steps = array(
		1 => 'SplitImportFile',
		2 => 'DefineTables',
		3 => 'LoadTables',
		4 => 'DefineIndexes',
		5 => 'AssignUserIDs',
		6 => 'AssignOtherIDs',
		7 => 'InsertTables',
		8 => 'UpdateCounts'
		);
	
	public $Structures = array(
		'Category' => array('CategoryID' => 'int', 'Name' => 'varchar(30)', 'Description' => 'varchar(250)', 'ParentCategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'),
		'Comment' => array('CommentID' => 'int', 'DiscussionID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Format' => 'varchar(20)', 'Body' => 'text', 'Score' => 'float'),
		'Discussion' => array('DiscussionID' => 'int', 'Name' => 'varchar(100)', 'CategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Score' => 'float', 'Announce' => 'tinyint', 'Closed' => 'tinyint', 'Announce' => 'tinyint'),
		'Role' => array('RoleID' => 'int', 'Name' => 'varchar(100)', 'Description' => 'varchar(200)'),
		'User' => array('UserID' => 'int', 'Name' => 'varchar(20)', 'Email' => 'varchar(200)', 'Password' => 'varbinary(34)', 'Gender' => array('m', 'f'), 'Score' => 'float'),
		'UserRole' => array('UserID' => 'int', 'RoleID' => 'int')
		);
	
	/**
	 * @var Gdn_Timer
	 */
	public $Timer = NULL;
	
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
		
		// There still might be users that have an overlapping usernames which must be changed.
		// Append a random suffic to the new username.
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
		if(!array_key_exists($TableName, $this->Data['Tables']))
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
	
	public function DefineTables() {
		$St = Gdn::Structure();
		
		foreach($this->Structures as $Table => $Columns) {
			$St->Table(self::TABLE_PREFIX.$Table);
			
			foreach($Columns as $Name => $Type) {
				$St->Column($Name, $Type, NULL);
			}
			// Add a new ID column.
			if(array_key_exists($Table.'ID', $Columns)) {
				$St
					->Column('_NewID', $Columns[$Table.'ID'], NULL)
					->Column('_Action', array('Insert', 'Update'));
				
			}
			
			$St->Set(TRUE, TRUE);
		}
		return TRUE;
	}
	
	public function DefineIndexes() {
		$St = Gdn::Structure();
		
		foreach($this->Structures as $Table => $Columns) {
			$St->Table(self::TABLE_PREFIX.$Table);
			
			// Check to index the primary key.
			$Col = $Table.'ID';
			if(array_key_exists($Col, $Columns))
				$St->Column($Col, $Columns[$Col], NULL, 'index');
				
			if($Table == 'User') {
				$St
					->Column('Name', $Columns['Name'], NULL, 'index')
					->Column('Email', $Columns['Email'], NULL, 'index')
					->Column('_NewID', 'int', NULL, 'index');
			}
		
			if(count($St->Columns()) > 0)
				$St->Set();
		}
		return TRUE;
	}
	
	public function InsertTables() {
		$InsertedCount = 0;
		
		foreach($this->Structures as $TableName => $Columns) {
			if(GetValue('Inserted', GetValue($TableName, $this->Data['Tables'], array()))) {
				$InsertedCount++;
			} else {
				$this->Data['CurrentStepMessage'] = $TableName;
				
				switch($TableName) {
					case 'UserRole':
						$Sql = "insert :_UserRole ( UserID, RoleID )
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
						$this->_InsertTable($TableName);
				}
				
				$this->Data['Tables'][$TableName]['Inserted'] = TRUE;
				// Make sure the loading isn't taking too long.
				if($this->Timer->ElapsedTime() > $this->MaxStepTime)
					break;
			}
		}
		
		$Result = $InsertedCount == count($this->Structures);
		if($Result)
			$this->Data['CurrentStepMessage'] = '';
		return $Result;
	}
	
	protected function _InsertTable($TableName) {
		if(!array_key_exists($TableName, $this->Data['Tables']))
			return;
		
		$Structure = $this->Structures[$TableName];
		
		// Build the column insert list.
		$Insert = "insert :_$TableName (\n  "
			.implode(",\n  ", array_keys($Structure))
			."\n)";
		$From = "from :_z$TableName i";
		$Where = '';
		
		// Build the select list for the insert.
		$Select = array();
		foreach($Structure as $Column => $Type) {
			if($Column == $TableName.'ID') {
				// This is the primary key.
				$Select[] = "i._NewID as $Column";
				$Where = "\nwhere i._Action = 'Insert'";
			} elseif(substr_compare($Column, 'ID', -2, 2) == 0) {
				// This is an ID field. Check for a join.
				foreach($this->Structures as $StructureTableName => $StructureColumns) {
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
		$From .= "\nleft join :_$TableName o0\n  on i._NewID = o0.$PK";
		$Where .= " and o0.$PK is null";
		
		// Build the sql statement.
		$Sql = $Insert
			."\nselect\n  ".implode(",\n  ", $Select)
			."\n".$From
			.$Where;
			
		$this->Query($Sql);
	}
	
	public function LoadTables() {
		$LoadedCount = 0;
		foreach($this->Data['Tables'] as $Table => $TableInfo) {
			if(GetValue('Loaded', $TableInfo)) {
				$LoadedCount++;
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
		if(!array_key_exists($Tablename, $this->Structures))
			throw new Exception("The table \"$Tablename\" is not a valid import table.");
		
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
	
	public function ParseInfoLine($Line) {
		$Info = explode(',', $Line);
		$Result = array();
		foreach($Info as $Item) {
			$PropVal = explode(':', $Item);
			if(array_key_exists(1, $PropVal))
				$Result[trim($PropVal[0])] = trim($PropVal[1]);
		}
		
		return $Result;
	}
	
	/**
	 * Run the step in the import.
	 * @param int $Step the step to run.
	 * @return mixed Whether the step succeeded or an array of information.
	 */
	public function RunStep($Step = 1) {
		if($Step > count($this->Steps)) {
			return 'COMPLETE';
		}
		if(!$this->Timer) {
			$NewTimer = TRUE;
			$this->Timer = new Gdn_Timer();
			$this->Timer->Start('');
		}
		
		$Method = $this->Steps[$Step];
		$Result = call_user_method($Method, $this);
		
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

	public function SplitImportFile() {
		$Path = $this->ImportPath;
		$Tables = array();
		
		// Open the import file.
		$fpin = gzopen($Path, 'rb');
		$fpout = NULL;
		
		// Make sure it has the proper header.
		$Header = fgets($fpin);
		if(!$Header || strlen($Header) < 7 || substr_compare('Vanilla', $Header, 0, 7) != 0) {
			throw new Exception('The import file is not in the correct format.');
		}
		$Header = $this->ParseInfoLine($Header);
		
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
				$Tables[$Table] = $TableInfo;
			}
		}
		gzclose($fpin);
		if($fpout)
			gzclose($fpout);
		$this->Data['Tables'] = $Tables;
		
		return TRUE;
	}
	
	public function UpdateCounts() {
		$StepSql = array(
			"update :_Discussion d set
FirstCommentID = (select min(c.CommentID) from :_Comment c where c.DiscussionID = d.DiscussionID),
LastCommentID = (select max(c.CommentID) from :_Comment c where c.DiscussionID = d.DiscussionID),
CountComments = (select count(c.CommentID) from :_Comment c where c.DiscussionID = d.DiscussionID),
DateLastComment = (select max(c.DateInserted) from :_Comment c where c.DiscussionID = d.DiscussionID)",

			"update :_Discussion d
join :_Comment c
  on d.LastCommentID = c.CommentID
set d.LastCommentUserID = c.InsertUserID",

			"update :_Category c set
c.CountDiscussions = (select count(d.DiscussionID) from :_Discussion d where d.CategoryID = c.CategoryID)");
		
		$CurrentSubstep = GetValue('CurrentSubstep', $this->Data, 0);
		for($i = $CurrentSubstep; $i < count($StepSql); $i++) {
			$Sql = $StepSql[$i];
			$this->Query($Sql);
			if($this->Timer->ElapsedTime() > $this->MaxStepTime) {
				$this->Data['CurrentSubstep'] = $i + 1;
				return FALSE;
			}
		}
		if(isset($this->Data['CurrentSubstep']))
			unset($this->Data['CurrentSubstep']);
		return TRUE;
	}
}