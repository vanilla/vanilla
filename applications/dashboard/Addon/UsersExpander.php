<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

/**
 * Expander of user fragments.
 */
class UsersExpander {

    /** @var \UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     */
    public function __construct(\UserModel $userModel) {
        $this->userModel = $userModel;
    }


    /**
     * Fetch user fragments for userIDs.
     *
     * @param array $userIDs
     *
     * @return array
     */
    public function __invoke(array $userIDs): array {
        return $this->userModel->getUserFragments($userIDs);
    }
}
