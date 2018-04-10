<?php
/**
 * For exporting other database structures into a format that can be imported.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Export handler.
 *
 * @see Gdn_ImportModel
 */
class ExportModel {

    /** Comment character to use in export. */
    const COMMENT = '//';

    /** Delimiter character to use in export. */
    const DELIM = ',';

    /** Escape character to use in export. */
    const ESCAPE = '\\';

    /** Newline character to use in export. */
    const NEWLINE = "\n";

    /** Null character to use in export. */
    const NULL = '\N';

    /** Quote character to use in export. */
    const QUOTE = '"';

    /** @var object|null File pointer. */
    protected $_File = null;

    /** @var object|null PDO connection. */
    protected $_PDO = null;

    /** @var bool Whether or not to use compression when creating the file.  */
    public $UseCompression = true;

    /** @var string The database prefix that exportTable() it will replace :_ with in a query string. */
    public $Prefix = '';

    /** @var array Data format we support exporting. */
    protected $_Structures = [
        'Category' => ['CategoryID' => 'int', 'Name' => 'varchar(30)', 'Description' => 'varchar(250)', 'ParentCategoryID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'],
        'Comment' => ['CommentID' => 'int', 'DiscussionID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Format' => 'varchar(20)', 'Body' => 'text', 'Score' => 'float'],
        'Conversation' => ['ConversationID' => 'int', 'FirstMessageID' => 'int', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int'],
        'ConversationMessage' => ['MessageID' => 'int', 'ConversationID' => 'int', 'Body' => 'text', 'InsertUserID' => 'int', 'DateInserted' => 'datetime'],
        'Discussion' => ['DiscussionID' => 'int', 'Name' => 'varchar(100)', 'CategoryID' => 'int', 'Body' => 'text', 'Format' => 'varchar(20)', 'DateInserted' => 'datetime', 'InsertUserID' => 'int', 'DateUpdated' => 'datetime', 'UpdateUserID' => 'int', 'Score' => 'float', 'Announce' => 'tinyint', 'Closed' => 'tinyint', 'Announce' => 'tinyint'],
        'Role' => ['RoleID' => 'int', 'Name' => 'varchar(100)', 'Description' => 'varchar(200)'],
        'UserConversation' => ['UserID' => 'int', 'ConversationID' => 'int', 'LastMessageID' => 'int'],
        'User' => ['UserID' => 'int', 'Name' => 'varchar(20)', 'Email' => 'varchar(200)', 'Password' => 'varbinary(34)', 'Gender' => ['u', 'm', 'f'], 'Score' => 'float'],
        'UserRole' => ['UserID' => 'int', 'RoleID' => 'int']
    ];

    /**
     * Create the export file and begin the export.
     *
     * @param string $path The path to the export file.
     * @param string $source The source program that created the export. This may be used by the import routine to do additional processing.
     */
    public function beginExport($path, $source = '') {
        $this->BeginTime = microtime(true);
        $timeStart = list($sm, $ss) = explode(' ', microtime());

        if ($this->UseCompression && function_exists('gzopen')) {
            $fp = gzopen($path, 'wb');
        } else {
            $fp = fopen($path, 'wb');
        }
        $this->_File = $fp;

        fwrite($fp, 'Vanilla Export: '.$this->version());
        if ($source) {
            fwrite($fp, self::DELIM.' Source: '.$source);
        }
        fwrite($fp, self::NEWLINE.self::NEWLINE);
        $this->comment('Exported Started: '.date('Y-m-d H:i:s'));
    }

    /**
     * Write a comment to the export file.
     *
     * @param string $message The message to write.
     * @param bool $echo Whether or not to echo the message in addition to writing it to the file.
     */
    public function comment($message, $echo = true) {
        fwrite($this->_File, self::COMMENT.' '.str_replace(self::NEWLINE, self::NEWLINE.self::COMMENT.' ', $message).self::NEWLINE);
        if ($echo) {
            echo $message, "\n";
        }
    }

    /**
     * End the export and close the export file.
     *
     * This method must be called if beginExport() has been called or else the export file will not be closed.
     */
    public function endExport() {
        $this->EndTime = microtime(true);
        $this->TotalTime = $this->EndTime - $this->BeginTime;
        $m = floor($this->TotalTime / 60);
        $s = $this->TotalTime - $m * 60;

        $this->comment('Exported Completed: '.date('Y-m-d H:i:s').sprintf(', Elapsed Time: %02d:%02.2f', $m, $s));

        if ($this->UseCompression && function_exists('gzopen')) {
            gzclose($this->_File);
        } else {
            fclose($this->_File);
        }
    }

    /**
     * Gets or sets the PDO connection to the database.
     *
     * @param mixed $dsnOrPDO One of the following:
     *  - <b>String</b>: The dsn to the database.
     *  - <b>PDO</b>: An existing connection to the database.
     *  - <b>Null</b>: The PDO connection will not be set.
     * @param string $username The username for the database if a dsn is specified.
     * @param string $password The password for the database if a dsn is specified.
     * @return PDO The current database connection.
     */
    public function pDO($dsnOrPDO = null, $username = null, $password = null) {
        if (!is_null($dsnOrPDO)) {
            if ($dsnOrPDO instanceof PDO) {
                $this->_PDO = $dsnOrPDO;
            } else {
                $this->_PDO = new PDO($dsnOrPDO, $username, $password);
                if (strncasecmp($dsnOrPDO, 'mysql', 5) == 0) {
                    $this->_PDO->exec('set names utf8mb4');
                }
            }
        }
        return $this->_PDO;
    }

    /**
     * Export a table to the export file.
     *
     * @param string $tableName the name of the table to export. This must correspond to one of the accepted vanilla tables.
     * @param mixed $query The query that will fetch the data for the export this can be one of the following:
     *  - <b>String</b>: Represents a string of sql to execute.
     *  - <b>PDOStatement</b>: Represents an already executed query resultset.
     *  - <b>Array</b>: Represents an array of associative arrays or objects containing the data in the export.
     * @param array $mappings Specifies mappings, if any, between the source and the export where the keys represent the export columns and the values represent the source columns.
     *  For a list of the export tables and columns see $this->structure().
     */
    public function exportTable($tableName, $query, $mappings = []) {
        $fp = $this->_File;

        // Make sure the table is valid for export.
        if (!array_key_exists($tableName, $this->_Structures)) {
            $this->comment("Error: $tableName is not a valid export."
                ." The valid tables for export are ".implode(", ", array_keys($this->_Structures)));
            fwrite($fp, self::NEWLINE);
            return;
        }
        $structure = $this->_Structures[$tableName];

        // Start with the table name.
        fwrite($fp, 'Table: '.$tableName.self::NEWLINE);

        // Get the data for the query.
        if (is_string($query)) {
            $query = str_replace(':_', $this->Prefix, $query); // replace prefix.
            $data = $this->pDO()->query($query, PDO::FETCH_ASSOC);
        } elseif ($query instanceof PDOStatement) {
            $data = $query;
        }

        // Set the search and replace to escape strings.
        $escapeSearch = [self::ESCAPE, self::DELIM, self::NEWLINE, self::QUOTE]; // escape must go first
        $escapeReplace = [self::ESCAPE.self::ESCAPE, self::ESCAPE.self::DELIM, self::ESCAPE.self::NEWLINE, self::ESCAPE.self::QUOTE];

        // Write the column header.
        fwrite($fp, implode(self::DELIM, array_keys($structure)).self::NEWLINE);

        // Loop through the data and write it to the file.
        foreach ($data as $row) {
            $row = (array)$row;
            $first = true;

            // Loop through the columns in the export structure and grab their values from the row.
            $exRow = [];
            foreach ($structure as $field => $type) {
                // Get the value of the export.
                if (array_key_exists($field, $row)) {
                    // The column has an exact match in the export.
                    $value = $row[$field];
                } elseif (array_key_exists($field, $mappings)) {
                    // The column is mapped.
                    $value = $row[$mappings[$field]];
                } else {
                    $value = null;
                }
                // Format the value for writing.
                if (is_null($value)) {
                    $value = self::NULL;
                } elseif (is_numeric($value)) {
                    // Do nothing, formats as is.
                } elseif (is_string($value)) {
                    //if(mb_detect_encoding($Value) != 'UTF-8')
                    //   $Value = utf8_encode($Value);

                    $value = self::QUOTE
                        .str_replace($escapeSearch, $escapeReplace, $value)
                        .self::QUOTE;
                } elseif (is_bool($value)) {
                    $value = $value ? 1 : 0;
                } else {
                    // Unknown format.
                    $value = self::NULL;
                }

                $exRow[] = $value;
            }
            // Write the data.
            fwrite($fp, implode(self::DELIM, $exRow));
            // End the record.
            fwrite($fp, self::NEWLINE);
        }

        // Write an empty line to signify the end of the table.
        fwrite($fp, self::NEWLINE);

        if ($data instanceof PDOStatement) {
            $data->closeCursor();
        }
    }



    /**
     * Returns an array of all the expected export tables and expected columns in the exports.
     * When exporting tables using exportTable() all of the columns in this structure will always be exported in the order here, regardless of how their order in the query.
     * @return array
     * @see vnExport::exportTable()
     */
    public function structures() {
        return $this->_Structures;
    }

    /**
     * Returns the version of export file that will be created with this export.
     * The version is used when importing to determine the format of this file.
     * @return string
     */
    public function version() {
        return '1.0';
    }
}
