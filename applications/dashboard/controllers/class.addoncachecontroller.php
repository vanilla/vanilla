<?php
/**
 * Provides endpoint for interfacing with the addon cache.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.4
 */

/**
 * Handles /addoncache endpoint
 */
class AddonCacheController extends DashboardController {

    /**
     * AddonCacheController constructor.
     *
     * @throws Gdn_UserException if endpoints are disabled in config.
     */
    public function __construct() {
        if (c('Cache.Addons.DisableEndpoints')) {
            throw new Gdn_UserException('Addon cache endpoints disabled', 403);
        }

        parent::__construct();
    }

    /**
     * Clear the addon cache.
     *
     * @throws Exception if using an invalid method.
     */
    public function clear($target) {
        $this->permission('Garden.Settings.Manage');

        if (Gdn::request()->isPostBack() === false) {
            throw new Exception('Requires POST', 405);
        }

        Gdn::request()->isAuthenticatedPostBack(true);

        $cleared = Gdn::addonManager()->clearCache();

        if ($cleared) {
            $this->informMessage(t('Addon cache cleared.'));
        } else {
            $this->informMessage(t('Unable to clear addon cache.'));
        }

        if (!empty($target)) {
            $this->setRedirectTo($target);
        }

        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Verify the addon cache is current.
     *
     * @param string $type
     * @throws Exception if no type specified.
     */
    public function verify($type) {
        $this->permission('Garden.Settings.Manage');

        if ($type === null) {
            throw new Exception('Type required');
        }

        $cached = Gdn::addonManager()->lookupAllByType($type);
        $current = Gdn::addonManager()->scan($type);

        $new = array_keys(array_diff_key($current, $cached));
        $invalid = array_keys(array_diff_key($cached, $current));

        $updateRequired = (count($new) || count($invalid));

        if ($updateRequired) {
            $clearUrl = '/addoncache/clear?Target={SelfUrl}';
            $actions = wrap(
                anchor(t('Click here to fix.'), $clearUrl, 'Hijack js-inform-close'),
                'div',
                ['class' => 'Actions']
            );

            $this->informMessage(
                t('The addon cache is outdated.').$actions,
                ['CssClass' => 'Dismissable', 'id' => 'CheckSummary']
            );

            if (debug()) {
                Logger::event(
                    'addoncache_outdated',
                    Logger::INFO,
                    'Addon cache outdated',
                    [
                        'type' => $type,
                        'new' => $new,
                        'invalid' => $invalid,
                        'current' => array_keys($current),
                        'cached' => array_keys($cached)
                    ]
                );
            }
        }

        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render('blank', 'utility', 'dashboard');
    }
}
