<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard\Utils;

use Vanilla\Models\CustomPageModel;
use VanillaTests\Http\TestHttpClient;
use VanillaTests\VanillaTestCase;

/**
 * @method TestHttpClient api()
 */
trait CustomPagesApiTestTrait
{
    /**
     * Return a valid custom page payload.
     *
     * @param array $overrides
     * @return array
     * @throws \Exception If custom page failed to be created.
     */
    protected function createCustomPage(array $overrides = []): array
    {
        $params = $overrides + [
            "seoTitle" => "Hello World",
            "seoDescription" => "Hello World",
            "urlcode" => "/hello-world-" . VanillaTestCase::makeRandomKey(),
            "status" => CustomPageModel::STATUS_PUBLISHED,
            "layoutData" => [
                "name" => "test",
                "layout" => [],
            ],
        ];

        $result = $this->api()
            ->post("/custom-pages", $params)
            ->getBody();

        $customPageID = $result["customPageID"] ?? null;

        if (is_null($customPageID)) {
            throw new \Exception("Failed to create a custom page because customPageID is null");
        }

        return $result;
    }
}
