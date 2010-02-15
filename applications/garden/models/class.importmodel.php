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
 * Object for importing files created with Gdn_ExportModel.
 * @see Gdn_ImportModel
 */
class Gdn_ImportModel {
	const COMMENT = '//';
	const DELIM = ',';
	const ESCAPE = '\\';
	const NEWLINE = "\n";
	const NULL = '\N';
	const TABLE_PREFIX = 'z';
	const QUOTE = '"';
	
	public $Data = array();
	
	public $Structures = array(
		'Category' => array('CategoryID' => 'int', 'Name' => 'varchar(30)', 'Description' => 'varchar(250)', 'ParentCategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'),
		'Comment' => array('CommentID' => 'int', 'DiscussionID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Format' => 'varchar(20)', 'Body' => 'text', 'Score' => 'float'),
		'Discussion' => array('DiscussionID' => 'int', 'Name' => 'varchar(100)', 'CategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Score' => 'float', 'Closed' => 'tinyint', 'Announce' => 'tinyint'),
		'Role' => array('RoleID' => 'int', 'Name' => 'varchar(100)', 'Description' => 'varchar(200)'),
		'User' => array('UserID' => 'int', 'Name' => 'varchar(20)', 'Email' => 'varchar(200)', 'Password' => 'varbinary(34)', 'Gender' => array('m', 'f'), 'Score' => 'float'),
		'UserRole' => array('UserID' => 'int', 'RoleID' => 'int')
		);
	
	public function DefineTables() {
		$St = Gdn::Structure();
		
		foreach($this->Structures as $Table => $Columns) {
			$St->Table(self::TABLE_PREFIX.$Table);
			
			foreach($Columns as $Name => $Type) {
				$St->Column($Name, $Type, NULL);
			}
			
			$St->Set();
		}
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
ignore 1 lines;";
		
		Gdn::Database()->Query($Sql);
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

	public function SplitFile($Path) {
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
		
		while(!feof($fpin)) {
			$Line = fgets($fpin);
			
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
	}
}