<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Models;

use \UserModel;

/**
 * Class SSOModel
 */
class SSOModel {

    /** @var UserModel */
    private $userModel;

    /**
     * SSOModel constructor.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel) {
        $this->userModel = $userModel;
    }

    /**
     * Authenticate a user using a SSOUserInfo object.
     *
     * @throws Exception
     * @param SSOUserInfo $ssoUserInfo
     * @return array The user's data on success or false on failure.
     */
    public function sso(SSOUserInfo $ssoUserInfo) {
        $userData = $this->userModel->getAuthentication($ssoUserInfo['uniqueID'], $ssoUserInfo['authenticatorID']);

        if (empty($userData)) {
            $userData = false;
        }

        return $userData;
    }
}
