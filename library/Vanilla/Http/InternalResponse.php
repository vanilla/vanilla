<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Http;

use Garden\Http\HttpResponse;
use Garden\Web\Data;
use Vanilla\Utility\ArrayUtils;

/**
 * Response type for internal requests.
 */
class InternalResponse extends HttpResponse
{
    private Data $data;

    /** @var \Throwable|null */
    private $throwable;

    /**
     * @param Data $data
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
        $data->applyMetaHeaders();
        parent::__construct($data->getStatus(), []);
        $headers = array_merge($data->getHeaders(), ["X-Data-Meta" => json_encode($data->getMetaArray())]);
        foreach ($headers as $key => $value) {
            $this->addHeader($key, is_array($value) ? $value[0] : $value);
        }
        if ($ex = $data->getMeta("exception")) {
            $this->setThrowable($ex);
        }
    }

    /**
     * @return Data
     */
    public function asData(): Data
    {
        return $this->data;
    }

    private function prepareBody()
    {
        if (empty($this->body)) {
            $this->setBody(json_decode(json_encode($this->data), true));
        }
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        $this->prepareBody();
        return parent::getBody();
    }

    /**
     * @inheritDoc
     */
    public function getRawBody(): string
    {
        $this->prepareBody();
        return parent::getRawBody();
    }

    /**
     * @return \Throwable|null
     */
    public function getThrowable(): ?\Throwable
    {
        if (!ArrayUtils::isArray($this->data->getData())) {
            return null;
        }
        $progressExceptions = $this->data["progress"]["exceptionsByID"] ?? [];
        if (is_array($progressExceptions)) {
            $progressException = reset($progressExceptions) ?: null;
        } else {
            $progressException = null;
        }

        return $this->throwable ?? $progressException;
    }

    /**
     * @param \Throwable|null $exception
     */
    public function setThrowable(?\Throwable $exception): void
    {
        $this->throwable = $exception;
    }
}
