<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\PartialCompletionException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Scheduler\Job\JobStatusModel;
use Vanilla\Schema\DateRangeExpression;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\SchemaUtils;

/**
 * /api/v2/job-statuses
 */
class JobStatusesApiController extends \AbstractApiController
{
    const POLL_MAX_SECONDS = 10;
    const POLL_FREQUENCY_MS = 500;

    /** @var JobStatusModel */
    private $jobStatusModel;

    /** @var \UserModel */
    private $userModel;

    /**
     * Since we are single threaded we need a way for test to execute a side effect *while* polling.
     *
     * Set a callback here and it will be called after all iterations.
     *
     * @var callable|null
     */
    public static $callAfterPollIterationsDoNotUseInProduction = null;

    /**
     * DI.
     *
     * @param JobStatusModel $jobStatusModel
     * @param \UserModel $userModel
     */
    public function __construct(JobStatusModel $jobStatusModel, \UserModel $userModel)
    {
        $this->jobStatusModel = $jobStatusModel;
        $this->userModel = $userModel;
    }

    /**
     * /api/v2/job-status
     *
     * Query job statuses by various criteria.
     *
     * @param array $query Query parameters.
     *
     * @return Data The response.
     */
    public function index(array $query = []): Data
    {
        $this->permission("Garden.SignIn.Allow");
        $in = Schema::parse([
            "jobTrackingID?" => RangeExpression::createSchema([":s"])->setField("x-filter", true),
            "jobExecutionStatus:s?" => [
                "minLength" => 1,
                "x-filter" => true,
            ],
            "trackingUserID:i" => [
                "default" => $this->getSession()->UserID,
                "min" => 0,
                "x-filter" => true,
            ],
        ])->addValidator("trackingUserID", [$this, "validateTrackingUserID"]);

        $out = Schema::parse([
            ":a" => $this->jobStatusSchema(),
        ]);

        $query = $in->validate($query);

        $where = ApiUtils::queryToFilters($in, $query);
        $rows = $this->jobStatusModel->select($where);

        $result = $out->validate($rows);

        return Data::box($result);
    }

    /**
     * POST /api/v2/job-status/poll
     *
     * This is a long polling endpoint with a maximum duration of 10 seconds.
     *
     * ## How it works
     *
     * - The client calls some other endpoint and triggers a job. The job tracking slip (with tracking ID) is returned to them.
     * - The trackingID, along with the user's ID are inserted into the jobStatus table.
     * - The client takes one or multiple tracking IDs and posts to this endpoint.
     * - This endpoint will hold the connection open with the user.
     * - The endpoint will check memcached every 500ms if there has been a change to one of the user's jobs.
     *   - Changes are either from 5 minutes before the request was made or the last time we reported changes to that user.
     *
     * If we find any changes
     * - Return immediately with the changed job status records and the number of remaining incomplete jobs.
     * If we reach the maximum duration
     * - Return with the number of remaining incomplete jobs.
     *
     * ## Recommended Usage
     *
     * - Call the endpoint until it returns. It may return with some changes or no changes.
     * - Continually make new requests to the endpoint until there are no more changes.
     *
     * ## Protection
     *
     * - The endpoint will not hold a connection open for more than 10 seconds.
     * - The endpoint will not allow holding a connection for a user that does not currently have any incomplete jobs.
     * - The endpoint relies on an active cache to work.
     *
     * @param array $body The HTTP body of the request.
     *
     * @return Data The response.
     */
    public function post_poll(array $body = []): Data
    {
        $this->permission("Garden.SignIn.Allow");
        $in = Schema::parse([
            "jobTrackingID?" => RangeExpression::createSchema([":s"])->setField("x-filter", true),
            "trackingUserID:i" => [
                "default" => $this->getSession()->UserID,
                "min" => 0,
            ],
            "maxDuration:i" => [
                "default" => self::POLL_MAX_SECONDS,
                "max" => 10,
            ],
        ])->addValidator("trackingUserID", [$this, "validateTrackingUserID"]);
        $out = Schema::parse(["incompleteJobCount:i", "updatedJobs:a" => $this->jobStatusSchema()]);

        $body = $in->validate($body);
        $userID = $body["trackingUserID"];
        $where = ApiUtils::queryToFilters($in, $body);

        if (!$this->jobStatusModel->pollingCacheIsActive()) {
            throw new ServerException(
                "A memory cache (such as memcached) must be configured to poll for job statuses."
            );
        }

        if ($this->jobStatusModel->getIncompleteCountForUser($userID, $where) === 0) {
            // Prevent people from opening up a long polling session if they have no ongoing jobs.
            throw new ForbiddenException("There were no matching job statuses to poll.");
        }

        // User has some jobs to check.

        $maxIterations = ($body["maxDuration"] / self::POLL_FREQUENCY_MS) * 1000;
        $iterations = 0;
        $lastSeenTime = $this->jobStatusModel->getLastSeenTime($userID);
        while (true) {
            if ($iterations >= $maxIterations) {
                if (self::$callAfterPollIterationsDoNotUseInProduction !== null) {
                    // No break here. We will do one more iteration then break.

                    call_user_func(self::$callAfterPollIterationsDoNotUseInProduction);
                    self::$callAfterPollIterationsDoNotUseInProduction = null;
                } else {
                    // Too many iterations. Close off the request.
                    // The client can poll again if they still want to wait.
                    break;
                }
            }

            if ($this->jobStatusModel->getCountUpdatedForUser($userID, $lastSeenTime) > 0) {
                // Our user has some job status complete.
                // Notably this check doesn't see if it was one of the ones we were specifically looking for.
                // This keeps it so the check can always work directly through cache.
                //
                // If this were to happen (a job by this user we weren't explicitly tracking changes status)
                // Then we end up returning early and the client can poll again.

                break;
            }
            $iterations++;
            usleep(self::POLL_FREQUENCY_MS * 1000);
        }

        $changed = $this->jobStatusModel->selectUpdatedForUser($userID, $lastSeenTime, $where);
        $newIncompleteCount = $this->jobStatusModel->getIncompleteCountForUser($userID, $where);

        // Help us narrow our query on future requests.
        $this->jobStatusModel->trackLastSeenTime($userID);

        $result = [
            "incompleteJobCount" => $newIncompleteCount,
            "updatedJobs" => $changed,
        ];
        $result = $out->validate($result);

        return new Data($result, ["status" => 200]);
    }

    /**
     * Validate the trackingUserID
     *
     * - The user exists.
     * - The user is either the current sessioned user or we are an admin.
     *
     * @param int $userID Potential ID to be validated.
     * @param ValidationField $field Field to adding specific error messages.
     */
    public function validateTrackingUserID(int $userID, ValidationField $field): bool
    {
        try {
            if ($userID !== $this->getSession()->UserID) {
                $this->permission("Garden.Settings.Manage");
                $user = $this->userModel->getID($userID);
                if (!$user) {
                    throw new NotFoundException("User");
                }
            }
            return true;
        } catch (\Exception $e) {
            $field->addError($e->getMessage());
            return false;
        }
    }

    /**
     * Output schema for a job status.
     *
     * @return Schema
     */
    private function jobStatusSchema(): Schema
    {
        return Schema::parse([
            "jobStatusID",
            "jobTrackingID",
            "trackingUserID",
            "dateInserted",
            "dateUpdated",
            "jobExecutionStatus",
            "errorMessage?",
            "progressTotalQuantity:i?",
            "progressCompleteQuantity:i?",
            "progressFailedQuantity:i?",
        ])->add($this->jobStatusModel->getReadSchema());
    }
}
