<?php
/**
 * Provides endpoint for interfacing with the addon cache.
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
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
    public function clear() {
        $this->permission('Garden.Settings.Manage');

        if (Gdn::request()->isPostBack() === false) {
            throw new Exception('Requires POST', 405);
        }

        Gdn::request()->isAuthenticatedPostBack(true);

        $this->setData(['Cleared' => Gdn::addonManager()->clearCache()]);

        $this->deliveryType(DELIVERY_TYPE_DATA);
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

        $this->setData([
            'Invalid' => $invalid,
            'New' => $new,
            'UpdateRequired' => (count($new) || count($invalid))
        ]);

        $this->deliveryType(DELIVERY_TYPE_DATA);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->render('blank', 'utility', 'dashboard');
    }
}
