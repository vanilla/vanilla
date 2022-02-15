<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use UserModel;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;

/**
 * Sharable properties and methods between the UserSpotlightWidget and the module.
 */
trait UserSpotlightWidgetTrait {

    /** @var UserModel */
    private $userModel;

    /** @var int */
    private $userID;

    /**
     * DI.
     *
     * @param UserModel $userModel
     */
    public function setDependencies(UserModel $userModel) {
        $this->userModel = $userModel;
    }

    /**
     * Get user data.
     *
     * @return array|null
     */
    protected function getData(): ?array {
        $userID = $this->getUserID();
        if (!$userID) {
            return null;
        }

        $user = $this->userModel->getFragmentByID($userID);
        return $user;
    }

    /**
     * Ge the schema for API params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema {
        return Schema::parse([
            'userID?' => [
                'type' => 'integer',
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'User',
                        'Choose a user.',
                        'Search...'
                    ),
                    new ApiFormChoices(
                        '/api/v2/users/by-names?name=%s*',
                        '/api/v2/users/%s',
                        'userID',
                        'name'
                    )
                )
            ]
        ]);
    }

    /**
     * @return int
     */
    public function getUserID(): int {
        return $this->userID;
    }

    /**
     * @param int $userID
     */
    public function setUserID(int $userID): void {
        $this->userID = $userID;
    }
}
