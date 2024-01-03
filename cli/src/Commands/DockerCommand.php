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
use Vanilla\Cli\Docker\Service\VanillaElasticService;
use Vanilla\Cli\Docker\Service\VanillaImgProxyService;
use Vanilla\Cli\Docker\Service\VanillaLogsService;
use Vanilla\Cli\Docker\Service\VanillaMailhogService;
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
    const SERVICE_MAP = [
        VanillaVanillaService::SERVICE_NAME => [VanillaVanillaService::class],
        VanillaImgProxyService::SERVICE_NAME => [VanillaImgProxyService::class],
        VanillaMailhogService::SERVICE_NAME => [VanillaMailhogService::class],
        VanillaElasticService::SERVICE_NAME => [VanillaElasticService::class],
        VanillaLogsService::SERVICE_NAME => [VanillaElasticService::class, VanillaLogsService::class],
        VanillaSearchService::SERVICE_NAME => [VanillaElasticService::class, VanillaSearchService::class],
        VanillaQueueService::SERVICE_NAME => [VanillaQueueService::class],
    ];

    use ScriptLoggerTrait;
    use InstallDataTrait;

    const VNLA_DOCKER_CWD = PATH_ROOT . "/docker";

    private ElasticSearchHttpClient $elasticClient;
    private KibanaHttpClient $kibanaClient;

    private const COMMAND_START = "start";
    private const COMMAND_STOP = "stop";

    private const COMMAND_UP = "up";
    private const COMMAND_DOWN = "down";

    private const COMMAND_RESET = "reset";

    /** @var AbstractLaravelService[] */
    private array $services = [];

    /**
     * DI.
     */
    public function __construct(ElasticSearchHttpClient $logClient, KibanaHttpClient $kibanaClient)
    {
        parent::__construct();
        $this->elasticClient = $logClient;
        $this->kibanaClient = $kibanaClient;
    }

    /**
     * Declare input arguments.
     */
    protected function configure()
    {
        parent::configure();
        $servicesValues = array_keys(self::SERVICE_MAP);
        $serviceCsv = implode(", ", $servicesValues);
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
    private static function validServiceKeys(): array
    {
        $validServices = array_keys(self::SERVICE_MAP);
        if (!file_exists(PATH_ROOT . "/cloud")) {
            $validServices = array_diff($validServices, [
                VanillaSearchService::SERVICE_NAME,
                VanillaQueueService::SERVICE_NAME,
            ]);
        }
        return $validServices;
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $rawServiceInput = $input->getOption("service") ?? "";
        if ($rawServiceInput === "all") {
            $inputServices = self::validServiceKeys();
        } else {
            $inputServices = array_filter(array_map("trim", explode(",", $rawServiceInput)));
        }

        if (empty($inputServices)) {
            // Try to load input services from config.
            $inputServices = self::validServiceKeys();
        }

        // Persist
        $badServices = array_diff($inputServices, self::validServiceKeys());
        if (!empty($badServices)) {
            throw new \Exception("Invalid service(s) specified: " . implode(", ", $badServices));
        }

        // Used services in order
        $usedServices = array_intersect(self::validServiceKeys(), $inputServices);

        $serviceClasses = [VanillaNginxService::class];
        foreach ($usedServices as $serviceKey) {
            $serviceClasses = array_merge($serviceClasses, self::SERVICE_MAP[$serviceKey]);
        }

        $container = new Container();
        foreach (array_unique($serviceClasses) as $serviceClass) {
            $this->services[] = $container->get($serviceClass);
        }
        $this->logger()->info("Using services: <yellow>" . implode(", ", $usedServices) . "</yellow>");
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
    private function start()
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

        // One final nginx restart
        DockerUtils::restartContainer(DockerCommand::VNLA_DOCKER_CWD, "nginx");
    }

    private function writeVanillaConfigs()
    {
        $configPath = PATH_CONF . "/docker-defaults.php";
        $serviceConfigs = [];
        foreach ($this->services as $service) {
            $serviceConfigs = array_merge_recursive($serviceConfigs, $service->getVanillaConfigDefaults());
        }

        $finalConfigs = [];
        foreach ($serviceConfigs as $key => $val) {
            ArrayUtils::setByPath($key, $finalConfigs, $val);
        }

        $fileContents = \Gdn_Configuration::format($finalConfigs, []);
        file_put_contents($configPath, $fileContents);
    }

    /**
     * Stop running containers if they are running.
     */
    private function stop()
    {
        foreach ($this->services as $service) {
            $service->stop();
        }
    }
}
