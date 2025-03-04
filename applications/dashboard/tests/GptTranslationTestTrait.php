<?php
/**
 * @author Pavel Goncharov  <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use MachineTranslation\Services\GptTranslationService;
use Vanilla\Forum\Models\CommunityMachineTranslationModel;
use VanillaTests\UsersAndRolesApiTestTrait;

trait GptTranslationTestTrait
{
    use UsersAndRolesApiTestTrait;

    /**
     * Set up a normal configuration for Gpt translations service. Should be called by `setup()`.
     *
     * @return void
     */
    protected function setupGptTranslations(): void
    {
        $config = [
            GptTranslationService::CONFIG_KEY => [
                "locales" => ["vf_fr" => "fr", "vf_en" => "en", "vf_ru" => "ru"],
                "wordsNotToTranslate" => "test,hello",
                "maxLocale" => 3,
            ],
            "Feature.aiFeatures.Enabled" => true,
            CommunityMachineTranslationModel::FEATURE_FLAG => true,
        ];
        \Gdn::config()->saveToConfig($config, options: false);
    }
}
