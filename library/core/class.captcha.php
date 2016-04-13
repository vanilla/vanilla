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