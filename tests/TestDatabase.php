<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PDO;

/**
 * A database object used for testing.
 */
class TestDatabase extends \Gdn_Database {
    /**
     * TestDatabase constructor.
     *
     * @param PDO|null $pdo
     * @psalm-suppress UndefinedConstant
     */
    public function __construct(PDO $pdo = null) {
        $config = [
            'Engine' => 'MySQL',
            'ConnectionOptions' => [
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "set names 'utf8mb4'; set time_zone = '+0:0';"
            ],
            'CharacterEncoding' => 'utf8mb4',
            'DatabasePrefix' => 'GDN_',
            'ExtendedProperties' => [
                'Collate' => 'utf8mb4_unicode_ci'
            ]
        ];
        parent::__construct($config);

        $this->setPDO($pdo);
    }


    /**
     * Set the underlying database connection.
     *
     * @param PDO $pdo The new database connection.
     */
    public function setPDO(PDO $pdo) {
        $this->_Connection = $pdo;
        $this->_Slave = $pdo;
    }
}
