<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Controller for the gpc endpoints.
 */
class GpcApiController extends AbstractApiController
{
    /** @var ConfigurationInterface */
    private ConfigurationInterface $configuration;

    /**
     * Inject dependencies.
     *
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Display the Global Privacy Control (GPC) configuration.
     *
     * Called by `/.well-known/gpc`.
     *
     * @throws ValidationException
     */
    public function get(): Data
    {
        $data = $this->configuration->get("gpc");
        if (isset($data["enabled"])) {
            $data["gpc"] = $data["enabled"];
            unset($data["enabled"]);
        }

        $out = $this->schema(Schema::parse(["gpc:b", "lastUpdate:dt"]));
        $out->validate($data);

        return new Data(data: $data, headers: ["Content-Type" => "application/json"]);
    }
}
