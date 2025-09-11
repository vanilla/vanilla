<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Garden\Http\HttpClient;
use Garden\Sites\Local\LocalSiteProvider;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\CurrentTimeStamp;
use VanillaTests\APIv0\E2ETestClient;
use VanillaTests\TestInstallModel;

/**
 * Utility for spawning a local site.
 */
class SpawnSiteCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    private ?string $basePath;

    private LocalSiteProvider $siteProvider;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->siteProvider = new LocalSiteProvider(PATH_ROOT . "/conf");
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("spawn-site")
            ->setDescription("Spawn a local site.")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputOption(
                        "base-path",
                        null,
                        Console\Input\InputOption::VALUE_OPTIONAL,
                        "The base path to spawn the site in. Eg. https://vanilla.local/BASE_PATH"
                    ),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Do we have a base path?
        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper("question");

        $basePath = $input->getOption("base-path");
        if (!$basePath) {
            // Prompt for it.
            $question = new Console\Question\Question(
                "What should the base path of the site be. A base path of <info>my-path</info> will result in a site at <comment>https://vanilla.local/</comment><info>my-path</info>: "
            );
            $basePath = $helper->ask($input, $output, $question);
            if (!$basePath) {
                throw new \Exception("Invalid path.");
            }
        }

        $this->spawnSite($basePath);
        return self::SUCCESS;
    }

    /**
     * Test.
     *
     * @param string $basePath
     *
     * @return void
     */
    private function spawnSite(string $basePath): void
    {
        $baseUrl = "<yellow>https://vanilla.local/<yellow><green>$basePath</green>";
        $this->logger()->title("Spawning site at $baseUrl.");

        $this->logger()->info("Creating database.");
        $dbname = str_replace("-", "_", slugify("vanilla_$basePath"));
        try {
            $pdo = new \PDO("mysql:host=database", "root", "");
        } catch (\PDOException $ex) {
            $this->logger()->error("Could not connect to database. Did you forget to start docker?");
            exit($ex->getCode());
        }

        $this->logger()->title("Creating Site");
        $this->logger()->info("Creating site at $baseUrl.");

        putenv("TEST_DB_HOST=database");
        putenv("TEST_DB_USER=root");
        $testClient = new E2ETestClient($basePath, "vanilla.local");
        $testClient->dbPrefix = "vanilla_";
        @$testClient->install();

        $testClient->get("/utility/alive.json");
        $this->logger()->info("Site is alive");

        $this->logger()->info("Ensure admin user exists.");
        $pdo = $testClient->getPDO();
        $currentTime = CurrentTimeStamp::getMySQL();
        $pdo->exec(
            <<<SQL
INSERT INTO GDN_User (Name, Password, HashMethod, Email, DateInserted, Admin)
VALUES ("admin", "password1234", "Text", "test@example.com", "$currentTime", 1)
ON DUPLICATE KEY UPDATE
    Password = "password1234",
    HashMethod = "Text"
SQL
        );

        $sslBaseUrl = str_replace("http://", "https://", $testClient->getBaseUrl());

        $this->logger()->title("Site Info");
        $this->logger()->info("You can now visit the site at <yellow>{$sslBaseUrl}</yellow>");
        $this->logger()->info("Database Name: <yellow>$dbname</yellow>");
        $this->logger()->info("Config Path: <yellow>{$testClient->getConfigPath()}</yellow>");
        $this->logger()->info("Admin Email: <yellow>test@example.com</yellow>");
        $this->logger()->info("Admin Username: <yellow>admin</yellow>");
        $this->logger()->info("Admin Password: <yellow>password1234</yellow>");
        $this->logger()->info("All emails will be sent to <yellow>http://mail.vanilla.local</yellow>");
    }
}
