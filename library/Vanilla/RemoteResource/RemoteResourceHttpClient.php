<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\RemoteResource;

use Garden\Http\HttpClient;
use Garden\Http\HttpHandlerInterface;

/**
 * Class RemoteResourceHttpClient
 *
 * @package Vanilla\RemoteResource
 */
class RemoteResourceHttpClient extends HttpClient
{
    /** @var int  */
    public const REQUEST_TIMEOUT = 10;

    /**
     * RemoteResourceHttpClient constructor.
     *
     * @param string $baseUrl
     * @param HttpHandlerInterface|null $handler
     */
    public function __construct(string $baseUrl = "", HttpHandlerInterface $handler = null)
    {
        parent::__construct($baseUrl, $handler);
        $this->setDefaultOption("timeout", self::REQUEST_TIMEOUT);
    }
}
