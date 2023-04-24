<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Garden\Web\Exception\HttpException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Docker\HostValidator;
use Symfony\Component\Console;
use Vanilla\Cli\Docker\DockerData;
use Vanilla\Cli\Docker\InstallNotFoundException;
use Vanilla\Cli\Docker\KibanaElasticHttpClient;
use Vanilla\Cli\Docker\LogElasticHttpClient;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallData;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;
use Webmozart\PathUtil\Path;

/**
 * Script for running vanilla in a docker container.
 */
class DockerCommand extends Console\Command\Command
{
    public const CONF_DB_MIGRATED = "docker.wasDbMigrated";
    public const CONF_LOGS_SETUP = "docker.wereLogsSetup";
    public const CONF_QUEUE_CLONED = "docker.wasQueueCloned";

    use ScriptLoggerTrait;
    use InstallDataTrait;

    const VNLA_DOCKER_CWD = PATH_ROOT . "/docker";

    private LogElasticHttpClient $logClient;
    private KibanaElasticHttpClient $kibanaClient;

    private const COMMAND_START = "start";
    private const COMMAND_STOP = "stop";

    private const COMMAND_UP = "up";
    private const COMMAND_DOWN = "down";

    private const COMMAND_RESET = "reset";

    private bool $useQueue;

    /** @var bool */
    private bool $useLogs;

    /** @var bool */
    private bool $useImgProxy;

    /** @var bool */
    private bool $useMail;

    /**
     * DI.
     */
    public function __construct(LogElasticHttpClient $logClient, KibanaElasticHttpClient $kibanaClient)
    {
        parent::__construct();
        $this->logClient = $logClient;
        $this->kibanaClient = $kibanaClient;
    }

    /**
     * Declare input arguments.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("docker")
            ->setDescription("Run vanilla locally in a docker container")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputArgument(
                        "sub-command",
                        Console\Input\InputArgument::OPTIONAL,
                        "Subcommand",
                        "start"
                    ),
                    new Console\Input\InputOption(
                        "with-queue",
                        null,
                        null,
                        "The vanilla-queue-service should also be started.",
                        null
                    ),
                    new Console\Input\InputOption("without-logs", null, null, "Do not run the log services.", null),
                    new Console\Input\InputOption(
                        "without-imgproxy",
                        null,
                        null,
                        "Do not run the imgproxy service.",
                        null
                    ),
                    new Console\Input\InputOption("without-mail", null, null, "Do not run the mail services.", null),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if ($input->getOption("with-queue") !== null || $this->installData()->get(self::CONF_QUEUE_CLONED)) {
            $this->useQueue = true;
        } else {
            $this->useQueue = false;
        }

        $this->useLogs = !$input->getOption("without-logs");
        $this->useImgProxy = !$input->getOption("without-imgproxy");
        $this->useMail = !$input->getOption("without-mail");
    }

    /**
     * Main command entrypoint.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument("sub-command");

        $dockerPath = DockerUtils::validateDocker();
        if ($output->isVerbose()) {
            $this->logger()->success("Found 'docker' executable: '$dockerPath'");
        }
        DockerUtils::ensureVanillaNetwork();

        switch ($command) {
            case self::COMMAND_STOP:
            case self::COMMAND_DOWN:
                $this->stop($input, $output);
                break;
            case self::COMMAND_START:
            case self::COMMAND_UP:
                $this->start($input, $output);
                break;
            default:
                throw new \Exception("Unknown subcommand '$command'");
        }

        return self::SUCCESS;
    }

    /**
     * Start/up command.
     *
     * - Ensures docker is running.
     * - Migrates old database if necessary.
     * - Ensures logs are configured.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function start(InputInterface $input, OutputInterface $output)
    {
        // Validate that we have docker.
        self::ensureSymlinks();

        $hostValidator = new HostValidator();
        $hostValidator->ensureHosts();
        $hostValidator->ensureCerts();

        $this->logger()->title("Starting docker");
        // Make sure our bootstrap files are symlinked.
        DockerUtils::startDocker(self::VNLA_DOCKER_CWD, $this->vanillaDockerEnv());

        if (!$this->installData()->get(self::CONF_DB_MIGRATED)) {
            // Check if there is a datastorage volume
            $volumes = DockerUtils::listVolumes();
            $shouldMigrate = false;
            if (in_array("datastorage", $volumes)) {
                // Prompt for
                $shouldMigrate = ShellUtils::promptYesNo(
                    "A set of database data was found from <yellow>vanilla-docker</yellow>. Would you like to migrate it?"
                );
            }

            if ($shouldMigrate) {
                DockerUtils::copyVolumeContents("datastorage", "vanilla_database");
                $this->logger()->success("Successfully migrated database contents. Restarting database.");
                DockerUtils::restartContainer(self::VNLA_DOCKER_CWD, "database");
            }
            $this->installData()->set(self::CONF_DB_MIGRATED, true);
        }

        if ($this->useLogs) {
            $this->checkEsLogsRunning();
            if (!$this->installData()->get(self::CONF_LOGS_SETUP)) {
                $this->logClient->setupIndexes();
                $this->kibanaClient->setupIndexes();

                $this->installData()->set(self::CONF_LOGS_SETUP, true);
            }
        }

        if ($this->useQueue) {
            $this->ensureQueueCloned();
            $this->startQueue();
            $this->logger()->title("Queue - Composer Install");
            DockerUtils::composer($this->queueDir(), "vanilla-queue", "/var/www/html", "install");
            DockerUtils::restartContainer($this->queueDir(), "vanilla-queue");
            DockerUtils::restartContainer(self::VNLA_DOCKER_CWD, "nginx");
            $this->ensureHorizonPublished();
            $this->logger()->info("Queue is now running at <yellow>https://queue.vanilla.localhost</yellow>");
        }
    }

    /**
     * Ensure horizon vendor assets for the frontend are ready.
     */
    private function ensureHorizonPublished()
    {
        $queueDir = $this->queueDir();
        if (!file_exists($queueDir . "/public/vendor/horizon")) {
            DockerUtils::artisan($queueDir, "vanilla-queue", "/var/www/html", "horizon:publish");
        }
    }

