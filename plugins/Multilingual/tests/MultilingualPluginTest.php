<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Multilingual;

use Vanilla\Addon;
use Vanilla\Models\AddonModel;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the multilingual plugin.
 */
class MultilingualPluginTest extends SiteTestCase
{
    use EventSpyTestTrait;

    public static $addons = ["Multilingual"];

    /**
     * Test the following:
     *
     * - Locale can be applied through a query parameter.
     * - Locale sticks into the session.
     * - Event handler locale definitions still apply.
     */
    public function testStickyLocales()
    {
        // Add a fr language locale.
        $testLocaleAddon = new Addon("/tests/fixtures/locales/test-fr");
        \Gdn::addonManager()->add($testLocaleAddon);
        $addonModel = $this->container()->get(AddonModel::class);
        $addonModel->enable($testLocaleAddon);

        // Locale key gets set through the query param and applied.
        $response = $this->bessy()
            ->getJsonData("/testtranslated/foo?locale=fr")
            ->getData();
        $this->assertEquals("bar-fr", $response["foo"]);
        $this->assertEquals("fr", $response["currentLocaleKey"]);

        // Locale is stashed in session and the afterSet_handler still worked.
        $response = $this->bessy()
            ->getJsonData("/testtranslated/overridden")
            ->getData();
        $this->assertEquals("inEventHandler", $response["overridden"]);
        $this->assertEquals("fr", $response["currentLocaleKey"]);
    }

    /**
     * Event handler mimicking vfcom's console locale loading.
     *
     * @param \Gdn_Locale $locale
     */
    public function gdn_locale_afterSet_handler(\Gdn_Locale $locale)
    {
        $translations = [
            "overridden" => "inEventHandler",
        ];

        $locale->LocaleContainer->loadArray($translations, "ClientLocale", "Definition", true);
    }

    /**
     * Controller definition just for the test:
     *
     * - Renders back the translated version of what was passed.
     * - Renders back the current locale key.
     *
     * @param \RootController $controller
     * @param string $source
     */
    public function rootController_testTranslated_create(\RootController $controller, string $source)
    {
        $controller->setData($source, t($source));
        $controller->setData("currentLocaleKey", \Gdn::locale()->current());
        $controller->renderData();
    }
}
