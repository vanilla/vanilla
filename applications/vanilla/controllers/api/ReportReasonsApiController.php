<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Database\SetLiterals\RawExpression;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Models\Model;
use Vanilla\Utility\ArrayUtils;

/**
 * /api/v2/report-reasons
 */
class ReportReasonsApiController extends \AbstractApiController
{
    /**
     * DI.
     */
    public function __construct(
        private ReportReasonModel $reportReasonModel,
        private \RoleModel $roleModel,
        private \UserModel $userModel
    ) {
    }

    /**
     * GET /api/v2/report-reasons
     *
     * Get a permission filtered list of report reasons.
     *
     * @return array
     */
    public function index(array $query = []): array
    {
        $this->permission("flag.add");
        $in = Schema::parse(["includeDeleted:b?", "includeSystem:b?"]);

        $query = $in->validate($query);

        $where = [
            "deleted" => false,
            "isSystem" => false,
        ];
        if ($query["includedDeleted"] ?? false) {
            $this->permission(["community.moderate", "posts.moderate"]);
            unset($where["deleted"]);
        }

        if ($query["includeSystem"] ?? false) {
            $this->permission(["community.moderate", "posts.moderate"]);
            unset($where["isSystem"]);
        }

        $reasons = $this->reportReasonModel->select(
            where: $where,
            options: [
                Model::OPT_ORDER => "sort",
            ]
        );

        if (!$this->getSession()->checkPermission("community.moderate")) {
            // Filter our reasons to only ones without role requirements, or ones that the user has access to.
            $userRoleIDs = $this->userModel->getRoleIDs($this->getSession()->UserID) ?: [];

            $reasons = array_filter($reasons, function ($reason) use ($userRoleIDs) {
                if (empty($reason["roleIDs"])) {
                    // this role has no permission filters.
                    return true;
                }

                $intersectingRoleIDs = array_intersect($reason["roleIDs"], $userRoleIDs);
                if (count($intersectingRoleIDs) > 0) {
                    // User has at least one of the required roles.
                    return true;
                }

                // User cannot acccess this reason.
                return false;
            });
            $reasons = array_values($reasons);
        }

        // We're going to expand out the roles
        $allRoleIDs = [];
        foreach ($reasons as $reason) {
            if (!empty($reason["roleIDs"])) {
                $allRoleIDs = array_merge($allRoleIDs, $reason["roleIDs"]);
            }
        }
        $roles = $this->roleModel->getWhere(where: ["roleID" => $allRoleIDs])->resultArray();
        $rolesByID = array_column($roles, null, "RoleID");
        foreach ($reasons as &$reason) {
            if (!empty($reason["roleIDs"])) {
                $reason["roles"] = array_map(function ($roleID) use ($rolesByID) {
                    $foundRole = $rolesByID[$roleID];

                    return [
                        "name" => $foundRole["Name"],
                        "roleID" => $foundRole["RoleID"],
                    ];
                }, $reason["roleIDs"]);
            } else {
                $reason["roles"] = null;
            }
        }

        if ($this->getSession()->checkPermission("community.moderate")) {
            // We're going to expand out the counts (only for admins)
            $this->reportReasonModel->joinReasonCounts($reasons);
        }

        return $reasons;
    }

    /**
     * GET /api/v2/report-reasons/:reportReasonID
     *
     * @param string $id The reportReasonID
     *
     * @return array
     */
    public function get(string $id, array $query = []): array
    {
        $this->permission("flag.add");
        $in = Schema::parse(["includeDeleted:b?"]);

        $query = $in->validate($query);

        $reason = $this->selectReason(reportReasonID: $id, includeDeleted: $query["includeDeleted"] ?? false);
        if (!empty($reason["roleIDs"]) && !$this->getSession()->checkPermission("community.moderate")) {
            // We have to make sure the user can access the reason.

            $userRoleIDs = $this->userModel->getRoleIDs($this->getSession()->UserID) ?: [];
            $intersectingRoleIDs = array_intersect($reason["roleIDs"], $userRoleIDs);
            if (count($intersectingRoleIDs) === 0) {
                // No sense in exposing that this reason exists since the IDs expose a lot of information about them.
                throw new NotFoundException("reportReason", ["reportReasonID" => $id]);
            }
        }

        if ($this->getSession()->checkPermission("community.moderate")) {
            $this->reportReasonModel->joinReasonCounts($reason);
        }

        return $reason;
    }

