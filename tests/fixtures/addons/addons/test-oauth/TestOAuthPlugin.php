<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\TestOAuth;

use Garden\Web\Exception\ServerException;
use UserModel;

/**
 * Used for testing `Gdn_OAuth2` functionality.
 */
class TestOAuthPlugin extends \Gdn_OAuth2
{
    public const PROVIDER_KEY = "testOAuth";
    public const GOOD_UNIQUE_ID = "133";

    public const GOOD_ACCESS_TOKEN = "good";
    public const NO_USER_ACCESS_TOKEN = "no-user";
    public const NO_ID_ACCESS_TOKEN = "no-id";

    /**
     * @var UserModel
     */
    private $userModel;

    public $profiles = [
        self::GOOD_ACCESS_TOKEN => [
            "UniqueID" => self::GOOD_UNIQUE_ID,
            "Name" => "Test",
            "Email" => "test@exmple.com",
        ],
        self::NO_ID_ACCESS_TOKEN => [
            "Name" => "Foo",
            "Email" => "foo@example.com",
        ],
        self::NO_USER_ACCESS_TOKEN => [
            "UniqueID" => "dsdssf",
        ],
    ];

    /**
     * TestOAuthPlugin constructor.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel)
    {
        parent::__construct(self::PROVIDER_KEY);
        $this->settingsView = "plugins/settings/oauth2";
        $this->userModel = $userModel;
    }

    /**
     * Get a dummy user profile without doing an API call.
     *
     * @return array
     */
    public function getProfile()
    {
        if (isset($this->profiles[$this->accessToken()])) {
            return $this->profiles[$this->accessToken()];
        }
        throw new ServerException("Invalid access token.");
    }

    /**
     * Delete the dummy user.
     */
    public function cleanUp()
    {
        $user = $this->userModel->getWhere(["Email" => "test@example.com"])->firstRow(DATASET_TYPE_ARRAY);

        if ($user) {
            $this->userModel->deleteID($user["UserID"]);
        }

        // Make sure our caches aren't messing with us.
        \Gdn::cache()->flush();
    }
}
