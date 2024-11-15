<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Exception;
use Gdn_UserException;
use Throwable;
use Vanilla\Cli\Utils\DatabaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Faker\Generator;
use Faker;
use Vanilla\CurrentTimeStamp;

/**
 * CLI command to generate data in bulk.
 */
class GenerateDataCommand extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const BATCH_SIZE = 1000;
    const MAX_TEXT_LENGTH = 2000;
    private Generator $faker;
    private string $locale;
    private ?int $parent;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName("generate-data")->setDescription("Command to create data in bulk on a site.");

        $definition = $this->getDefinition();

        // User
        $definition->addOption(
            new InputOption("user", null, InputOption::VALUE_OPTIONAL, "How many users to create.", 0)
        );
        $definition->addOption(
            new InputOption("roleid", null, InputOption::VALUE_OPTIONAL, "What role to give to the new users.", 0)
        );

        $definition->addOption(
            new InputOption("category", null, InputOption::VALUE_OPTIONAL, "How many categories to create.", 0)
        );
        $definition->addOption(
            new InputOption("discussion", null, InputOption::VALUE_OPTIONAL, "How many discussions to create.", 0)
        );
        $definition->addOption(
            new InputOption("comment", null, InputOption::VALUE_OPTIONAL, "How many comments to create.", 0)
        );
        $definition->addOption(
            new InputOption("locale", null, InputOption::VALUE_OPTIONAL, "Locale of the data to generate.", "en_US")
        );
        $definition->addOption(
            new InputOption("parent", null, InputOption::VALUE_OPTIONAL, "ID to ALWAYS use as the parent.", null)
        );
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->locale = $input->getOption("locale");
        $this->parent = $input->getOption("parent");
        parent::initialize($input, $output);
    }

    /**
     * Generate the following data:
     * - Users
     * - Categories
     * - Discussions
     * - Comments
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Gdn_UserException
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generateUsers($input, $output);
        $this->generateCategories($input, $output);
        $this->generateDiscussions($input, $output);
        $this->generateComments($input, $output);

        return self::SUCCESS;
    }

    /**
     * Create new users along with their roles.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function generateUsers(InputInterface $input, OutputInterface $output): void
    {
        $userCount = $input->getOption("user");
        if ($userCount <= 0) {
            return;
        }

        $roleID = $input->getOption("roleid");
        if (!$roleID) {
            $roleID = $this->getMemberRole();
        }

        $faker = Faker\Factory::create($this->locale);
        $provider = "Faker\Provider\\$this->locale\Person";
        $faker->addProvider(new $provider($faker));

        $this->logger()->title("Generating $userCount users.");
        $progressBar = new ProgressBar($output, $userCount);

        $current = 0;
        while ($current < $userCount) {
            $users = [];
            $userRoles = [];
            $batchSize = min($userCount - $current, self::BATCH_SIZE);
            $maxUserID = $this->getMaxPrimaryID("User");

            for ($i = 0; $i < $batchSize; $i++) {
                $name = $faker->name() . rand(1, 1000);
                $users[] = [
                    "UserID" => $maxUserID++,
                    "Name" => $this->getDatabase()->quoteExpression($name),
                    "Password" => "password",
                    "HashMethod" => "Reset",
                    "Email" => $this->getDatabase()->quoteExpression("{$name}_noemail@higherlogic.com"),
                    "DateInserted" => date("Y-m-d H:i:s"),
                    "DateLastActive" => date("Y-m-d H:i:s"),
                ];
                $userRoles[] = [
                    "UserID" => $maxUserID,
                    "RoleID" => $roleID,
                ];
            }

            $sql = "INSERT INTO GDN_User (Name, Password, HashMethod, Email, DateInserted, DateLastActive) VALUES ";
            $sql .= implode(
                ", ",
                array_map(function ($user) {
                    return "({$user["Name"]}, '{$user["Password"]}', '{$user["HashMethod"]}', {$user["Email"]}, '{$user["DateInserted"]}', '{$user["DateLastActive"]}')";
                }, $users)
            );
            $this->getDatabase()->query($sql);

            // Set the user roles.
            $sql = "INSERT INTO GDN_UserRole (UserID, RoleID) VALUES ";
            $sql .= implode(
                ", ",
                array_map(function ($userRole) {
                    return "({$userRole["UserID"]}, {$userRole["RoleID"]})";
                }, $userRoles)
            );
            $this->getDatabase()->query($sql);

            $current += $batchSize;
            $progressBar->advance($batchSize);
        }
        $progressBar->finish();
    }

    /**
     * Create new categories.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function generateCategories(InputInterface $input, OutputInterface $output): void
    {
        $categoryCount = $input->getOption("category");
        if ($categoryCount <= 0) {
            return;
        }

        $faker = Faker\Factory::create($this->locale);
        $provider = "Faker\Provider\\$this->locale\Company";
        $faker->addProvider(new $provider($faker));

        $this->logger()->title("Generating $categoryCount categories.");
        $progressBar = new ProgressBar($output, $categoryCount);
        $current = 0;
        $systemUserID = $this->getWhere("User", ["Name =" => "System"])[0]["UserID"] ?? 0;
        while ($current < $categoryCount) {
            $categories = [];
            $batchSize = min($categoryCount - $current, self::BATCH_SIZE);
            $maxCategoryID = $this->getMaxPrimaryID("Category");
            for ($i = 0; $i < $batchSize; $i++) {
                $name = $faker->bs() . rand(1, 1000);
                $categories[] = [
                    "CategoryID" => ++$maxCategoryID,
                    "ParentCategoryID" => -2,
                    "Name" => $this->getDatabase()->quoteExpression($name),
                    "UrlCode" => $this->getDatabase()->quoteExpression(slugify($name . "-" . CurrentTimeStamp::get())),
                    "InsertUserID" => $systemUserID,
                    "DateInserted" => date("Y-m-d H:i:s"),
                    "DateUpdated" => date("Y-m-d H:i:s"),
                ];
            }

            $sql =
                "INSERT INTO GDN_Category (CategoryID, ParentCategoryID, Name, UrlCode, InsertUserID, DateInserted, DateUpdated) VALUES ";
            $sql .= implode(
                ", ",
                array_map(function ($category) {
                    return "({$category["CategoryID"]}, {$category["ParentCategoryID"]}, {$category["Name"]}, {$category["UrlCode"]}, {$category["InsertUserID"]}, '{$category["DateInserted"]}', '{$category["DateUpdated"]}')";
                }, $categories)
            );
            $this->getDatabase()->query($sql);

            // Set the ParentCategoryID.
            $this->updateParentCategory($categories);

            $current += $batchSize;
            $progressBar->advance($batchSize);
        }

        $this->logger()->info("Make sure to go to `dba/counts` to recalculate the counts.");
        $progressBar->finish();
    }

    /**
     * Create new discussions.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function generateDiscussions(InputInterface $input, OutputInterface $output): void
    {
        $discussionCount = $input->getOption("discussion");
        if ($discussionCount <= 0) {
            return;
        }

        $faker = Faker\Factory::create($this->locale);
        $provider = "Faker\Provider\\$this->locale\Company";
        $faker->addProvider(new $provider($faker));

        $this->logger()->title("Generating $discussionCount discussions.");
        $progressBar = new ProgressBar($output, $discussionCount);

        $categories = $this->getWhere("Category", ["CategoryID >" => -1]);
        $categoryCount = count($categories);

        $users = $this->getWhere("User");
        $userCount = count($users);
        $current = 0;

        while ($current < $discussionCount) {
            $discussions = [];
            $batchSize = min($discussionCount - $current, self::BATCH_SIZE);

            for ($i = 0; $i < $batchSize; $i++) {
                $name = $faker->catchPhrase();
                $body = $faker->realText(self::MAX_TEXT_LENGTH);
                $categoryID = $this->parent ?? $categories[$this->generateRandomSeed($categoryCount)]["CategoryID"];
                $userID = $users[$this->generateRandomSeed($userCount)]["UserID"];

                $discussions[] = [
                    "CategoryID" => $categoryID,
                    "Name" => $this->getDatabase()->quoteExpression($name),
                    "Body" => $this->getDatabase()->quoteExpression($body),
                    "Format" => "Text",
                    "InsertUserID" => $userID,
                    "DateInserted" => date("Y-m-d H:i:s"),
                    "DateUpdated" => date("Y-m-d H:i:s"),
                ];
            }

            $sql =
                "INSERT INTO GDN_Discussion (CategoryID, Name, Body, Format, InsertUserID, DateInserted, DateUpdated) VALUES ";
            $sql .= implode(
                ", ",
                array_map(function ($discussion) {
                    return "({$discussion["CategoryID"]}, {$discussion["Name"]}, {$discussion["Body"]}, '{$discussion["Format"]}', {$discussion["InsertUserID"]}, '{$discussion["DateInserted"]}', '{$discussion["DateUpdated"]}')";
                }, $discussions)
            );
            $this->getDatabase()->query($sql);

            $current += $batchSize;
            $progressBar->advance($batchSize);
        }

        $progressBar->finish();
    }

    /**
     * Create new comments.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function generateComments(InputInterface $input, OutputInterface $output): void
    {
        $commentCount = $input->getOption("comment");
        if ($commentCount <= 0) {
            return;
        }

        $faker = Faker\Factory::create($this->locale);
        $provider = "Faker\Provider\\$this->locale\Company";
        $faker->addProvider(new $provider($faker));

        $this->logger()->title("Generating $commentCount comments.");
        $progressBar = new ProgressBar($output, $commentCount);

        $discussions = $this->getWhere("Discussion");
        $discussionCount = count($discussions);

        $users = $this->getWhere("User");
        $userCount = count($users);
        $current = 0;

        while ($current < $commentCount) {
            $comments = [];
            $batchSize = min($commentCount - $current, self::BATCH_SIZE);

            for ($i = 0; $i < $batchSize; $i++) {
                $body = $faker->realText(self::MAX_TEXT_LENGTH);
                $discussionID =
                    $this->parent ?? $discussions[$this->generateRandomSeed($discussionCount)]["DiscussionID"];
                $userID = $users[$this->generateRandomSeed($userCount)]["UserID"];

                $comments[] = [
                    "DiscussionID" => $discussionID,
                    "InsertUserID" => $userID,
                    "parentRecordType" => "Discussion",
                    "parentRecordID" => $discussionID,
                    "Body" => $this->getDatabase()->quoteExpression($body),
                    "Format" => "Text",
                    "DateInserted" => date("Y-m-d H:i:s"),
                ];
            }

            $sql =
                "INSERT INTO GDN_Comment (DiscussionID, InsertUserID, parentRecordType, parentRecordID, Body, Format, DateInserted) VALUES ";

            $sql .= implode(
                ", ",
                array_map(function ($comment) {
                    return "({$comment["DiscussionID"]}, {$comment["InsertUserID"]}, '{$comment["parentRecordType"]}', {$comment["parentRecordID"]}, {$comment["Body"]}, '{$comment["Format"]}', '{$comment["DateInserted"]}')";
                }, $comments)
            );

            $this->getDatabase()->query($sql);

            $current += $batchSize;
            $progressBar->advance($batchSize);
        }

        $progressBar->finish();
    }

    // UTILS

    /**
     * Fetch the RoleID of the member role.
     *
     * @return int
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function getMemberRole(): int
    {
        $result = $this->getDatabase()
            ->query("SELECT RoleID FROM GDN_Role WHERE `Type` = 'member' limit 1")
            ->resultArray();

        if (empty($result)) {
            $this->logger()->error("No member role found.");
            die();
        }

        return (int) $result[0]["RoleID"];
    }

    /**
     * Generate a random seed to be used as an ID.
     *
     * With enough records, this will generate a normal distribution in accordance to the Central limit Theorem.
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    private function generateRandomSeed(int $max, int $min = 0): int
    {
        // We want to generate an index between 0 and Count -1.
        $max--;
        return (rand($min, $max) + rand($min, $max)) / 2;
    }

    /**
     * Fetch the maximum primary ID of a table.
     *
     * @param string $table
     * @return int
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function getMaxPrimaryID(string $table): int
    {
        $result = $this->getDatabase()
            ->query("SELECT MAX(`{$table}ID`) as maxID FROM `GDN_$table`")
            ->resultArray();

        return (int) $result[0]["maxID"];
    }

    /**
     * Return all primary IDs of a table.
     *
     * @param string $table
     * @param array $where
     * @return array
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function getWhere(string $table, array $where = []): array
    {
        $queryWhere = "";
        if (!empty($where)) {
            $queryWhere =
                "WHERE " .
                implode(
                    " AND ",
                    array_map(
                        function ($key, $value) {
                            return "$key '$value'";
                        },
                        array_keys($where),
                        $where
                    )
                );
        }

        return $this->getDatabase()
            ->query("SELECT {$table}ID FROM GDN_$table $queryWhere")
            ->resultArray();
    }

    /**
     * Update the category parent.
     *
     * This will set the parent category to a random category that is less than the current category. This will create a tree structure.
     *
     * @param array $categories
     * @return void
     * @throws Gdn_UserException
     * @throws Throwable
     */
    private function updateParentCategory(array $categories): void
    {
        $allCategories = $this->getWhere("Category");
        $categoryCount = count($allCategories);

        if ($this->parent) {
            $categoryWhere = implode(
                ", ",
                array_map(function ($category) {
                    return "{$category["CategoryID"]}";
                }, $categories)
            );

            $this->getDatabase()->query(
                "UPDATE GDN_Category SET ParentCategoryID = $this->parent WHERE CategoryID in ($categoryWhere)"
            );
        }

        foreach ($categories as $category) {
            do {
                $parentCategoryID = $allCategories[$this->generateRandomSeed($categoryCount)]["CategoryID"];
            } while ($parentCategoryID >= $category["CategoryID"]);

            $this->getDatabase()->query(
                "UPDATE GDN_Category SET ParentCategoryID = $parentCategoryID WHERE CategoryID = {$category["CategoryID"]}"
            );
        }
    }
}