    /**
     * GET /api/v2/report-reasons/:reportReasonID/edit
     *
     * @param string $id The reportReasonID
     *
     * @return array
     */
    public function get_edit(string $id): array
    {
        $this->permission("community.moderate");
        $reason = $this->selectReason(reportReasonID: $id);

        $result = ArrayUtils::pluck($reason, ["reportReasonID", "name", "description", "roleIDs", "sort"]);

        return $result;
    }

    /**
     * PATCH /api/v2/report-reasons/:reportReasonID
     *
     * @param string $id
     * @param array $body
     *
     * @return array
     */
    public function patch(string $id, array $body): array
    {
        $this->permission("community.moderate");
        $in = Schema::parse([
            "name:s?",
            "description:s?",
            "roleIDs?" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
            ],
            "sort:i?",
        ]);

        $existingReason = $this->selectReason(reportReasonID: $id);

        $body = $in->validate($body);

        $this->reportReasonModel->update(set: $body, where: ["reportReasonID" => $id]);

        $updatedReason = $this->selectReason(reportReasonID: $id);
        return $updatedReason;
    }

    /**
     * POST /api/v2/report-reasons
     *
     * @param array $body
     *
     * @return Data
     */
    public function post(array $body): Data
    {
        $this->permission("community.moderate");
        $in = Schema::parse([
            "reportReasonID:s",
            "name:s",
            "description:s",
            "roleIDs?" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
            ],
        ]);

        if (empty($body["roleIDs"])) {
            $body["roleIDs"] = null;
        } else {
            $in->addValidator("roleIDs", [$this->roleModel, "roleIDsValidator"]);
        }

        $body = $in->validate($body);

        // Check if the reason already exists.
        try {
            $existingReason = $this->selectReason($body["reportReasonID"], includeDeleted: true);
            // We found an existing reason. This is a conflict.
            return new Data(
                [
                    "message" => "A reason with this API name already exists.",
                    "code" => 409,
                    "conflictingReason" => $existingReason,
                ],
                409
            );
        } catch (NotFoundException $e) {
            // It doesn't exist, so we can create it.
            // Nothing to handle here.
        }

        $this->reportReasonModel->insert($body);

        $reportReasonID = $body["reportReasonID"];
        $reason = $this->selectReason(reportReasonID: $reportReasonID);
        return new Data($reason);
    }

    /**
     * Delete a report reason.
     *
     * @param string $reportReasonID
     *
     * @return void
     */
    public function delete(string $reportReasonID): void
    {
        $this->permission("community.moderate");

        // Make sure the reason exists.
        $reason = $this->selectReason($reportReasonID);
        $this->reportReasonModel->joinReasonCounts($reason);

        if (!$reason["isDeletable"] || $reason["countReports"] > 0) {
            // Soft delete it.
            $this->reportReasonModel->update(set: ["deleted" => true], where: ["reportReasonID" => $reportReasonID]);
        } else {
            // Actually delete it
            $this->reportReasonModel->delete(["reportReasonID" => $reportReasonID]);
        }
    }

    /**
     * Update sorts.
     *
     * @param array<string, int> $body
     */
    public function put_sorts(array $body): void
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(Schema::parse([":o" => "Key-value mapping of apiName => sort"]));
        $body = $in->validate($body);
        $this->reportReasonModel->updateSorts($body);
    }

    ///
    /// Private utilities
    ///

    /**
     * Get a reason by ID or a throw a {@link NotFoundException}
     *
     * @param string $reportReasonID
     * @param bool $includeDeleted Requires community.manage permission.
     *
     * @return array
     *
     * @throws NotFoundException
     */
    private function selectReason(string $reportReasonID, bool $includeDeleted = false): array
    {
        $where = [
            "reportReasonID" => $reportReasonID,
            "deleted" => false,
        ];
        if ($includeDeleted) {
            $this->permission("community.moderate");
            $where["deleted"] = [true, false];
        }

        try {
            $reportReason = $this->reportReasonModel->selectSingle($where);
        } catch (NoResultsException $e) {
            throw new NotFoundException("reportReason", ["reportReasonID" => $reportReasonID], $e);
        }

        // Expand out the roles.

        return $reportReason;
    }
}
