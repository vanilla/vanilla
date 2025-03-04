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
     * @inheritDoc
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
     * @inheritDoc
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

        $allSites = $this->siteProvider->getSites();
        $existingBaseUrls = [];
        foreach ($allSites as $site) {
            $existingBaseUrls[] = $site->getBaseUrl();
        }

        $expectedBaseUrl = "http://vanilla.local/$basePath";
        $siteAlreadyExists = in_array($expectedBaseUrl, $existingBaseUrls);
        if ($siteAlreadyExists) {
            $this->logger()->error("A site already exists at <yellow>$expectedBaseUrl</yellow>.");
            return 1;
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
        $this->logger()->title("Spawning site at <yellow>$basePath</yellow>.");

        $this->logger()->info("Creating database.");
        $dbname = str_replace("-", "_", slugify("vanilla_$basePath"));
        try {
            $pdo = new \PDO("mysql:host=database", "root", "");
        } catch (\PDOException $ex) {
            $this->logger()->error("Could not connect to database. Did you forget to start docker?");
            exit($ex->getCode());
        }
        $pdo->exec("CREATE DATABASE $dbname");
        $this->logger()->success("Database <yellow>$dbname</yellow> created.");

        $this->logger()->info("Creating configuration file.");
        $configPath = PATH_ROOT . "/conf/vanilla.local/$basePath.php";
        touch($configPath);
        chmod($configPath, 0777);
        $this->logger()->success("Created configuration file at <yellow>{$configPath}</yellow>");

        $this->logger()->info("Spawning Site");
        $baseUrl = "http://vanilla.local/$basePath";
        $httpClient = new HttpClient($baseUrl);
        $httpClient->setThrowExceptions(true);
        $httpClient->post("/dashboard/setup.json", [
            "Database-dot-Host" => "database",
            "Database-dot-Name" => $dbname,
            "Database-dot-User" => "root",
            "Database-dot-Password" => "",
            "Garden-dot-Title" => "$basePath Local Site",
            "Email" => "test@example.com",
            "Name" => "admin",
            "Password" => "password1234",
            "PasswordMatch" => "password1234",
        ]);
        $this->logger()->success("Site spawned at <yellow>$baseUrl</yellow>");

        $this->logger()->title("Site Info");
        $this->logger()->info("You can now visit the site at <yellow>$baseUrl</yellow>");
        $this->logger()->info("Database Name: <yellow>$dbname</yellow>");
        $this->logger()->info("Config Path: <yellow>$configPath</yellow>");
        $this->logger()->info("Admin Email: <yellow>test@example.com</yellow>");
        $this->logger()->info("Admin Username: <yellow>admin</yellow>");
        $this->logger()->info("Admin Password: <yellow>password1234</yellow>");
        $this->logger()->info("All emails will be sent to <yellow>http://mail.vanilla.local</yellow>");
    }
}
