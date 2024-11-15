<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

use Exception;
use Gdn;
use Gdn_Database;
use Gdn_UserException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Schema\RangeExpression;

/**
 * Abstract class for command using a database.
 */
abstract class DatabaseCommand extends Console\Command\Command
{
    private Gdn_Database|NULL $database = null;

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

    /**
     * Fetch the config values.
     */
    protected function configure()
    {
        parent::configure();
        $this->setDefinition(
            new Console\Input\InputDefinition([
                new Console\Input\InputOption("dbhost", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("dbport", null, Console\Input\InputOption::VALUE_OPTIONAL),
                new Console\Input\InputOption("dbname", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("dbuser", null, Console\Input\InputOption::VALUE_REQUIRED),
                new Console\Input\InputOption("dbpassword", "p", Console\Input\InputOption::VALUE_NONE),
            ])
        );
    }

    /**
     * Initialize the DB connection.
     */
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

        $host = $getRequiredOption("dbhost");
        $dbName = $getRequiredOption("dbname");
        $port = $input->getOption("dbport") ?? 3306;
        $user = $getRequiredOption("dbuser");
        $dbInfo = [
            "Host" => $host,
            "Dbname" => $dbName,
            "User" => $user,
            "Port" => $port,
            "Engine" => "MySQL",
            "Prefix" => "GDN_",
        ];

        if ($input->getOption("dbpassword")) {
            /** @var Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper("question");
            $question = new Console\Question\Question("Enter the password for the database" . PHP_EOL);
            $dbInfo["Password"] = $helper->ask($input, $output, $question);
        }

        $this->database = Gdn::getContainer()->get(Gdn_Database::class);

        \Gdn::config()->saveToConfig("Database", $dbInfo);
        $this->database->init($dbInfo);
    }

    /**
     * Fetch the posts based on a recordType and a cursor.
     *
     * @param array $record
     * @param int $offset
     * @param array $where
     * @return array|null
     * @throws Exception
     */
    protected function fetchPosts(array $record, int $offset, array $where = []): array|null
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
     * @param int $to
     * @param array $where
     * @return int
     * @throws Exception
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

    /**
     * Determine if a table exists
     *
     * @param string $table
     * @return bool
     * @throws \Gdn_UserException
     */
    protected function tableExists(string $table): bool
    {
        $result = $this->getDatabase()
            ->query("show tables like '$table'")
            ->count();

        return $result > 0;
    }

    /**
     * Determine if a column exists in a table
     *
     * @param string $table
     * @param string $column
     * @return bool
     * @throws Gdn_UserException
     */
    protected function columnExists(string $table, string $column): bool
    {
        $result = $this->getDatabase()
            ->query(
                "
            select
                column_name
            from
                information_schema.columns
            where
                table_schema = database()
                and table_name = '$table'
                and column_name = '$column'
        "
            )
            ->count();
        return $result > 0;
    }

    /**
     * Do a get where on the specified table.
     *
     * @param $table
     * @param null $where
     * @param int|null $limit
     * @return int
     * @throws Gdn_UserException
     */
    protected function getCountWhere($table, $where = null, ?int $limit = null): int
    {
        $queryWhere = "";
        $queryLimit = "";

        if (isset($where)) {
            $queryWhere = "where $where";
        }

        if (isset($limit)) {
            $queryLimit = "limit $limit";
        }

        return $this->getDatabase()
            ->query("select * from $table $queryWhere $queryLimit")
            ->count();
    }
}
