<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Controllers;

use VanillaTests\APIv2\AbstractAPIv2Test;

/**
 * Test the security settings.
 */
class SecuritySettingsTest extends AbstractAPIv2Test {


    /**
     * Test the domain settings on the security page.
     *
     * Notably the CSP domains are stored as an array and the trusted domains as a \n delimited string.
     */
    public function testDomainSettings() {
        $stringConfig1 = "domain.com\n*.glob.com";
        $arrayConfig1 = [
            "domain.com",
            "*.glob.com",
        ];
        $stringConfig2 = $stringConfig1 . "\ndomain2.com";
        $arrayConfig2 = array_merge($arrayConfig1, ["domain2.com"]);
        $config = \Gdn::config();
        $config->saveToConfig([
            \SettingsController::CONFIG_TRUSTED_DOMAINS => $stringConfig1,
            \SettingsController::CONFIG_CSP_DOMAINS => $arrayConfig1,
        ]);

        // Load the page.
        $html = $this->bessy()->getHtml("/dashboard/settings/security");
        $html->assertCssSelectorText(
            "#Form_Garden-dot-TrustedDomains",
            $stringConfig1
        );
        $html->assertCssSelectorText(
            "#Form_ContentSecurityPolicy-dot-ScriptSrc-dot-AllowedDomains",
            $stringConfig1
        );

        // Update the form.
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-TrustedDomains" => $stringConfig2,
            "ContentSecurityPolicy-dot-ScriptSrc-dot-AllowedDomains" => $stringConfig2,
        ]);

        $this->assertConfigValue(\SettingsController::CONFIG_TRUSTED_DOMAINS, $stringConfig2);
        $this->assertConfigValue(\SettingsController::CONFIG_CSP_DOMAINS, $arrayConfig2);
    }
}
