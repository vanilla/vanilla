<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Garden\Container\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Docker\HostValidator;
use Symfony\Component\Console;
use Vanilla\Cli\Docker\KibanaHttpClient;
use Vanilla\Cli\Docker\ElasticSearchHttpClient;
use Vanilla\Cli\Docker\Service\AbstractLaravelService;
use Vanilla\Cli\Docker\Service\AbstractService;
use Vanilla\Cli\Docker\Service\VanillaJobberService;
use Vanilla\Cli\Docker\Service\VanillaManagementService;
use Vanilla\Cli\Docker\Service\VanillaElasticService;
use Vanilla\Cli\Docker\Service\VanillaFilesService;
use Vanilla\Cli\Docker\Service\VanillaImgProxyService;
use Vanilla\Cli\Docker\Service\VanillaLogsService;
use Vanilla\Cli\Docker\Service\VanillaMailhogService;
use Vanilla\Cli\Docker\Service\VanillaMySqlService;
use Vanilla\Cli\Docker\Service\VanillaNginxService;
use Vanilla\Cli\Docker\Service\VanillaQueueService;
use Vanilla\Cli\Docker\Service\VanillaSearchService;
use Vanilla\Cli\Docker\Service\VanillaVanillaService;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Utility\ArrayUtils;

/**
 * Script for running vanilla in a docker container.
 */
class DockerCommand extends Console\Command\Command
{
    const SERVICE_CLASSES = [
        VanillaNginxService::class,
        VanillaMySqlService::class,
        VanillaManagementService::class,
        VanillaVanillaService::class,
        VanillaImgProxyService::class,
        VanillaMailhogService::class,
        VanillaElasticService::class,
        VanillaLogsService::class,
        VanillaSearchService::class,
        VanillaQueueService::class,
        VanillaFilesService::class,
        VanillaJobberService::class,
    ];

    use ScriptLoggerTrait;
    use InstallDataTrait;

    const VNLA_DOCKER_CWD = PATH_ROOT . "/docker";

    const COMMAND_START = "start";
    const COMMAND_STOP = "stop";

    const COMMAND_UP = "up";
    const COMMAND_DOWN = "down";

    private const SUBCOMMANDS = [self::COMMAND_START, self::COMMAND_STOP, self::COMMAND_UP, self::COMMAND_DOWN];

    /** @var AbstractLaravelService[] */
    private array $services = [];

    /**
     * Get instances of all the service classes.
     *
     * @return array<string, AbstractService> Mapping of serviceID to service instance.
     */
    public static function allServiceInstances(): array
    {
        static $services = null;
        if ($services === null) {
            // Startup is really slow if we have to keep re-initializing these, but there are a few static contexts we
            // want to use them in. The static keeps this performant.
            $services = [];
            $container = new Container();
            foreach (self::SERVICE_CLASSES as $SERVICE_CLASS) {
                /** @var AbstractService $instance */
                $instance = $container->get($SERVICE_CLASS);
                $services[$instance->descriptor->serviceID] = $instance;
            }
        }
        return $services;
    }

    /**
     * @return array
     */
    public static function getRunningServices(): array
    {
        return array_filter(self::allServiceInstances(), fn(AbstractService $service) => $service->isRunning());
    }

    /**
     * Declare input arguments.
     */
    protected function configure(): void
    {
        parent::configure();
        $serviceCsv = implode(", ", self::validServiceIDs());

        $commands = implode(", ", self::SUBCOMMANDS);

        $this->setName("docker")
            ->setDescription("Run vanilla locally in a docker container")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputArgument(
                        "sub-command",
                        Console\Input\InputArgument::OPTIONAL,
                        "One of: $commands",
                        "start"
                    ),
                    new Console\Input\InputOption(
                        "service",
                        null,
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "A CSV of services to start. Valid values are: {$serviceCsv}",
                        null
                    ),
                ])
            );
    }

    /**
     * Get the keys of all valid services.
     *
     * @return array
     */
    private static function validServiceIDs(): array
    {
        $serviceIDs = array_map(
            fn(AbstractService $service) => $service->descriptor->serviceID,
            self::allServiceInstances()
        );
        $validServices = $serviceIDs;
        if (!file_exists(PATH_ROOT . "/cloud")) {
            $validServices = array_diff($validServices, [
                VanillaSearchService::SERVICE_ID,
                VanillaQueueService::SERVICE_ID,
                VanillaManagementService::SERVICE_ID,
                VanillaJobberService::SERVICE_ID,
            ]);
        }
        return $validServices;
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $rawServiceInput = $input->getOption("service") ?? "";
        if ($rawServiceInput === "all") {
            $inputServiceIDs = self::validServiceIDs();
        } else {
            $inputServiceIDs = array_filter(array_map("trim", explode(",", $rawServiceInput)));
        }

        $this->initServiceIDs($inputServiceIDs);
    }

    /**
     * Initialize the command for a set of serviceIDs.
     *
     * @param array $serviceIDs
     *
     * @return void
     */
    public function initServiceIDs(array $serviceIDs): void
    {
        $validServiceIDs = self::validServiceIDs();

        if (empty($serviceIDs)) {
            // Try to load input services from config.
            $serviceIDs = $validServiceIDs;
        }

        // Used services in order

        $allServiceInstances = self::allServiceInstances();

        $finalServiceIDs = [VanillaNginxService::SERVICE_ID];
        foreach ($serviceIDs as $serviceID) {
            $serviceInstance = $allServiceInstances[$serviceID] ?? null;
            if ($serviceInstance === null) {
                throw new \Exception("Service '$serviceID' not found");
            }

            // First add dependants
            foreach ($serviceInstance::$requiredServiceIDs as $requiredServiceID) {
                if (!in_array(needle: $requiredServiceID, haystack: $finalServiceIDs)) {
                    $finalServiceIDs[] = $requiredServiceID;
                }
            }
            // Then add the service itself
            if (!in_array(needle: $serviceID, haystack: $finalServiceIDs)) {
                $finalServiceIDs[] = $serviceID;
            }
        }

        $serviceInstances = array_map(fn(string $serviceID) => $allServiceInstances[$serviceID], $finalServiceIDs);

        $this->services = $serviceInstances;
        $this->logger()->info("Using services: <yellow>" . implode(", ", $finalServiceIDs) . "</yellow>");
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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
                $this->stop();
                break;
            case self::COMMAND_START:
            case self::COMMAND_UP:
                $this->start();
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
     */
    public function start(): void
    {
        // Validate that we have docker.
        $hostValidator = new HostValidator();
        $hostValidator->ensureHosts();
        $hostValidator->ensureCerts();

        foreach ($this->services as $service) {
            $service->ensureCloned();
            $service->start();
        }

        $this->writeVanillaConfigs();
    }

    /**
     * Stop running containers if they are running.
     */
    public function stop(): void
    {
        foreach ($this->services as $service) {
            $service->stop();
        }
        $this->writeVanillaConfigs();
    }

    /**
     * Write config defaults for vanilla.
     */
    private function writeVanillaConfigs(): void
    {
        $configPath = PATH_CONF . "/docker-defaults.php";
        $finalConfigs = [];
        foreach (self::getRunningServices() as $service) {
            foreach ($service->getVanillaConfigDefaults() as $key => $val) {
                ArrayUtils::setByPath($key, $finalConfigs, $val);
            }
        }

        $fileContents = \Gdn_Configuration::format($finalConfigs, []);
        file_put_contents($configPath, $fileContents);
    }
}
