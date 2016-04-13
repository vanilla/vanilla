<?php

/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

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
class Captcha {

    /**
     * Should we expect captcha submissions?
     *
     * @return boolean
     */
    public static function enabled() {
        return !c('Garden.Registration.SkipCaptcha', false);
    }

    /**
     * Wrapper for captcha rendering
     *
     * Allows conditional ignoring of captcha rendering if skipped in the config.
     */
    public static function render() {
        if (!Captcha::enabled()) {
            return;
        }

        // Hook to allow rendering of captcha form
        Gdn::pluginManager()->fireAs('captcha')->fireEvent('render');
    }

    /**
     * Validate captcha
     *
     * @param mixed $value
     */
    public static function validate($value = null) {

        if (is_null($value)) {
            // Get captcha text
            $captchaText = null;
            Gdn::pluginManager()->EventArguments['captchatext'] = &$captchaText;
            Gdn::pluginManager()->fireAs('captcha')->fireEvent('get', [
                'captcha' => $value
            ]);
            $value = $captchaText;
        }

        if (is_null($value)) {
            return false;
        }

        // Validate captcha text

        // Assume invalid submission
        $valid = false;

        Gdn::pluginManager()->EventArguments['captchavalid'] = &$valid;
        Gdn::pluginManager()->fireAs('captcha')->fireEvent('validate', [
            'captcha' => $value
        ]);
        $isValid = $valid ? true : false;
        unset(Gdn::pluginManager()->EventArguments['captchavalid']);
        return $isValid;
    }

}