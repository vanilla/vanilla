<?php
/**
 * @author Sooraj Francis <sfrancis@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\AutomationRules\Triggers;

use Exception;
use Garden\Container\ContainerException;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Http\InternalClient;
use Vanilla\Utility\ArrayUtils;

trait UserSearchTrait
{
    private int $limit = 30;

    // Currently elastic search only support max limit to 30
    private int $maxLimit = 30;
    private string $recordType = "user";
    private string $userUrl = "/users";
    private string $searchUrl = "/search";
    private string $recordID = "userID";
    private InternalClient $internalClient;

    /**
     * Get the internal client
     *
     * @return InternalClient
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function api(): InternalClient
    {
        if (!isset($this->internalClient)) {
            $this->internalClient = \Gdn::getContainer()->get(InternalClient::class);
        }
        return $this->internalClient;
    }

    /**
     * Query users endpoint to get the results
     *
     * @param array $params
     * @return Data
     * @throws Exception
     */
    private function queryUsers(array $params): Data
    {
        $params["limit"] = $params["limit"] ?? $this->limit;
        if ($params["limit"] > $this->maxLimit) {
            throw new Exception("The maximum limit allowed is {$this->maxLimit}");
        }
        $params["sort"] = $params["sort"] ?? $this->recordID;
        $response = $this->api()->get($this->userUrl, $params);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to get results from user search");
        }
        return $response->asData();
    }

    /**
     * Get the count based on the query
     *
     * @param array $query
     * @return int
     * @throws \Exception
     */
    public function getCount(array $query): int
    {
        $validKeys = ["emailDomain", "profileFields", "emailConfirmed"];
        $params = [
            "limit" => 1,
            "sort" => "userID",
        ];
        foreach ($validKeys as $key) {
            if (isset($query[$key])) {
                $params[$key] = $query[$key];
            }
        }
        $data = $this->queryUsers($params);
        return $data->getMeta("paging.totalCount", 0);
    }

    /**
     * Search for users on conditions defined by the query
     *
     * @param array $query
     * @return array
     * @throws Exception
     */
    private function getUserSearch(array $query): array
    {
        $params = ArrayUtils::pluck($query, [
            "emailDomain",
            "emailConfirmed",
            "profileFields",
            "page",
            "limit",
            "sort",
            "userID",
        ]);
        $params["limit"] = $params["limit"] ?? $this->limit;
        $params["page"] = $params["page"] ?? 1;
        $params["recordTypes"] = $this->recordType;
        if ($params["limit"] > $this->maxLimit) {
            throw new Exception("The maximum limit allowed is {$this->maxLimit}");
        }
        $params["sort"] = $params["sort"] ?? $this->recordID;
        $response = $this->api()->get($this->searchUrl, $params);
        $result = [];
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to get results from user search");
        }
        $result["records"] = $response->getBody();
        $data = $response->asData();
        $result["next"] = $data->getMeta("HTTP_X_APP_PAGE_NEXT_URL");
        return $result;
    }

    /**
     * Iterator to get user records based on condition in batches
     *
     * @param array $query
     * @param int $tries
     * @return \Generator
     * @throws Exception
     */
    public function getUserRecordIterator(array $query, int $tries = 0): \Generator
    {
        try {
            $page = 1;
            $userID = null;
            while (true) {
                if (isset($query["userID"]) && empty($userID)) {
                    $userID = $query["userID"];
                    $query["userID"] = ">" . $query["userID"];
                }
                $query["sort"] = $query["sort"] ?? "userID";
                $query["page"] = $page;
                $result = $this->getUserSearch($query);
                $data = $result["records"];
                foreach ($data as $record) {
                    yield $record["recordID"] => $record;
                }
                $page++;
                if (empty($data) || empty($result["next"])) {
                    return;
                }
            }
        } catch (Exception $e) {
            // Retry 3 times before throwing the exception
            if ($tries > 3) {
                throw $e;
            }
            $tries++;
            if ($userID) {
                $query["userID"] = $userID;
            }
            return $this->getUserRecordIterator($query, $tries);
        }
    }
}
