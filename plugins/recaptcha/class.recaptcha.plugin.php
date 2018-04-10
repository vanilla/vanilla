<?php

/**
 * @copyright 2009-2018 Vanilla Forums Inc
 * @license Proprietary
 */

/**
 * Recaptcha Validation
 *
 * This plugin adds recaptcha validation to signups.
 *
 * Changes:
 *  0.1        Development
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package vanilla
 */
class RecaptchaPlugin extends Gdn_Plugin {

    /**
     * reCAPTCHA private key
     * @var string
     */
    protected $privateKey;

    /**
     * reCAPTCHA public key
     * @var string
     */
    protected $publicKey;

    /**
     * Plugin initialization.
     *
     */
    public function __construct() {
        parent::__construct();

        // Get keys from config
        $this->privateKey = c('Recaptcha.PrivateKey');
        $this->publicKey = c('Recaptcha.PublicKey');
    }

    /**
     * Override private key in memory.
     *
     * @param string $key
     */
    public function setPrivateKey($key) {
        $this->privateKey = $key;
    }

    /**
     * Get private key from memory.
     *
     * @return string
     */
    public function getPrivateKey() {
        return $this->privateKey;
    }

    /**
     * Override public key in memory.
     *
     * @param string $key
     */
    public function setPublicKey($key) {
        $this->publicKey = $key;
    }

    /**
     * Get public key from memory.
     *
     * @return string
     */
    public function getPublicKey() {
        return $this->publicKey;
    }

    /**
     * Validate a reCAPTCHA submission.
     *
     * @param string $captchaText
     * @return boolean
     * @throws Exception
     */
    public function validateCaptcha($captchaText) {
        $api = new Garden\Http\HttpClient('https://www.google.com/recaptcha/api');
        $data = [
            'secret' => $this->getPrivateKey(),
            'response' => $captchaText
        ];
        $response = $api->get('/siteverify', $data);

        if ($response->isSuccessful()) {
            $result = $response->getBody();
            $errorCodes = val('error_codes', $result);
            if ($result && val('success', $result)) {
                return true;
            } else if (!empty($errorCodes) && $errorCodes != ['invalid-input-response']) {
                throw new Exception(formatString(t('No response from reCAPTCHA.').' {ErrorCodes}', ['ErrorCodes' => join(', ', $errorCodes)]));
            }
        } else {
            throw new Exception(t('No response from reCAPTCHA.'));
        }

        return false;
    }

    /**
     * Hook (controller) to manage captcha config.
     *
     * @param SettingsController $sender
     */
    public function settingsController_registration_handler($sender) {
        $configurationModel = $sender->EventArguments['Configuration'];

        $manageCaptcha = c('Garden.Registration.ManageCaptcha', true);
        $sender->setData('_ManageCaptcha', $manageCaptcha);

        if ($manageCaptcha) {
            $configurationModel->setField('Recaptcha.PrivateKey');
            $configurationModel->setField('Recaptcha.PublicKey');
        }
    }

    /**
     * Hook to indicate a captcha service is available.
     *
     * @param Gdn_PluginManager $sender
     * @param array $args
     */
    public function captcha_isEnabled_handler($sender, $args) {
        $args['Enabled'] = true;
    }

    /**
     * Hook (view) to manage captcha config.
     *
     * THIS METHOD ECHOS DATA
     *
     * @param SettingsController $sender
     */
    public function captcha_settings_handler($sender) {
        echo $sender->fetchView('registration', 'settings', 'plugins/recaptcha');
    }

    /**
     * Hook (view) to render a captcha.
     *
     * THIS METHOD ECHOS DATA
     *
     * @param Gdn_Controller $sender
     */
    public function captcha_render_handler($sender) {
        echo $sender->fetchView('captcha', 'display', 'plugins/recaptcha');
    }

    /**
     * Hook to validate captchas.
     *
     * @param Gdn_PluginManager $sender
     * @return boolean
     * @throws Exception
     */
    public function captcha_validate_handler($sender) {
        $valid = &$sender->EventArguments['captchavalid'];

        $recaptchaResponse = Gdn::request()->post('g-recaptcha-response');
        if (!$recaptchaResponse) {
            return $valid = false;
        }

        return $valid = $this->validateCaptcha($recaptchaResponse);
    }

    /**
     * Hook to return captcha submission data.
     *
     * @param Gdn_PluginManager $sender
     */
    public function captcha_get_handler($sender) {
        $recaptchaResponse = Gdn::request()->post('g-recaptcha-response');
        if ($recaptchaResponse) {
            $sender->EventArguments['captchatext'] = $recaptchaResponse;
        }
    }

    /**
     * Display reCAPTCHA entry field.
     *
     * THIS METHOD ECHOS DATA
     *
     * @param Gdn_Form $sender
     * @return string
     */
    public function gdn_form_captcha_handler($sender) {
        if (!$this->getPrivateKey() || !$this->getPublicKey()) {
            echo '<div class="Warning">' . t('reCAPTCHA has not been set up by the site administrator in registration settings. This is required to register.') .  '</div>';
        }

        // Google whitelist https://developers.google.com/recaptcha/docs/language
        $whitelist = ['ar', 'bg', 'ca', 'zh-CN', 'zh-TW', 'hr', 'cs', 'da', 'nl', 'en-GB', 'en', 'fil', 'fi', 'fr', 'fr-CA', 'de', 'de-AT', 'de-CH', 'el', 'iw', 'hi', 'hu', 'id', 'it', 'ja', 'ko', 'lv', 'lt', 'no', 'fa', 'pl', 'pt', 'pt-BR', 'pt-PT', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'es-419', 'sv', 'th', 'tr', 'uk', 'vi'];

        // Use our current locale against the whitelist.
        $language = Gdn::locale()->language();
        if (!in_array($language, $whitelist)) {
            $language = (in_array(Gdn::locale()->Locale, $whitelist)) ? Gdn::locale()->Locale : false;
        }

        $scriptSrc = 'https://www.google.com/recaptcha/api.js?hl='.$language;

        $attributes = ['class' => 'g-recaptcha', 'data-sitekey' => $this->getPublicKey(), 'data-theme' => c('Recaptcha.Theme', 'light')];

        // see https://developers.google.com/recaptcha/docs/display for details
        $this->EventArguments['Attributes'] = &$attributes;
        $this->fireEvent('BeforeCaptcha');

        echo '<div '. attribute($attributes) . '></div>';
        echo '<script src="'.$scriptSrc.'"></script>';
    }

    /**
     * On plugin enable.
     *
     */
    public function setup() {}

}
