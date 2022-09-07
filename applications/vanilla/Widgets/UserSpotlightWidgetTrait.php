<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use UserModel;
use Vanilla\Dashboard\Models\UserFragment;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Logging\ErrorLogger;

/**
 * Sharable properties and methods between the UserSpotlightWidget and the module.
 */
trait UserSpotlightWidgetTrait
{
    /** @var UserModel */
    private $userModel;

    /**
     * DI.
     *
     * @param UserModel $userModel
     */
    public function setDependencies(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Get user data.
     *
     * @param int $userID
     *
     * @return UserFragment|null
     */
    protected function getUserFragment(int $userID): ?UserFragment
    {
        try {
            $user = $this->userModel->getFragmentByID($userID, true);
            return $user;
        } catch (NoResultsException $e) {
            ErrorLogger::warning($e, ["userspotlight"]);
            return null;
        }
    }

    /**
     * Ge the schema for API params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        return Schema::parse([
            "userID" => [
                "type" => "integer",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("User", "Choose a user.", "Search..."),
                    new ApiFormChoices("/api/v2/users/by-names?name=%s*", "/api/v2/users/%s", "userID", "name")
                ),
            ],
        ]);
    }
}
