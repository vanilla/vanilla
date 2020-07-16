<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Garden\Schema\Schema;
use Garden\Web\Exception\HttpException;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use Vanilla\Web\Pagination\ApiPaginationIterator;

/**
 * A local job for handling bulk deletes.
 */
class LocalApiBulkDeleteJob extends LocalApiJob {

    /** @var string */
    private $iteratorUrl;

    /** @var string */
    private $recordIDField;

    /** @var string */
    private $deleteUrlPattern;

    /** @var string|null */
    private $finalDeleteUrl;

    /** @var array|null */
    private $notify;

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $this->vanillaClient->setDefaultHeader(LogTransactionMiddleware::HEADER_NAME, \LogModel::generateTransactionID());
        $iterator = new ApiPaginationIterator($this->vanillaClient, $this->iteratorUrl);
        foreach ($iterator as $apiResults) {
            // Loop through each one and delete it.
            foreach ($apiResults as $apiResult) {
                $id = $apiResult[$this->recordIDField];
                $deleteUrl = str_replace(":recordID", $id, $this->deleteUrlPattern);
                $this->vanillaClient->delete($deleteUrl);
            }
        }

        if ($this->finalDeleteUrl) {
            $this->vanillaClient->delete($this->finalDeleteUrl);
        }

        return JobExecutionStatus::complete();
    }

    /**
     * @inheritdoc
     */
    public function setMessage(array $message) {
        $schema = Schema::parse([
            'iteratorUrl:s',
            'recordIDField:s',
            'deleteUrlPattern:s', // Pattern "/some/path/:recordID/asdfasdf
            'finalDeleteUrl:s?',
            'notify:o?' => [
                'userID:i',
                'title:s',
                'body:s',
            ],
        ]);

        $message = $schema->validate($message);
        $this->iteratorUrl = $message['iteratorUrl'];
        $this->recordIDField = $message['recordIDField'];
        $this->deleteUrlPattern = $message['deleteUrlPattern'];

        if (strpos($this->deleteUrlPattern, ":recordID") === false) {
            throw new \Exception("Unable to queue a bulk delete job with an invalid deleteUrlPattern. It must contain a `:recordID` placeholder");
        }

        $this->finalDeleteUrl = $message['finalDeleteUrl'] ?? null;
        $this->notify = $message['notify'] ?? null;
    }
}
