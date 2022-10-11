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
class SecuritySettingsTest extends AbstractAPIv2Test
{
    /**
     * Test the domain settings on the security page.
     *
     * Notably the CSP domains are stored as an array and the trusted domains as a \n delimited string.
     */
    public function testDomainSettings()
    {
        $stringConfig1 = "domain.com\n*.glob.com";
        $arrayConfig1 = ["domain.com", "*.glob.com"];
        $stringConfig2 = $stringConfig1 . "\ndomain2.com";
        $arrayConfig2 = array_merge($arrayConfig1, ["domain2.com"]);
        $config = \Gdn::config();
        $config->saveToConfig([
            \SettingsController::CONFIG_TRUSTED_DOMAINS => $stringConfig1,
            \SettingsController::CONFIG_CSP_DOMAINS => $arrayConfig1,
        ]);

        // Load the page.
        $html = $this->bessy()->getHtml("/dashboard/settings/security");
        $html->assertCssSelectorText("#Form_Garden-dot-TrustedDomains", $stringConfig1);
        $html->assertCssSelectorText("#Form_ContentSecurityPolicy-dot-ScriptSrc-dot-AllowedDomains", $stringConfig1);

        // Update the form.
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-TrustedDomains" => $stringConfig2,
            "ContentSecurityPolicy-dot-ScriptSrc-dot-AllowedDomains" => $stringConfig2,
            "Garden-dot-Password-dot-MinLength" => \SettingsController::DEFAULT_PASSWORD_LENGTH,
        ]);

        $this->assertConfigValue(\SettingsController::CONFIG_TRUSTED_DOMAINS, $stringConfig2);
        $this->assertConfigValue(\SettingsController::CONFIG_CSP_DOMAINS, $arrayConfig2);
    }

    /**
     * Password Minimum Length settings throws an exception on values
     * less than default minimum.
     */
    public function testPasswordSettingThrowsMinimumValueException()
    {
        $validationMinMessage = "Password minimum length value should be greater than or equal 8.";
        $minimumPasswordLength = \SettingsController::DEFAULT_PASSWORD_LENGTH;

        //validate we are getting successful error message on empty / invalid or a value less than default Minimum
        $this->expectException(\Garden\Schema\ValidationException::class);
        $this->expectExceptionMessage($validationMinMessage);
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-Password-dot-MinLength" => $minimumPasswordLength - 2,
        ]);
    }

    /**
     * Throw an exception if the given value is not an integer.
     */
    public function testPasswordSettingThrowsIntegerException()
    {
        $ValidationIntMessage = "garden.Password.MinLength is not a valid integer";
        $minimumPasswordLength = \SettingsController::DEFAULT_PASSWORD_LENGTH;
        $nonIntNumber = (float) $minimumPasswordLength + 0.865;

        //validate we are getting successful error message on non integer value
        $this->expectException(\Garden\Schema\ValidationException::class);
        $this->expectExceptionMessage($ValidationIntMessage);
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-Password-dot-MinLength" => $nonIntNumber,
        ]);
    }

    /**
     * Test to check Password Minimum Length setting is stored successfully.
     */
    public function testPasswordSetting()
    {
        $minimumPasswordLength = \SettingsController::DEFAULT_PASSWORD_LENGTH;
        //the configuration should be saved on values greater than password default minimum
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-Password-dot-MinLength" => $minimumPasswordLength + 1,
        ]);

        $this->assertConfigValue("Garden.Password.MinLength", $minimumPasswordLength + 1);
    }

    /**
     * Test to check SignIn Attempts and LockoutTime setting are stored successfully.
     */
    public function testLoginSetting()
    {
        //the configuration for SignIn should replace -1 with 0 on save
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-Password-dot-MinLength" => \SettingsController::DEFAULT_PASSWORD_LENGTH,
            "Garden-dot-SignIn-dot-Attempts" => -1,
            "Garden-dot-SignIn-dot-LockoutTime" => -1,
        ]);

        $this->assertConfigValue("Garden.SignIn.Attempts", 0);
        $this->assertConfigValue("Garden.SignIn.LockoutTime", 0);

        //the configuration for SignIn should save
        $this->bessy()->post("/dashboard/settings/security", [
            "Garden-dot-Password-dot-MinLength" => \SettingsController::DEFAULT_PASSWORD_LENGTH,
            "Garden-dot-SignIn-dot-Attempts" => 10,
            "Garden-dot-SignIn-dot-LockoutTime" => 505,
        ]);

        $this->assertConfigValue("Garden.SignIn.Attempts", 10);
        $this->assertConfigValue("Garden.SignIn.LockoutTime", 505);
    }
}
