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
class LocalRemoteResourceJob extends LocalApiJob
{
    const REMOTE_RESOURCE_LOCK = "REMOTE_RESOURCE_LOCK.%s";

    /** @var RemoteResourceModel */
    protected $remoteResourceModel;

    /** @var Gdn_Cache */
    protected $cache;

    /** @var RemoteResourceHttpClient */
    protected $remoteResourceHttpClient;

    /** @var string */
    private $url = "";

    /** @var array */
    private $headers = [];

    /** @var null */
    private $callable = null;

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
    public function setMessage(array $message)
    {
        $schema = Schema::parse(["url:s", "headers:o?", "callable?" => ["nullable" => true]]);

        $message = $schema->validate($message);
        $this->url = $message["url"] ?? "";
        $this->headers = $message["headers"] ?? [];
        if (!empty($message["callable"]) && is_callable($message["callable"], false, $callable_name)) {
            $this->callable = $message["callable"];
        }
    }

    /**
     * Execute job.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        $key = sprintf(self::REMOTE_RESOURCE_LOCK, $this->url);
        $expiry = RemoteResourceHttpClient::REQUEST_TIMEOUT * 2;
        // Acquire an atomic lock.
        if ($this->cache->add($key, $this->url, [Gdn_Cache::FEATURE_EXPIRY => $expiry])) {
            $response = $this->getRemoteResource();
            $contentBody = $response->getBody();
            if (!is_string($contentBody)) {
                $contentBody = "";
            }
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
    private function getRemoteResource(): HttpResponse
    {
        $response = $this->remoteResourceHttpClient->get($this->url, [], $this->headers);
        return $response;
    }

    /**
     * Save the remote resources content.
     *
     * @param HttpResponse $response
     * @param string|null $contentBody
     * @return JobExecutionStatus
     */
    private function saveContent(HttpResponse $response, ?string $contentBody): JobExecutionStatus
    {
        $set = ["url" => RemoteResourceModel::PREFIX . $this->url, "content" => null, "lastError" => null];
        $contentType = $response->getHeader("Content-Type");

        //If Accept headers are set store and process content if the response content type matches one of the accept headers
        if (!empty($this->headers["Accept"]) && !empty($contentType)) {
            $validFormat = false;
            foreach ($this->headers["Accept"] as $acceptedContentTypes) {
                if (stripos($contentType, $acceptedContentTypes) > -1) {
                    $validFormat = true;
                    break;
                }
            }
            if (!$validFormat) {
                $set["lastError"] = "Invalid Content Format";
                $contentBody = "";
            }
        }

        if ($response->isSuccessful()) {
            //if a callable is passed then call the callable to process the content
            if (is_callable($this->callable, false, $callable_name)) {
                $contentBody = call_user_func($this->callable, $contentBody);
            }
            $set["content"] = $contentBody;
            $jobStatus = JobExecutionStatus::complete();
        } else {
            $set["lastError"] = $response->getStatus();
            $jobStatus = JobExecutionStatus::error();
        }
        $this->remoteResourceModel->insert($set);
        return $jobStatus;
    }
}
