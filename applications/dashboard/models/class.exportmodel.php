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
 * Object for exporting other database structures into a format that can be imported.
 * @see Gdn_ImportModel
 */
class ExportModel {
	const COMMENT = '//';
	const DELIM = ',';
	const ESCAPE = '\\';
	const NEWLINE = "\n";
	const NULL = '\N';
	const QUOTE = '"';
	
	/**
	 * Create the export file and begin the export.
	 * @param string $Path The path to the export file.
	 * @param string $Source The source program that created the export. This may be used by the import routine to do additional processing.
	 */
	public function BeginExport($Path, $Source = '') {
		$this->BeginTime = microtime(TRUE);
		$TimeStart = list($sm, $ss) = explode(' ', microtime());
		
		if($this->UseCompression && function_exists('gzopen'))
			$fp = gzopen($Path, 'wb');
		else
			$fp = fopen($Path, 'wb');
		$this->_File = $fp;
		
		fwrite($fp, 'Vanilla Export: '.$this->Version());
		if($Source)
			fwrite($fp, self::DELIM.' Source: '.$Source);
		fwrite($fp, self::NEWLINE.self::NEWLINE);
		$this->Comment('Exported Started: '.date('Y-m-d H:i:s'));
	}
	
	/**
	 * Write a comment to the export file.
	 * @param string $Message The message to write.
	 * @param bool $Echo Whether or not to echo the message in addition to writing it to the file.
	 */
	public function Comment($Message, $Echo = TRUE) {
		fwrite($this->_File, self::COMMENT.' '.str_replace(self::NEWLINE, self::NEWLINE.self::COMMENT.' ', $Message).self::NEWLINE);
		if($Echo)
			echo $Message, "\n";
	}
	
	/**
	 * End the export and close the export file. This method must be called if BeginExport() has been called or else the export file will not be closed.
	 */
	public function EndExport() {
		$this->EndTime = microtime(TRUE);
		$this->TotalTime = $this->EndTime - $this->BeginTime;
		$m = floor($this->TotalTime / 60);
		$s = $this->TotalTime - $m * 60;
		
		$this->Comment('Exported Completed: '.date('Y-m-d H:i:s').sprintf(', Elapsed Time: %02d:%02.2f', $m, $s));
		
		if($this->UseCompression && function_exists('gzopen'))
			gzclose($this->_File);
		else
			fclose($this->_File);
	}
	
	protected $_File = NULL;
	
	protected $_PDO = NULL;
	/**
	 * Gets or sets the PDO connection to the database.
	 * @param mixed $DsnOrPDO One of the following:
	 *  - <b>String</b>: The dsn to the database.
	 *  - <b>PDO</b>: An existing connection to the database.
	 *  - <b>Null</b>: The PDO connection will not be set.
	 *  @param string $Username The username for the database if a dsn is specified.
	 *  @param string $Password The password for the database if a dsn is specified.
	 *  @return PDO The current database connection.
	 */
	public function PDO($DsnOrPDO = NULL, $Username = NULL, $Password = NULL) {
		if(!is_null($DsnOrPDO)) {
			if($DsnOrPDO instanceof PDO)
				$this->_PDO = $DsnOrPDO;
			else {
				$this->_PDO = new PDO($DsnOrPDO, $Username, $Password);
				if(strncasecmp($DsnOrPDO, 'mysql', 5) == 0)
					$this->_PDO->exec('set names utf8');
			}
		}
		return $this->_PDO;
	}
	
