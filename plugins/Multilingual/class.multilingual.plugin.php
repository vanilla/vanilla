<?php

/**
 * Multilingual Plugin
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

/* Change Log
   1.0 - Make MobileFriendly //Lincoln 2012-01-13
   1.1 - Move locale setting to later in startup for Embed //Lincoln 2012-02-22
   1.2 - Create locale chooser module //Lincoln 2014-08-13
   1.3 - Updates to accommodate locale canonicalization //Todd 2015-03-20
*/

/**
 * Allows multiple locales to work in Vanilla.
 *
 * You can trigger an alternate locale by adding 'locale' in the query string,
 * setting var vanilla_embed_locale in an embedded forum, or selecting one of the
 * language links added to the footer. User-selected locale takes precedence.
 * The selected locale is stored in the session. If it is user-selected AND the
 * user is logged in, it is stored in UserMeta.
 *
 * @example http://example.com/discussions?locale=de-DE
 * @example <script>var vanilla_embed_locale = 'de-DE';</script>
 */
class MultilingualPlugin extends Gdn_Plugin {
    protected static $overrides = [
        'nso' => ['Name' => 'Sesotho sa Leboa'],
        'zu_ZA' => ['Name' => 'isiZulu']
    ];

    /**
     * Return the enabled locales suitable for local choosing.
     *
     * @return array Returns an array in the form `[locale => localeName]`.
     */
    public static function enabledLocales() {
        $defaultLocale = Gdn_Locale::canonicalize(c('Garden.Locale'));

        $localeModel = new LocaleModel();
        if (class_exists('Locale')) {
            $localePacks = $localeModel->enabledLocalePacks(false);
            $locales = [];
            foreach ($localePacks as $locale) {
                if (isset(static::$overrides[$locale]['Name'])) {
                    $locales[$locale] = static::$overrides[$locale]['Name'];
                } else {
                    $locales[$locale] = mb_convert_case(Locale::getDisplayName($locale, $locale), MB_CASE_TITLE, "UTF-8");
                }
            }
            $defaultName = Locale::getDisplayName($defaultLocale, $defaultLocale);
        } else {
            $locales = $localeModel->enabledLocalePacks(true);
            $locales = array_column($locales, 'Name', 'Locale');
            $defaultName = $defaultLocale === 'en' ? 'English' : $defaultLocale;
        }
        asort($locales);

        if (!array_key_exists($defaultLocale, $locales)) {
            $locales = array_merge(
                [$defaultLocale => $defaultName],
                $locales
            );
        }

        return $locales;
    }


    /**
     * Set user's preferred locale.
     *
     * Moved event from AppStart to AfterAnalyzeRequest to allow Embed to set P3P header first.
     */
    public function gdn_dispatcher_afterAnalyzeRequest_handler($sender) {
        // Set user preference
        if ($tempLocale = $this->getAlternateLocale()) {
            Gdn::locale()->set($tempLocale, Gdn::applicationManager()->enabledApplicationFolders(), Gdn::pluginManager()->enabledPluginFolders());
        }
    }

    /**
     * Show alternate locale options in Foot.
     */
    public function base_render_before($sender) {
        // Not in Dashboard
        // Block guests until guest sessions are restored
        if ($sender->MasterView == 'admin' || !checkPermission('Garden.SignIn.Allow'))
            return;

        $sender->addModule('LocaleChooserModule');

        // Add a simple style
        $sender->addAsset('Head', '<style>.LocaleOption { padding-left: 10px; } .LocaleOptions { padding: 10px; } .Dashboard .LocaleOptions { display: none; }</style>');
    }

    /**
     * Get user preference or queried locale.
     */
    protected function getAlternateLocale() {
        $locale = false;

        // User preference
        if (Gdn::session()->isValid()) {
            $locale = $this->getUserMeta(Gdn::session()->UserID, 'Locale', false);
            $locale = val('Plugin.Multilingual.Locale', $locale, false);
        }
        // Query string
        if (!$locale) {
            $locale = $this->validateLocale(Gdn::request()->get('locale'));
            if ($locale) {
                Gdn::session()->stash('Locale', $locale);
            }
        }
        // Session
        if (!$locale) {
            $locale = Gdn::session()->stash('Locale', '', false);
        }

        return $locale;
    }

    /**
     * Allow user to set their preferred locale via link-click.
     *
     * @param ProfileController $sender
     * @param String $locale Two letter locale code to be saved to the user's profile as default language.
     * @throws Exception
     */
    public function profileController_setLocale_create($sender, $locale) {
        if (!Gdn::session()->UserID) {
            throw permissionException('Garden.SignIn.Allow');
        }
        $redirectURL = $_SERVER['HTTP_REFERER'] ?? url('/');
        if ($sender->Form->authenticatedPostBack()) {
            // If we got a valid locale, save their preference
            if (isset($locale)) {
                $locale = $this->validateLocale($locale);
                if ($locale) {
                    $this->setUserMeta(Gdn::session()->UserID, 'Locale', $locale);
                }
            }

            $target = gdn::request()->get('Target');
            if ($target) {
                $redirectURL = $target;
            }
            // Back from whence we came.
            redirectTo($redirectURL);
        }

        redirectTo($redirectURL);
    }

    /**
     * Confirm selected locale is valid and available.
     *
     * @param string $locale Locale code.
     * @return string Returns the canonical version of the locale on success or an empty string otherwise.
     */
    protected function validateLocale($locale) {
        $canonicalLocale = Gdn_Locale::canonicalize($locale);
        $locales = static::enabledLocales();

        $result = isset($locales[$canonicalLocale]) ? $canonicalLocale : '';
        return $result;
    }
}
