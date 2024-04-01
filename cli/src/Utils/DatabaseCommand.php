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
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Schema\RangeExpression;

/**
 * Abstract class for command using a database.
 */
abstract class DatabaseCommand extends Console\Command\Command
{
    /** @var Gdn_Database */
    private $database = null;

    protected int $batchSize = 1000;

    /**
     * Return the Database singleton.
     *
     * @return Gdn_Database
     */
    public function getDatabase(): Gdn_Database
    {
        return $this->database;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDefinition(
            new Console\Input\InputDefinition([
                new Console\Input\InputOption("db-host", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("db-port", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("db-name", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("db-user", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("db-password", null, Console\Input\InputOption::VALUE_REQUIRED),
            ])
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        // Validate inputs.
        $getRequiredOption = function (string $name) use ($input) {
            $value = $input->getOption($name);
            if ($value === null) {
                throw new \Exception("Argument '$name' is required.");
            }
            return $value;
        };

        $host = $getRequiredOption("db-host");
        $dbName = $getRequiredOption("db-name");
        $port = $input->getOption("db-port") ?? 3306;
        $user = $getRequiredOption("db-user");
        $password = $input->getOption("db-password") ?? "";

        $this->database = Gdn::getContainer()->get(Gdn_Database::class);
        $dbInfo = [
            "Host" => $host,
            "Dbname" => $dbName,
            "User" => $user,
            "Password" => $password,
            "Port" => $port,
            "Engine" => "MySQL",
            "Prefix" => "GDN_",
        ];
        \Gdn::config()->saveToConfig("Database", $dbInfo);
        $this->database->init($dbInfo);
    }

    /**
     * Fetch the posts based on a recordType and a cursor.
     *
     * @param $record
     * @param $offset
     * @return array|null
     */
    protected function fetchPosts(array $record, int $offset, array $where = [])
    {
        $sql = $this->getDatabase()->createSql();

        $where = array_merge($where, [
            $record["id"] => new RangeExpression(">=", $record["from"], "<=", $record["to"]),
        ]);

        $result = $sql
            ->select([$record["id"], "Body", "Format", "DateInserted", $record["parentRecordID"]])
            ->from($record["table"])
            ->where($where)
            ->offset($offset)
            ->limit($this->batchSize)
            ->get()
            ->resultArray();

        return $result;
    }

    /**
     * A comma-separated list of the records to be indexed (e.g. `discussion,comment`).
     *
     * @param string $records
     */
    public function setRecords(string $records): void
    {
        $records = explode(",", $records);
        $this->records = $records;
    }

    /**
     * Set the size of the batches to be indexed.
     *
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Return the highest value between the MaxID of a certain field or the $to argument.
     *
     * @param string $recordID
     * @param string $recordTable
     * @param array $where
     * @param int $to
     * @return int
     */
    protected function getMaxID(string $recordID, string $recordTable, int $to, array $where = []): int
    {
        $sql = $this->getDatabase()->createSql();
        $result = $sql
            ->select($recordID, "max")
            ->from($recordTable)
            ->where($where)
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);

        return min($result[$recordID], $to);
    }
}
