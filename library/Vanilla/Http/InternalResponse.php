<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Http;

use Garden\Http\HttpResponse;
use Garden\Web\Data;

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
        parent::__construct(
            $data->getStatus(),
            array_merge($data->getHeaders(), ["X-Data-Meta" => json_encode($data->getMetaArray())])
        );
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
            $processNode = function ($node) use (&$processNode) {
                if (is_array($node)) {
                    // Do all the children.
                    foreach ($node as $key => $value) {
                        $node[$key] = $processNode($value);
                    }
                    return $node;
                }

                if (is_object($node)) {
                    if ($node instanceof \JsonSerializable) {
                        $node = $node->jsonSerialize();
                        $node = $processNode($node);
                        return $node;
                    } else {
                        $node = (array) $node;
                        $node = $processNode($node);
                        return $node;
                    }
                }

                return $node;
            };
            // Done lazily to avoid json encoding/decoding if we don't have to.
            $result = $processNode($this->data->jsonSerialize());
            $this->setBody($result);
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
        return $this->throwable;
    }

    /**
     * @param \Throwable|null $exception
     */
    public function setThrowable(?\Throwable $exception): void
    {
        $this->throwable = $exception;
    }
}
