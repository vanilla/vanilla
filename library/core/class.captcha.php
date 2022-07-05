<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\ReCaptchaVerification;

/**
 * Captcha handler
 *
 * Base functionality and hook points for captcha functionality.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package vanilla
 * @subpackage core
 * @since 2.2
 */
class Captcha
{
    /**
     * Should we expect captcha submissions?
     *
     * @return boolean
     */
    public static function enabled()
    {
        $enabled = !Gdn::config("Garden.Registration.SkipCaptcha", false);
        return $enabled;
    }

    /**
     * Wrapper for captcha settings.
     *
     * Allows conditional ignoring of captcha settings if disabled in the config.
     *
     * @param SettingsController $settingsController
     * @return null
     */
    public static function settings(SettingsController $settingsController)
    {
        $recaptchaFields = [
            "Recaptcha.PrivateKey",
            "Recaptcha.PublicKey",
            "RecaptchaV3.PublicKey",
            "RecaptchaV3.PrivateKey",
        ];

        // If the ManageCaptcha config is explicitly set to false, we do not carry on.
        if (!Gdn::config("Garden.Registration.ManageCaptcha", true)) {
            return null;
        }

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);

        $manageCaptcha = Gdn::config("Garden.Registration.ManageCaptcha", true);
        $settingsController->setData("_ManageCaptcha", $manageCaptcha);

        if ($manageCaptcha) {
            foreach ($recaptchaFields as $recaptchaField) {
                $configurationModel->setField($recaptchaField);
            }

            if (!$settingsController->Form->authenticatedPostBack()) {
                // Apply the config settings to the form.
                $settingsController->Form->setData($configurationModel->Data);
            } else {
                $config = \Gdn::config();

                // Save every config value.
                foreach ($recaptchaFields as $recaptchaField) {
                    $recaptchaFieldValue = $settingsController->Form->getFormValue($recaptchaField, "");
                    $config->saveToConfig($recaptchaField, $recaptchaFieldValue);
                }
            }

            // Rendering captcha settings form.
            echo $settingsController->fetchView("recaptchaoptions");
        }

        return null;
    }

    /**
     * Wrapper for captcha rendering.
     *
     * Allows conditional ignoring of captcha rendering if skipped in the config.
     *
     * @param Gdn_Controller $controller
     * @return null;
     */
    public static function render($controller)
    {
        if (!Captcha::enabled()) {
            return null;
        }

        // Rendering of captcha form.
        echo $controller->fetchView("recaptcha");
        return null;
    }

    /**
     * Validate captcha.
     *
     * @param mixed $captchaText
     * @return boolean validity of captcha submission
     */
    public static function validate($captchaText = null)
    {
        if (!Captcha::enabled()) {
            return null;
        }

        if (is_null($captchaText)) {
            // Get captcha text.
            $recaptchaResponse = Gdn::request()->post("g-recaptcha-response");
            if ($recaptchaResponse) {
                $captchaText = $recaptchaResponse;
            }
        }

        if (is_null($captchaText)) {
            return false;
        }

        // Validate captcha text
        // Assume invalid submission
        $isValid = false;

        $api = new Garden\Http\HttpClient("https://www.google.com/recaptcha/api");
        $data = [
            "secret" => Gdn::config("Recaptcha.PrivateKey"),
            "response" => $captchaText,
        ];
        $response = $api->get("/siteverify", $data);

        if ($response->isSuccessful()) {
            $result = $response->getBody();
            $errorCodes = val("error_codes", $result);
            if ($result && val("success", $result)) {
                $isValid = true;
            } elseif (!empty($errorCodes) && $errorCodes != ["invalid-input-response"]) {
                throw new Exception(
                    formatString(t("No response from reCAPTCHA.") . " {ErrorCodes}", [
                        "ErrorCodes" => join(", ", $errorCodes),
                    ])
                );
            }
        } else {
            throw new Exception(t("No response from reCAPTCHA."));
        }

        return (bool) $isValid;
    }
}
