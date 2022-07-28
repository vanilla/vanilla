<?php
if (!defined('APPLICATION')){
    exit();
}
echo '<li class="CaptchaInput">';
echo $this->Form->label("Security Check", '');
if (!c('Recaptcha.PrivateKey') || !c('Recaptcha.PublicKey')) {
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

$attributes = [
    'class' => 'g-recaptcha',
    'data-sitekey' => c('Recaptcha.PublicKey'),
    'data-theme' => c('Recaptcha.Theme', 'light')
];

// see https://developers.google.com/recaptcha/docs/display for details
$this->EventArguments['Attributes'] = &$attributes;
$this->fireEvent('BeforeCaptcha');

echo '<div '. attribute($attributes) . '></div>';
echo '<script src="'.$scriptSrc.'"></script>';
echo '</li>';