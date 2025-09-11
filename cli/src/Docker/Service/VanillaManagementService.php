<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Webmozart\PathUtil\Path;

/**
 * Service for dashboard.vanilla.local
 */
class VanillaManagementService extends AbstractLaravelService
{
    const SERVICE_ID = "management";

    public static array $requiredServiceIDs = [VanillaMySqlService::SERVICE_ID];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new LaravelServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Management Dashboard",
                containerName: "management-dashboard",
                url: "https://management.vanilla.local",
                gitUrl: "git@github.com:vanillaops/management-dashboard.git"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "WWWGROUP" => getmygid(),
            "WWWUSER" => getmyuid(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function finishStart(): void
    {
        $this->artisanMigrate();
        $this->doInitialNpmInstallAndBuild();

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
            "APP_SERVICE" => "management-dashboard",
            "APP_DEBUG" => "true",
            "APP_URL" => "https://dashboard.vanilla.local",
            "APP_PORT" => "8042",
            "DB_HOST" => "mysql.vanilla.local",
            "DB_PORT" => "3306",
            "DB_USERNAME" => "root",
            "DB_PASSWORD" => "",
            "JWT_PRIVATE_KEY" => "localhostprivate",
            "REDIS_HOST" => "management_redis",
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
