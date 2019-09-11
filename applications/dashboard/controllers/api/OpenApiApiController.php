<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\OpenAPIBuilder;
use Vanilla\Web\Controller;
use Vanilla\Dashboard\Models\SwaggerModel;

/**
 * Returns the swagger spec for the APIv2.
 */
class OpenApiApiController extends Controller {
    /**
     * @var SwaggerModel The swagger model dependency.
     */
    private $swaggerModel;

    /**
     * @var OpenAPIBuilder The OpenAPI builder dependency.
     */
    private $openApiBuilder;

    /** @var bool */
    private $allowOpenApiAccess;

    /**
     * Construct a {@link SwaggerApiController}.
     *
     * @param SwaggerModel $swaggerModel The swagger model dependency.
     * @param OpenAPIBuilder $openApiBuilder The OpenAPI generator.
     * @param ConfigurationInterface $config
     */
    public function __construct(SwaggerModel $swaggerModel, OpenApiBuilder $openApiBuilder, ConfigurationInterface $config) {
        $this->swaggerModel = $swaggerModel;
        $this->openApiBuilder = $openApiBuilder;
        $this->allowOpenApiAccess = $config->get('OpenApi.AllowPublicAccess', false);
    }

    /**
     * Get the root swagger object.
     *
     * @return array Returns the swagger object as an array.
     * @deprecated
     */
    public function get_v2() {
        $this->permission('Garden.Settings.Manage');

        $this->schema(
            new Schema(['$ref' => 'https://raw.githubusercontent.com/OAI/OpenAPI-Specification/master/schemas/v2.0/schema.json']),
            'out'
        );

        $this->getSession()->getPermissions()->setAdmin(true);
        return $this->swaggerModel->getSwaggerObject();
    }

    /**
     * Get the Open API object for endpoints on add-ons.
     *
     * @param array $query The querystring.
     * @return array Returns the OpenAPI object as an array.
     */
    public function get_v3(array $query = []) {
        $this->permission();

        $in = $this->schema([
            'disabled:b' => [
                'description' => 'Show endpoints for disabled add-ons.',
                'default' => false,
            ],
            'hidden:b' => [
                'description' => 'Show hidden endpoints.',
                'default' => false
            ],
        ], 'in');
        $query = $in->validate($query);

        if (!$this->allowOpenApiAccess || (isset($query['hidden']) && $query['hidden'])) {
            $this->permission('Garden.Settings.Manage');
        }

        $result = $this->openApiBuilder->getEnabledOpenAPI($query['disabled'], $query['hidden']);
        return $result;
    }

    /**
     * Get summary counts for the API.
     *
     * @return array
     */
    public function get_v2Summary() {
        $all = $this->get_v2();

        $o = array_intersect_key($all, ['swagger' => 1, 'info' => 1, 'host' => 1, 'basePath' => 1, 'consumes' => 1]);

        $pathCount = 0;
        $endpointCount = 0;
        $resources = [];

        foreach ($all['paths'] as $path => $methods) {
            $resource = explode('/', $path, 3)[1];
            $resources["/$resource"] = 1;

            $pathCount++;
            foreach ($methods as $method => $endpoint) {
                $endpointCount++;
            }
        }

        $o['countResources'] = count($resources);
        $o['countPaths'] = $pathCount;
        $o['countEndpoints'] = $endpointCount;
        $o['resources'] = array_keys($resources);

        return $o;
    }
}
