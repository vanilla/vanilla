<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Dashboard\Models\RoleRequestModel;

/**
 * Display a list of role application links.
 *
 * This module displays a list of links to role applications for roles the user doesn't have access to.
 */
class RoleApplicationLinksModule extends Gdn_Module {
    /**
     * @inheritDoc
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    /**
     * {@inheritDoc}
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * @return string
     */
    public function toString() {
        if (!Gdn::session()->isValid()) {
            return '';
        }
        $this->loadData();

        return parent::toString();
    }

    /**
     * Load the role request metas for roles the user doesn't have.
     */
    private function loadData() {
        /** @var \Vanilla\Dashboard\Models\RoleRequestsApiController $api */
        $api = Gdn::getContainer()->get(\Vanilla\Dashboard\Models\RoleRequestsApiController::class);
        $metas = $api
            ->index_metas([
                'type' => RoleRequestModel::TYPE_APPLICATION,
                'hasRole' => false,
                'expand' => true
            ])
            ->getData();
        $this->setData('metas', $metas);
    }
}