	/**
	 * Export a table to the export file.
	 * @param string $TableName the name of the table to export. This must correspond to one of the accepted vanilla tables.
	 * @param mixed $Query The query that will fetch the data for the export this can be one of the following:
	 *  - <b>String</b>: Represents a string of sql to execute.
	 *  - <b>PDOStatement</b>: Represents an already executed query resultset.
	 *  - <b>Array</b>: Represents an array of associative arrays or objects containing the data in the export.
	 *  @param array $Mappings Specifies mappings, if any, between the source and the export where the keys represent the export columns and the values represent the source columns.
	 *  For a list of the export tables and columns see $this->Structure().
	 */
	public function ExportTable($TableName, $Query, $Mappings = array()) {
		$fp = $this->_File;
		
		// Make sure the table is valid for export.
		if(!array_key_exists($TableName, $this->_Structures)) {
			$this->Comment("Error: $TableName is not a valid export."
				." The valid tables for export are ". implode(", ", array_keys($this->_Structures)));
			fwrite($fp, self::NEWLINE);
			return;
		}
		$Structure = $this->_Structures[$TableName];
		
		// Start with the table name.
		fwrite($fp, 'Table: '.$TableName.self::NEWLINE);
		
		// Get the data for the query.
		if(is_string($Query)) {
			$Query = str_replace(':_', $this->Prefix, $Query); // replace prefix.
			$Data = $this->PDO()->query($Query, PDO::FETCH_ASSOC);
		} elseif($Query instanceof PDOStatement) {
			$Data = $Query;
		}
		
		// Set the search and replace to escape strings.
		$EscapeSearch = array(self::ESCAPE, self::DELIM, self::NEWLINE, self::QUOTE); // escape must go first
		$EscapeReplace = array(self::ESCAPE.self::ESCAPE, self::ESCAPE.self::DELIM, self::ESCAPE.self::NEWLINE, self::ESCAPE.self::QUOTE);
		
		// Write the column header.
		fwrite($fp, implode(self::DELIM, array_keys($Structure)).self::NEWLINE);
		
		// Loop through the data and write it to the file.
		foreach($Data as $Row) {
			$Row = (array)$Row;
			$First = TRUE;
			
			// Loop through the columns in the export structure and grab their values from the row.
			$ExRow = array();
			foreach($Structure as $Field => $Type) {
				// Get the value of the export.
				if(array_key_exists($Field, $Row)) {
					// The column has an exact match in the export.
					$Value = $Row[$Field];
				} elseif(array_key_exists($Field, $Mappings)) {
					// The column is mapped.
					$Value = $Row[$Mappings[$Field]];
				} else {
					$Value = NULL;
				}
				// Format the value for writing.
				if(is_null($Value)) {
					$Value = self::NULL;
				} elseif(is_numeric($Value)) {
					// Do nothing, formats as is.
				} elseif(is_string($Value)) {
					//if(mb_detect_encoding($Value) != 'UTF-8')
					//   $Value = utf8_encode($Value);
					
					$Value = self::QUOTE
						.str_replace($EscapeSearch, $EscapeReplace, $Value)
						.self::QUOTE;
				} elseif(is_bool($Value)) {
					$Value = $Value ? 1 : 0;
				} else {
					// Unknown format.
					$Value = self::NULL;
				}
				
				$ExRow[] = $Value;
			}
			// Write the data.
			fwrite($fp, implode(self::DELIM, $ExRow));
			// End the record.
			fwrite($fp, self::NEWLINE);
		}
		
		// Write an empty line to signify the end of the table.
		fwrite($fp, self::NEWLINE);
		
		if($Data instanceof PDOStatement)
			$Data->closeCursor();
	}
	
	/**
	 * @var string The database prefix. When you pass a sql string to ExportTable() it will replace occurances of :_ with this property.
	 * @see vnExport::ExportTable()
	 */
	public $Prefix = '';
	
	protected $_Structures = array(
		'Category' => array('CategoryID' => 'int', 'Name' => 'varchar(30)', 'Description' => 'varchar(250)', 'ParentCategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'),
		'Comment' => array('CommentID' => 'int', 'DiscussionID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Format' => 'varchar(20)', 'Body' => 'text', 'Score' => 'float'),
		'Conversation' => array('ConversationID' => 'int', 'FirstMessageID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'),
		'ConversationMessage' => array('MessageID' => 'int', 'ConversationID' => 'int', 'Body' => 'text', 'InsertUserID' => 'int', 'DateInserted' => 'datetime'),
		'Discussion' => array('DiscussionID' => 'int', 'Name' => 'varchar(100)', 'CategoryID' => 'int', 'Body' => 'text', 'Format' => 'varchar(20)', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Score' => 'float', 'Announce' => 'tinyint', 'Closed' => 'tinyint', 'Announce' => 'tinyint'),
		'Role' => array('RoleID' => 'int', 'Name' => 'varchar(100)', 'Description' => 'varchar(200)'),
		'UserConversation' => array('UserID' => 'int', 'ConversationID' => 'int', 'LastMessageID' => 'int'),
		'User' => array('UserID' => 'int', 'Name' => 'varchar(20)', 'Email' => 'varchar(200)', 'Password' => 'varbinary(34)', 'Gender' => array('u', 'm', 'f'), 'Score' => 'float'),
		'UserRole' => array('UserID' => 'int', 'RoleID' => 'int')
		);
	/**
	 * Returns an array of all the expected export tables and expected columns in the exports.
	 * When exporting tables using ExportTable() all of the columns in this structure will always be exported in the order here, regardless of how their order in the query.
	 * @return array
	 * @see vnExport::ExportTable()
	 */
	public function Structures() {
		return $this->_Structures;
	}
	
	/**
	 * @var bool Whether or not to use compression when creating the file.
	 */
	public $UseCompression = TRUE;
	
	/**
	 * Returns the version of export file that will be created with this export.
	 * The version is used when importing to determine the format of this file.
	 * @return string
	 */
	public function Version() {
		return '1.0';
	}
}
