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
 * Service for elastic.vanilla.localhost
 */
class VanillaElasticService extends AbstractService
{
    const SERVICE_NAME = "elastic";

    private ElasticSearchHttpClient $elasticClient;

    /**
     * @param ElasticSearchHttpClient $elasticClient
     */
    public function __construct(ElasticSearchHttpClient $elasticClient)
    {
        $this->elasticClient = $elasticClient;
    }

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.elastic.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Vanilla Elastic";
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
        return "elastic.vanilla.localhost";
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
        return [
            "EnabledPlugins.syslogger" => true,
        ];
    }

    /**
     * Perform additional setup of indexes.
     */
    private function setup()
    {
        $this->checkElasticRunning();
        $this->elasticClient->setupIndexes();
    }

    /**
     * Validate that the elastic search logs are running.
     */
    private function checkElasticRunning()
    {
        $this->logger()->title("Checking ElasticSearch Health");
        try {
            for ($i = 0; $i < 11; $i++) {
                try {
                    $this->elasticClient->healthCheck();
                    $this->logger()->success("ElasticSearch is healthy");
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
            $this->logger()->error("ElasticSearch failed health check.");
            throw $ex;
        }
    }
}
