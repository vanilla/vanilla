<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Web\Exception\HttpException;
use Vanilla\Cli\Commands\DockerCommand;
use Vanilla\Cli\Docker\ElasticSearchHttpClient;
use Vanilla\Cli\Docker\KibanaHttpClient;

/**
 * Service for kibana.vanilla.localhost
 */
class VanillaLogsService extends AbstractService
{
    const SERVICE_NAME = "logs";

    private KibanaHttpClient $kibanaClient;

    /**
     * @param KibanaHttpClient $kibanaClient
     */
    public function __construct(KibanaHttpClient $kibanaClient)
    {
        $this->kibanaClient = $kibanaClient;
    }

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.elastic.yml:./docker-compose.logs.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Vanilla Logs";
    }

    /**
     * @inheritDoc
     */
    public function getTargetDirectory(): string
    {
        return DockerCommand::VNLA_DOCKER_CWD;
    }

    /**
     * @inheritDoc
     */
    public function getHostname(): string
    {
        return "kibana.vanilla.localhost";
    }

    /**
     * @inheritDoc
     */
    public function start()
    {
        parent::start();
        $this->setup();
    }

    public function getVanillaConfigDefaults(): array
    {
        return [];
    }

    /**
     * Perform additional setup of indexes.
     */
    private function setup()
    {
        $this->checkElasticRunning();
        $this->kibanaClient->setupIndexes();
    }

    /**
     * Validate that the elastic search logs are running.
     */
    private function checkElasticRunning()
    {
        $this->logger()->title("Checking Kibana Health");
        try {
            for ($i = 0; $i < 11; $i++) {
                try {
                    $this->kibanaClient->healthCheck();
                    $this->logger()->success("Kibana is healthy");
                    return;
                } catch (\Exception $e) {
                    // We might still be starting up.
                    if ($i === 9) {
                        throw $e;
                    } else {
                        // try again.
                        sleep(5);
                    }
                }
            }
        } catch (HttpException $ex) {
            $this->logger()->error("Kibana failed health check.");
            throw $ex;
        }
    }
}
