<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Scheduler\Job;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Schema\Schema;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Perform an HTTP request.
 */
class HttpRequestJob implements LocalJobInterface {

    /** @var string|null */
    private $body;

    /** @var HttpClient */
    private $client;

    /** @var int */
    private $delay;

    /** @var string */
    private $feedbackJob;

    /** @var array */
    private $feedbackMessage = [];

    /** @var array */
    private $headers = [];

    /** @var string */
    private $method;

    /** @var JobPriority */
    private $priority;

    /** @var SchedulerInterface */
    private $scheduler;

    /** @var string */
    private $uri;

    /**
     * Setup the job.
     *
     * @param HttpClient $client
     * @param SchedulerInterface $scheduler
     */
    public function __construct(HttpClient $client, SchedulerInterface $scheduler) {
        $this->client = $client;
        $this->scheduler = $scheduler;
    }

    /**
     * Get a schema for validating the job configuration message.
     *
     * @return Schema
     */
    private function messageSchema(): Schema {
        $schema = Schema::parse([
            "body" => [
                "allowNull" => true,
                "default" => null,
                "type" => "string",
            ],
            "feedbackJob?" => [
                "default" => null,
                "type" => "string",
            ],
            "feedbackMessage?",
            "headers" => [
                "additionalProperties" => [
                    "type" => "string",
                ],
            ],
            "method" => [
                "enum" => [
                    HttpRequest::METHOD_DELETE,
                    HttpRequest::METHOD_GET,
                    HttpRequest::METHOD_HEAD,
                    HttpRequest::METHOD_OPTIONS,
                    HttpRequest::METHOD_PATCH,
                    HttpRequest::METHOD_POST,
                    HttpRequest::METHOD_PUT,
                ],
                "type" => "string",
            ],
            "uri" => [
                "type" => "string",
            ],
        ]);
        return $schema;
    }

    /**
     * {@inheritDoc}
     */
    public function run(): JobExecutionStatus {
        $startTime = microtime(true);
        $response = $this->client->request(
            $this->method,
            $this->uri,
            $this->body ?? "",
            $this->headers ?? []
        );
        $endTime = microtime(true);
        $duration = intval(($endTime - $startTime) * 1000);

        $this->scheduleFeedback($response, $duration);

        if ($response->isResponseClass("2xx")) {
            return JobExecutionStatus::complete();
        } else {
            return JobExecutionStatus::error();
        }
    }

    /**
     * Schedule the feedback job, if configured.
     *
     * @param HttpResponse $response
     * @param integer $duration
     * @return void
     */
    private function scheduleFeedback(HttpResponse $response, int $duration): void {
        if (!is_string($this->feedbackJob)) {
            return;
        }

        $request = $response->getRequest();
        $defaultMessage = [
            "requestHeaders" => $this->stringifyHeaders($request->getHeaders()),
            "requestBody" => $request->getBody(),
            "requestDuration" => $duration,
            "responseHeaders" => $this->stringifyHeaders($response->getHeaders()),
            "responseBody" => $response->getRawBody(),
            "responseCode" => $response->getStatusCode(),
        ];
        $message = $defaultMessage + $this->feedbackMessage;
        $this->scheduler->addJob(
            $this->feedbackJob,
            $message
        );
    }

    /**
     * Set the request body.
     *
     * @param string $body
     */
    private function setBody(?string $body): void {
        $this->body = $body;
    }

    /**
     * {@inheritDoc}
     */
    public function setDelay(int $seconds) {
        $this->delay = $seconds;
    }

    /**
     * Configure the class of a job to be run after a request has been executed.
     *
     * @param string|null $job
     * @return void
     */
    private function setFeedbackJob(?string $job): void {
        $this->feedbackJob = $job;
    }

    /**
     * Configure additional parameters to be sent to the feedback job.
     *
     * @param array $message
     * @return void
     */
    private function setFeedbackMessage(array $message): void {
        $this->feedbackMessage = $message;
    }

    /**
     * Set request headers.
     *
     * @param array|null $headers
     * @return void
     */
    private function setHeaders(?array $headers): void {
        $this->headers = $headers;
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage(array $message) {
        $message += [
            "headers" => [],
            "feedbackMessage" => [],
        ];
        $message = $this->messageSchema()->validate($message);

        $this->setBody($message["body"]);
        $this->setFeedbackJob($message["feedbackJob"]);
        $this->setFeedbackMessage($message["feedbackMessage"]);
        $this->setHeaders($message["headers"]);
        $this->setMethod($message["method"]);
        $this->setUri($message["uri"]);
    }

    /**
     * Set the request method.
     *
     * @param string $method
     * @return void
     */
    private function setMethod(string $method): void {
        $this->method = $method;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(JobPriority $priority) {
        $this->priority = $priority;
    }

    /**
     * Set the request URI.
     *
     * @param string $uri
     * @return void
     */
    private function setUri(string $uri): void {
        $this->uri = $uri;
    }

    /**
     * Given an associative array of headers, return the string version.
     *
     * @param array $headers
     * @return string
     */
    private function stringifyHeaders(array $headers): string {
        $headerStrings = [];
        foreach ($headers as $directive => $values) {
            if (is_array($values)) {
                foreach ($values as $val) {
                    $headerStrings[] = "{$directive}: {$val}";
                }
            }
        }
        $result = implode("\n", $headerStrings);
        return $result;
    }
}
