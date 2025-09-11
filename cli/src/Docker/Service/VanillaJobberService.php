<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponseException;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Cli\Commands\DockerJobberCommand;

/**
 * Service for dashboard.vanilla.local
 */
class VanillaJobberService extends AbstractLaravelService
{
    const SERVICE_ID = "jobber";

    public static array $requiredServiceIDs = [VanillaMySqlService::SERVICE_ID];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new LaravelServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "VNLA Jobber",
                containerName: "jobber",
                url: "https://jobber.vanilla.local",
                gitUrl: "git@github.com:vanilla/vnla-jobber.git"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getContainerCommands(): array
    {
        return [new DockerJobberCommand($this)];
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "WWWGROUP" => getmygid(),
            "WWWUSER" => getmyuid(),
            "LOCAL_VANILLA_CONF_DIR" => PATH_CONF,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function finishStart(): void
    {
        $this->artisanMigrate();
        $this->doInitialNpmInstallAndBuild();

        // Sync sites
        $this->logger()->info("> vnla docker:jobber artisan sites:sync");
        $this->getContainerCommands()[0]->executeSubCommand("artisan", ["sites:sync"]);

        parent::finishStart();
    }

    /**
     * Some sanity checking.
     *
     * @param array $envVariables
     * @return void
     */
    protected function validateEnvFile(array $envVariables): void
    {
        $expectedValues = [
            "APP_SERVICE" => "vnla-jobber",
            "APP_DEBUG" => "true",
            "APP_URL" => "https://jobber.vanilla.local",
            "APP_PORT" => "8042",
            "DB_HOST" => "mysql.vanilla.local",
            "DB_PORT" => "3306",
            "DB_USERNAME" => "root",
            "DB_PASSWORD" => "",
            "REDIS_HOST" => "jobber_redis",
        ];

        $schema = Schema::parse([]);

        foreach ($expectedValues as $key => $expectedValue) {
            $schema->addValidator($key, function ($value, ValidationField $field) use ($expectedValue) {
                if ($value !== $expectedValue) {
                    $field->addError("Expected value '{$expectedValue}', got '{$value}'");
                }
                return Invalid::value();
            });
        }
        $schema->validate($envVariables);
    }
}
