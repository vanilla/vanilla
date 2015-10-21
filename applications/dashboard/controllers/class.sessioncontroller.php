<?php
/**
 * Convenience access to current user's session.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /session endpoint.
 */
class SessionController extends DashboardController {

    /**
     * Stash a value in the user's session, or unstash it if no value was provided to stash.
     *
     * Looks for Name and Value POST/GET variables to pass along to Gdn_Session.
     */
    public function stash() {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);

        $name = $this->Request->post('Name', '');
        $value = $this->Request->post('Value', '');

        if ($name !== '' && $value === null) {
            $response = Gdn::session()->getPublicStash($name, true);
            $this->setJson('Unstash', $response);
        } else {
            Gdn::session()->setPublicStash($name, $value);
        }

        $this->render();
    }
}
