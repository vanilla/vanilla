<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

use Gdn;
use Gdn_Configuration;
use Gdn_Database;

/**
 * Abstract class for command using the database.
 */
abstract class DatabaseCommand
{
    /** @var String */
    private $dbname;

    /** @var String */
    private $user;

    /** @var String */
    private $host;

    /** @var String */
    private $password;

    /** @var int */
    private $port;

    /** @var Gdn_Database */
    private $database = null;

    /**
     * Return the Database singleton.
     *
     * @return Gdn_Database|null
     */
    public function getDatabase()
    {
        if (!isset($this->database)) {
            $this->setDatabase();
        }

        return $this->database;
    }

    /**
     * Set the database as a singleton and update the config with the new data.
     */
    public function setDatabase()
    {
        $this->database = Gdn::getContainer()->get(Gdn_Database::class);

        if (isset($this->user, $this->host, $this->dbname)) {
            $dbInfo = [
                "Host" => $this->host ?? "database",
                "Dbname" => $this->dbname,
                "User" => $this->user ?? "root",
                "Password" => $this->password ?? "",
                "Port" => $this->port ?? 3306,
                "Engine" => "MySQL",
                "Prefix" => "GDN_",
            ];
            $config = Gdn::getContainer()->get(Gdn_Configuration::class);
            $config->saveToConfig(["Database" => $dbInfo]);
            $this->database->init($dbInfo);
        }
    }

    /**
     * Set the database User. Default to "root".
     *
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * Set the database Host. Default to "database".
     *
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * Set the database Password. Default to an empty string.
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Set the database Port. Default to 3306.
     *
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * Set the database name.
     *
     * @param string $dbname
     */
    public function setDbname(string $dbname): void
    {
        $this->dbname = $dbname;
    }
}
