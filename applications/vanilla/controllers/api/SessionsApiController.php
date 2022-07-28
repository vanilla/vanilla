<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Data;

/**
 * API Controller for the `/sessions` resource.
 */
class SessionsApiController extends AbstractApiController
{
    /** @var SessionModel */
    private $sessionModel;

    /**
     * SessionsApiController's constructor.
     *
     * @param SessionModel $sessionModel
     */
    public function __construct(SessionModel $sessionModel)
    {
        $this->sessionModel = $sessionModel;
    }

    /**
     * Get a schema instance comprised of all available session fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function outputSchema(): Schema
    {
        $schema = Schema::parse([
            "sessionID:s" => "The session's ID.",
            "userID:i" => "The user's ID.",
            "dateInserted:dt" => "Date inserted.",
            "dateUpdated:dt|n" => "Date updated.",
            "dateExpires:dt|n" => "Expiry date.",
        ]);
        return $schema;
    }

    /**
     * Handles the GET /sessions APIv2 endpoint.
     *
     * @param array $query
     * @return array
     * @throws \Garden\Schema\ValidationException If it fails filter validations.
     */
    public function index(array $query): array
    {
        $in = Schema::parse([
            "filter:s?" => [
                "enum" => ["valid", "invalid"],
            ],
            "userID:i",
        ]);
        if (\Gdn::session()->UserID != $query["userID"]) {
            $this->permission("Garden.Moderation.Manage");
        }
        $query = $in->validate($query);

        $sessions = $this->sessionModel->getSessions($query["userID"], $query["filter"] ?? "");

        $out = $this->schema([":a" => $this->outputSchema()], "out")->setDescription("Get sessions.");
        $sessions = $out->validate($sessions);

        return $sessions;
    }

    /**
     * Handles the DELETE /sessions APIv2 endpoint.
     *
     * @param array $args Request parameters.
     * @return Data
     */
    public function delete(array $args): Data
    {
        $in = Schema::parse(["userID:i", "sessionID:s?"]);
        $args = $in->validate($args);

        $userID = $args["userID"];
        $sessionID = $args["sessionID"] ?? null;

        if (\Gdn::session()->UserID != $userID) {
            $this->permission("Garden.Moderation.Manage");
        }

        $statusCode = 204;
        $sessionExists = $this->sessionModel->sessionExists($userID, $sessionID);

        if ($sessionExists) {
            $this->sessionModel->expireUserSessions($userID, $sessionID);
            $sessionStillExists = $this->sessionModel->sessionExists($userID, $sessionID);

            if ($sessionStillExists) {
                $statusCode = 500;
            }
        } else {
            $statusCode = 404;
        }
        return new Data([], ["status" => $statusCode]);
    }
}
