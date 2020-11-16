<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\FileUtils;
use Vanilla\OpenAPIBuilder;
use Vanilla\Web\Controller;

/**
 * API endpoints for the /config resource.
 */
final class ConfigApiController extends Controller {
    public const PERM_PUBLIC = 'public';
    public const PERM_MEMBER = 'member';
    public const PERM_MODERATOR = 'community.moderate';
    public const PERM_COMMUNITY_MANAGER = 'community.manage';
    public const PERM_ADMIN = 'site.manage';

    /**
     * All of the permissions that are valid for config reading.
     */
    public const READ_PERMS = [
        self::PERM_PUBLIC,
        self::PERM_MEMBER,
        self::PERM_MODERATOR,
        self::PERM_COMMUNITY_MANAGER,
        self::PERM_ADMIN,
    ];

    /**
     * All of the permissions that are valid for config writing.
     */
    public const WRITE_PERMS = [
        self::PERM_COMMUNITY_MANAGER,
        self::PERM_ADMIN,
    ];

    /**
     * @var OpenAPIBuilder
     */
    private $apiBuilder;

    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * ConfigApiController constructor.
     *
     * @param OpenAPIBuilder $apiBuilder
     * @param ConfigurationInterface $config
     * @param string $cachePath
     */
    public function __construct(OpenAPIBuilder $apiBuilder, ConfigurationInterface $config, string $cachePath = '') {
        $this->apiBuilder = $apiBuilder;
        $this->config = $config;
        $this->cachePath = $cachePath ?: PATH_CACHE.'/config-schema.php';
    }

    /**
     * The GET /api/v2/config endpoint.
     *
     * @param array $query
     * @return Data
     */
    public function get(array $query = []): Data {
        $in = $this->schema([
            'select?' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string'
                ],
                'style' => 'form',
            ]
        ], 'in');
        $query = $in->validate($query);

        $select = $query['select'] ?? null;

        $out = $this->getConfigSchema();
        $result = [];
        foreach ($out->getField('properties') as $key => $item) {
            if (is_array($select)) {
                $matched = false;
                foreach ($select as $pattern) {
                    if (fnmatch($pattern, $key)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            $permission = $this->realPermissionName($item['x-read'] ?? self::PERM_ADMIN);
            if ($permission === 'public' ||
                ($permission === self::PERM_MEMBER && $this->getSession()->isValid()) ||
                $this->getSession()->checkPermission($permission)
            ) {
                $configKey = $item['x-key'] ?? $key;
                $result[$key] = $this->config->get($configKey, $item['default'] ?? null);
            }
        }
        return new Data($result);
    }

    /**
     * The PATCH /api/v2/config endpoint
     *
     * @param array $body
     * @return Data
     */
    public function patch(array $body): Data {
        $in = $this->getConfigSchema();

        // Make sure the user has all necessary permissions.
        $permissions = [];
        foreach ($in->getField('properties') as $key => $item) {
            if (array_key_exists($key, $body)) {
                $permissions[$this->realPermissionName($item['x-write'] ?? self::PERM_ADMIN)] = true;
            }
        }
        $this->permission(array_keys($permissions));

        $in->setFlag(Schema::VALIDATE_EXTRA_PROPERTY_EXCEPTION, true);
        $valid = $in->validate($body, true);
        $this->config->saveToConfig($valid);

        return new Data(null);
    }

    /**
     * Return the config data schema.
     *
     * @return Data
     */
    public function get_schema(): Data {
        $this->permission('Garden.Settings.Manage');

        $schema = $this->getConfigSchema();
        $r = new Data($schema->getSchemaArray());
        return $r;
    }

    /**
     * Get the config schema.
     *
     * @return Schema
     */
    private function getConfigSchema(): Schema {
        if (file_exists($this->cachePath)) {
            $r = FileUtils::getExport($this->cachePath);
        } else {
            $r = $this->buildConfigSchemaArray();
            FileUtils::putExport($this->cachePath, $r);
        }
        return new Schema($r);
    }

    /**
     * Build the config schema.
     *
     * @return array
     */
    private function buildConfigSchemaArray(): array {
        $openAPI = $this->apiBuilder->getFullOpenAPI();
        $config = $openAPI['components']['schemas']['Config'];
        ksort($config['properties']);

        return $config;
    }

    /**
     * Get the real permission name corresponding to the permission requested.
     *
     * @param string $permission
     * @return string
     */
    private function realPermissionName(string $permission) {
        if (in_array($permission, [self::PERM_PUBLIC, self::PERM_MEMBER])) {
            return $permission;
        }

        return $this->getSession()->getPermissions()->untranslatePermission($permission);
    }
}
