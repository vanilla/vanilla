<?php
/**
 * @author Pavel Goncharov  <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Dashboard;

use VanillaTests\UsersAndRolesApiTestTrait;

trait JITTTranslationTestTrait
{
    use UsersAndRolesApiTestTrait;

    /**
     * Set up a normal configuration for jit translations. Should be called by `setup()`.
     *
     * @param array $sources
     * @return void
     */
    protected function setupJITTranslations(): void
    {
        $config = [
            "JustInTimeTranslated" => [
                "enabled" => true,
                "languageCount" => 3,
                "languageSelected" => ["vf_fr" => "fr", "vf_en" => "en", "vf_ru" => "ru"],
                "wordsNotToTranslate" => "test,hello",
            ],
        ];
        \Gdn::config()->saveToConfig($config, options: false);
    }
}
