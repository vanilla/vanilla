<?php
namespace Vanilla\Dashboard\Controllers;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Redirect;
use Vanilla\ApiUtils;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\PageDispatchController;
use Vanilla\Utility\UrlUtils;

/**
 * Controller for /home/leaving page.
 */
class LeavingController extends PageDispatchController
{
    /** @var ConfigurationInterface */
    private ConfigurationInterface $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Handle /home/leaving.
     *
     * @param array $query
     * @return \Garden\Web\Data|Redirect
     */
    public function index(array $query)
    {
        if (!$this->configuration->get("Garden.Format.WarnLeaving", true)) {
            // This page is not accessible when leaving page is disabled.
            throw new NotFoundException("Page");
        }

        $schema = Schema::parse([
            "allowTrusted:b" => [
                "default" => false,
            ],
            "target:s?",
            "Target:s?",
        ])->requireOneOf(["target", "Target"]);

        $query = $schema->validate($query);

        $target = $query["target"] ?? $query["Target"];
        $allowTrusted = $query["allowTrusted"];

        if ($allowTrusted && isTrustedDomain($target)) {
            return new Redirect($target, 302);
        }

        return $this->useSimplePage("Leaving")
            ->permission()
            ->setSeoTitle(t("Leaving"))
            ->blockRobots()
            ->render();
    }
}
