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
 * Service for logs.vanilla.local
 */
class VanillaLogsService extends AbstractService
{
    const SERVICE_ID = "logs";

    /**
     * Constructor.
     *
     * @param KibanaHttpClient $kibanaClient
     */
    public function __construct(private KibanaHttpClient $kibanaClient)
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Vanilla Logs",
                containerName: "kibana",
                url: "https://logs.vanilla.local"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.elastic.yml:./docker-compose.logs.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        parent::start();
        $this->checkElasticRunning();
        $this->kibanaClient->setupIndexes();
    }

    /**
     * Validate that the elastic search logs are running.
     */
    private function checkElasticRunning(): void
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
