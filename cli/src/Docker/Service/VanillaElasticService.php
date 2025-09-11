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
 * Service for elastic.vanilla.local
 */
class VanillaElasticService extends AbstractService
{
    const SERVICE_ID = "elastic";

    /**
     * @param ElasticSearchHttpClient $elasticClient
     */
    public function __construct(private ElasticSearchHttpClient $elasticClient)
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Elastic Search",
                containerName: "elasticsearch",
                url: "https://elastic.vanilla.local"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.elastic.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        parent::start();
        $this->setup();
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "EnabledPlugins.syslogger" => true,
        ];
    }

    /**
     * Perform additional setup of indexes.
     */
    private function setup(): void
    {
        $this->checkElasticRunning();
        $this->elasticClient->setupIndexes();
    }

    /**
     * Validate that the elastic search logs are running.
     */
    private function checkElasticRunning(): void
    {
        $this->logger()->title("Checking ElasticSearch Health");
        try {
            for ($i = 0; $i < 11; $i++) {
                try {
                    $this->elasticClient->healthCheck();
                    $this->logger()->success("ElasticSearch is healthy");
                    return;
                } catch (\Exception $e) {
                    $this->logger()->warning("Attempt #$i Failed");
                    // We might still be starting up.
                    if ($i === 9) {
                        throw $e;
                    } else {
                        // try again.
                        sleep(3);
                    }
                }
            }
        } catch (HttpException $ex) {
            $this->logger()->error("ElasticSearch failed health check.");
            throw $ex;
        }
    }
}
