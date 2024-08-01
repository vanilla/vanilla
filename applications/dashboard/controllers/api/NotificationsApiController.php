<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Api;

use AbstractApiController;
use ActivityModel;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;

/**
 * Manage notifications for the current user.
 */
class NotificationsApiController extends AbstractApiController
{
    /** Default limit on number of rows allowed in a page of a paginated response. */
    const PAGE_SIZE_DEFAULT = 30;

    /** Maximum allowable number of items in a page of a paginated response. */
    const PAGE_SIZE_MAXIMUM = 100;

    /** @var ActivityModel */
    private $activityModel;

    /** @var LongRunner */
    private $longRunner;

    /**
     * NotificationsApiController constructor.
     *
     * @param ActivityModel $activityModel
     */
    public function __construct(ActivityModel $activityModel, LongRunner $longRunner)
    {
        $this->activityModel = $activityModel;
        $this->longRunner = $longRunner;
    }

    /**
     * Get a single notification.
     *
     * @param int $id
     * @return array
     * @throws ValidationException If the request fails validation.
     * @throws ValidationException If the response fails validation.
     * @throws HttpException If the user has a permission-based ban in their session.
     * @throws PermissionException If the user does not have permission to access the notification.
     */
    public function get(int $id): array
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema()->setDescription("Get a single notification.");
        $out = $this->schema($this->notificationSchema(), "out");

        $row = $this->notificationByID($id);
        $this->notificationPermission($row);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an notification ID schema. Useful for documenting ID parameters in the URL path.
     *
     * @return Schema
     */
    private function idParamSchema()
    {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(
                [
                    "id" => "The notification ID.",
                ],
                "in"
            );
        }

        return $schema;
    }

    /**
     * List notifications for the current user.
     *
     * @param array $query
     * @return Data
     * @throws ValidationException If the request fails validation.
     * @throws ValidationException If the response fails validation.
     * @throws HttpException If the user has a permission-based ban in their session.
     * @throws PermissionException If the user does not have permission to access notifications.
     * @throws Exception If unable to parse pagination input.
     */
    public function index(array $query): Data
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema([
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => self::PAGE_SIZE_DEFAULT,
                "minimum" => 1,
                "maximum" => self::PAGE_SIZE_MAXIMUM,
            ],
            "page" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "shouldBatch:b?" => [
                "description" =>
                    "Notifications pertaining to a single record will be grouped and delivered as a single notification.",
                "default" => true,
            ],
        ])->setDescription("List notifications for the current user.");
        $out = $this->schema(
            [
                ":a" => $this->notificationSchema(),
            ],
            "out"
        );

        $query = $in->validate($query);
        [$offset, $limit] = offsetLimit("p" . $query["page"], $query["limit"]);

        if ($query["shouldBatch"]) {
            $rows = $this->activityModel->getWhereBatched(
                [
                    "NotifyUserID" => $this->getSession()->UserID,
                ],
                "",
                "",
                $limit,
                $offset,
                true
            );
        } else {
            $rows = $this->activityModel->getWhere(
                [
                    "NotifyUserID" => $this->getSession()->UserID,
                ],
                "",
                "",
                $limit,
                $offset
            );
        }
        $rows = $rows->resultArray();

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);

        return new Data($result, ["paging" => ApiUtils::morePagerInfo($rows, "/api/v2/notifications", $query, $in)]);
    }

    /**
     * Normalize a database record to match the API schema definition.
     *
     * @param array $row
     * @return array
     */
    private function normalizeOutput(array $row): array
    {
        $row = $this->activityModel->normalizeNotificationRow($row);
        return $row;
    }

    /**
     * Get a single notification by its activity ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException If the notification could not be found.
     */
    private function notificationByID(int $id): array
    {
        $notification = $this->activityModel
            ->getWhere([
                "ActivityID" => $id,
                "NotifyUserID >" => 0,
            ])
            ->firstRow(DATASET_TYPE_ARRAY);
        if (empty($notification)) {
            throw new NotFoundException("Notification");
        }
        return $notification;
    }

    /**
     * Verify the current user has permission to access a notification.
     *
     * @param array $notification
     * @throws ClientException If the user is attempting to access a notification that is not their own.
     */
    private function notificationPermission(array $notification)
    {
        if ($notification["NotifyUserID"] !== $this->getSession()->UserID) {
            throw new ClientException("You do not have access to the notification(s).", 403);
        }
    }

    /**
     * Get a schema representing all available notification fields.
     *
     * @return Schema
     */
    private function notificationSchema(): Schema
    {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema($this->activityModel->notificationSchema(), "NotificationSchema");
        }

        return $schema;
    }

    /**
     * Update a notification.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws ValidationException If the request fails validation.
     * @throws ValidationException If the response fails validation.
     * @throws HttpException If the user has a permission-based ban in their session.
     * @throws PermissionException If the user does not have permission to access the notification.
     */
    public function patch(int $id, array $body): array
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema();
        $in = $this->schema(
            [
                "read?" => [
                    "enum" => [true],
                ],
            ],
            "in"
        )->setDescription("Update a notification.");
        $out = $this->schema($this->notificationSchema(), "out");

        $body = $in->validate($body);

        $row = $this->activityModel->getNotificationWithCount($id);
        $this->notificationPermission($row);

        if (array_key_exists("read", $body)) {
            if (isset($row["count"]) && $row["count"] > 1) {
                $this->activityModel->updateNotificationStatusBatch($id);
            }
            $this->activityModel->updateNotificationStatusSingle($id);
        }

        $row = $this->notificationByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Read a notification.
     *
     * @param int $id
     * @param array $body
     * @return array
     * @throws ValidationException If the request fails validation.
     * @throws ValidationException If the response fails validation.
     * @throws HttpException If the user has a permission-based ban in their session.
     * @throws PermissionException If the user does not have permission to access the notification.
     */
    public function put_read(int $id, array $body): array
    {
        $this->permission("Garden.SignIn.Allow");

        $this->idParamSchema();
        $in = $this->schema([], "in")->setDescription("Update a notification.");
        $out = $this->schema($this->notificationSchema(), "out");

        $in->validate($body);

        $row = $this->notificationByID($id);
        $this->notificationPermission($row);

        if (isset($row["count"]) && $row["count"] > 1) {
            $this->activityModel->updateNotificationStatusBatch($id);
        }
        $this->activityModel->updateNotificationStatusSingle($id);

        $row = $this->notificationByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Update all notifications.
     *
     * @param array $body
     * @return Data
     * @throws ValidationException If the request fails validation.
     * @throws ValidationException If the response fails validation.
     * @throws HttpException If the user has a permission-based ban in their session.
     * @throws PermissionException If the user does not have permission to access notifications.
     */
    public function patch_index(array $body): Data
    {
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema(
            [
                "read?" => [
                    "enum" => [true],
                ],
            ],
            "in"
        )->setDescription("Update all notifications.");
        $out = $this->schema([], "out");

        $body = $in->validate($body);

        if (array_key_exists("read", $body)) {
            // If the number of pending notifications is excessive, the longrunner might not be able to clear them all.
            $response = $this->longRunner->runApi(
                new LongRunnerAction(ActivityModel::class, "markAllRead", [$this->getSession()->UserID])
            );
        } else {
            $response = null;
        }

        return new Data($response, ["status" => 204]);
    }
}
