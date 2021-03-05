<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\RemoteResource;

use Garden\Http\HttpResponse;
use Garden\Schema\Schema;
use Gdn_Cache;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Class LocalRemoteResourceJob
 *
 * @package Vanilla\RemoteResource
 */
class LocalRemoteResourceJob extends LocalApiJob {

    const REMOTE_RESOURCE_LOCK = 'REMOTE_RESOURCE_LOCK.%s';

    /** @var RemoteResourceModel */
    protected $remoteResourceModel;

    /** @var Gdn_Cache */
    protected $cache;

    /** @var RemoteResourceHttpClient */
    protected $remoteResourceHttpClient;

    /** @var string */
    private $url = '';

    /**
     * DI.
     *
     * @param RemoteResourceModel $remoteResourceModel
     * @param RemoteResourceHttpClient $remoteResourceHttpClient
     * @param Gdn_Cache $cache
     */
    public function __construct(
        RemoteResourceModel $remoteResourceModel,
        RemoteResourceHttpClient $remoteResourceHttpClient,
        Gdn_Cache $cache
    ) {
        $this->remoteResourceModel = $remoteResourceModel;
        $this->remoteResourceHttpClient = $remoteResourceHttpClient;
        $this->cache = $cache;
    }

    /**
     * Set job Message
     *
     * @param array $message
     */
    public function setMessage(array $message) {
        $schema = Schema::parse([
            'url:s',
        ]);

        $message = $schema->validate($message);
        $this->url = $message['url'] ?? '';
    }

    /**
     * Execute job.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $key = sprintf(self::REMOTE_RESOURCE_LOCK, $this->url);
        $locked = $this->cache->get($key);
        if (!$locked) {
            $expiry = RemoteResourceHttpClient::REQUEST_TIMEOUT * 2;
            $this->cache->add(
                $key,
                $this->url,
                [Gdn_Cache::FEATURE_EXPIRY => $expiry]
            );
            $response = $this->getRemoteResource();
            $contentBody = $response->getBody();
            $jobStatus = $this->saveContent($response, $contentBody);
            $this->cache->remove($key);
            return $jobStatus;
        } else {
            return JobExecutionStatus::progress();
        }
    }

    /**
     * Get remoteResource
     *
     * @return HttpResponse
     */
    private function getRemoteResource(): HttpResponse {
        $response = $this->remoteResourceHttpClient->get($this->url);
        return $response;
    }

    /**
     * Save the remote resources content.
     *
     * @param HttpResponse $response
     * @param string|null $contentBody
     * @return JobExecutionStatus
     */
    private function saveContent(HttpResponse $response, ?string $contentBody): JobExecutionStatus {
        if ($response->isSuccessful() && $contentBody) {
            $this->remoteResourceModel->insert(["url" => $this->url, "content" => $contentBody]);
            $jobStatus = JobExecutionStatus::complete();
        } else {
            $this->remoteResourceModel->insert(["url" => $this->url, "lastError" => $response->getStatus()]);
            $jobStatus = JobExecutionStatus::error();
        }
        return $jobStatus;
    }
}
