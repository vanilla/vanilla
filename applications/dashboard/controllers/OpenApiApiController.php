<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Dashboard\Controllers;

use Garden\Schema\Schema;
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
     * Construct a {@link SwaggerApiController}.
     *
     * @param SwaggerModel $swaggerModel The swagger model dependency.
     */
    public function __construct(SwaggerModel $swaggerModel) {
        $this->swaggerModel = $swaggerModel;
    }

    /**
     * Get the root swagger object.
     *
     * @return array Returns the swagger object as an array.
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
