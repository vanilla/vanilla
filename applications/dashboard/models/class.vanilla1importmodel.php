<?php
/**
 * Vanilla1 import model.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Object for doing specific actions to a Vanilla 1 import.
 */
class Vanilla1ImportModel extends Gdn_Model {

    /** @var ImportModel */
    var $ImportModel = null;

    /**
     * Custom finalization.
     */
    public function afterImport() {
        // Set up the routes to redirect from their older counterparts.
        $Router = Gdn::router();
        $Router->SetRoute('\?CategoryID=(\d+)(?:&page=(\d+))?', 'categories/$1/p$2', 'Permanent');
        $Router->SetRoute('\?page=(\d+)', 'discussions/p$1', 'Permanent');
        $Router->SetRoute('comments\.php\?DiscussionID=(\d+)', 'discussion/$1/x', 'Permanent');
        $Router->SetRoute('comments\.php\?DiscussionID=(\d+)&page=(\d+)', 'discussion/$1/x/p$2', 'Permanent');
        $Router->SetRoute('account\.php\?u=(\d+)', 'dashboard/profile/$1/x', 'Permanent');
    }
}