    /**
     * Get the environment to use when starting and stopping docker for vanilla-cloud.
     *
     * @return array
     */
    private function vanillaDockerEnv(): array
    {
        $composeFiles = ["./docker-compose.yml"];
        if ($this->useLogs) {
            $composeFiles[] = "./docker-compose.logs.yml";
        }
        if ($this->useImgProxy) {
            $composeFiles[] = "./docker-compose.imgproxy.yml";
        }
        if ($this->useMail) {
            $composeFiles[] = "./docker-compose.mailhog.yml";
        }
        return [
            "COMPOSE_FILE" => implode(":", $composeFiles),
        ];
    }

    /**
     * Stop running containers if they are running.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function stop(InputInterface $input, OutputInterface $output)
    {
        DockerUtils::stopDocker(self::VNLA_DOCKER_CWD, $this->vanillaDockerEnv());
        if ($this->useQueue) {
            DockerUtils::stopDocker($this->queueDir());
        }
    }

    /**
     * Validate that the elastic search logs are running.
     */
    private function checkEsLogsRunning()
    {
        $this->logger()->title("Checking Log Service Health");
        try {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->logClient->healthCheck();
                    $this->kibanaClient->healthCheck();
                    $this->logger()->success("Log service is healthy");
                    $this->logger()->info("Logs are now running at <yellow>https://logs.vanilla.localhost</yellow>");
                    return;
                } catch (\Exception $e) {
                    // We might still be starting up.
                    if ($i === 12) {
                        throw $e;
                    } else {
                        // try again.
                        sleep(5);
                    }
                }
            }
        } catch (HttpException $ex) {
            $this->logger()->error("Log service failed health check.");
            throw $ex;
        }
    }

    /**
     * Ensure our config bootstrapping classes are symlinked.
     */
    private function ensureSymlinks()
    {
        $this->forceSymlink("../docker/bootstrap.before.php", PATH_CONF . "/bootstrap.before.php");
        $earlySource = $this->useQueue ? "../docker/bootstrap.docker.queue.php" : "../docker/boostrap.docker.php";
        $this->forceSymlink($earlySource, PATH_CONF . "/bootstrap.early.php");

        $cloudLinkScript = PATH_ROOT . "/cloud/scripts/symlink-addons";
        if (file_exists($cloudLinkScript)) {
            // Make sure our cloud addons are all symlinked.
            $process = new Process([$cloudLinkScript], PATH_ROOT);
            $process->mustRun();
        }
        $this->logger()->debug("Symlinks created");
    }

    /**
     * Create a symlink `ln -sf`
     *
     * @param string $symlinkTarget
     * @param string $symlinkFile
     */
    private function forceSymlink(string $symlinkTarget, string $symlinkFile)
    {
        if (file_exists($symlinkFile)) {
            unlink($symlinkFile);
        }
        $process = new Process(["ln", "-sf", $symlinkTarget, $symlinkFile], PATH_ROOT);
        $process->mustRun();
    }

    /**
     * Start the queue's docker container.
     */
    private function startQueue()
    {
        $this->logger()->title("Starting Queue Service");
        $queueDir = $this->queueDir();

        $sanityCheckFile = $queueDir . "/docker/7.4";
        if (file_exists($sanityCheckFile)) {
            throw new \Exception(
                "Found an old version of the vanilla-queue-service. Make sure you have a version that support vnla-docker."
            );
        }

        // Make sure we have an env file.
        $envFilePath = $queueDir . "/.env";
        if (!file_exists($envFilePath)) {
            copy($queueDir . "/.env.example", $envFilePath);
        }

        DockerUtils::startDocker($queueDir, ["VANILLA_LOCAL_CONFIG_DIR" => PATH_ROOT . "/conf"]);
    }

    /**
     * Ensure that the queue is cloned.
     */
    private function ensureQueueCloned()
    {
        $queueDir = $this->queueDir();
        if (file_exists($queueDir)) {
            // It already exists.
            $this->installData()->set(self::CONF_QUEUE_CLONED, true);

            // Ensure the cache directory is writeable.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($queueDir . "/storage/framework")
            );

            foreach ($iterator as $item) {
                chmod($item, 0777);
            }

            return;
        }

        $this->logger()->title("Setup Queue");
        $process = new Process(["git", "clone", "git@github.com:vanilla/vanilla-queue-service.git", $queueDir]);
        $this->logger()->runProcess($process);
        $this->installData()->set(self::CONF_QUEUE_CLONED, true);
    }

    /**
     * Get the directory the queue will be cloned into.
     *
     * @return string
     */
    private function queueDir(): string
    {
        return Path::canonicalize(PATH_ROOT . "/../vanilla-queue-service");
    }
}
