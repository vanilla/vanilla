<?php
/**
 * Manages the social plugins.
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 * @deprecated See AddonsController.
 */

/**
 * Handles /social endpoint, so it must be an extrovert.
 */
class SocialController extends DashboardController {
    /**
     * Redirect our only deprecated endpoint.
     */
    public function manage() {
        redirect('addons/socialconnect');
    }
}
