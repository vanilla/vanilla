<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use Vanilla\Http\InternalClient;
use VanillaTests\UsersAndRolesApiTestTrait;

trait AiSuggestionsTestTrait
{
    use UsersAndRolesApiTestTrait;

    /**
     * Set up a normal configuration for AI suggestions. Should be called by `setup()`.
     *
     * @param array $sources
     * @return void
     */
    protected function setupAiSuggestions(array $sources = ["mockSuggestions"]): void
    {
        $assistantUser = $this->createUser();

        $config = [
            "Feature.customLayout.post.Enabled" => true,
            "Feature.AISuggestions.Enabled" => true,
            "aiSuggestions" => [
                "enabled" => true,
                "userID" => $assistantUser["userID"],
                "sources" => [
                    "mockSuggestion" => ["enabled" => false],
                ],
                "delay" => [
                    "unit" => "hour",
                    "length" => 0,
                ],
            ],
        ];
        foreach ($sources as $source) {
            $config["aiSuggestions"]["sources"][$source] = ["enabled" => true];
        }
        \Gdn::config()->saveToConfig($config, options: false);
    }
}
