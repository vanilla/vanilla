<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license https://vanillaforums.com Proprietary
 */

use Garden\Schema\Schema;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Class ReactionsModule
 */
class ReactionsModule extends AbstractReactModule
{
    /** @var object|null User */
    public $user = null;

    /**
     * @var UsersApiController
     */
    private $usersApi;

    /**
     * ReactionsModule constructor.
     *
     * @param UsersApiController $usersApi
     */
    public function __construct(UsersApiController $usersApi)
    {
        $this->usersApi = $usersApi;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function assetTarget()
    {
        return "Content";
    }

    /**
     * Return reactions data.
     *
     * @return array
     */
    public function getData(): array
    {
        $data = [];
        $this->user = !$this->user ? $this->_Sender->User ?? $this->data("User", Gdn::session()->User) : $this->user;
        $isValid = ($this->user->UserID ?? 0) > 0;
        if ($isValid) {
            $userID = $this->user->UserID;
            $name = $this->user->Name;
            $userData = $this->usersApi->get($userID, ["expand" => ["reactionsReceived"]])->getData();
            $reactionsReceived = array_values($userData["reactionsReceived"] ?? []);
            foreach ($reactionsReceived as $reactionReceived) {
                $url = url("profile/reactions/$name?reaction=" . strtolower($reactionReceived["urlcode"]), true);
                $data[] = $reactionReceived + ["url" => $url];
            }
        }

        return $data;
    }

    /**
     * Get props value if we have a user.
     *
     * @return array|null
     */
    public function getProps(): ?array
    {
        $reactions = $this->getData();
        return $this->user
            ? [
                "apiParams" => ["userID" => $this->user->UserID],
                "apiData" => $reactions,
                "homeWidget" => true,
            ]
            : null;
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "ReactionListModule";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([]);
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "ReactionList";
    }
}
