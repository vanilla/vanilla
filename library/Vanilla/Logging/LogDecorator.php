<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * A decorator for the log that adds default context attributes based on the current request.
 */
class LogDecorator implements LoggerInterface {
    use LoggerTrait;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * @var \Gdn_Request
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LogDecorator constructor.
     *
     * @param \Gdn_Session $session
     * @param \Gdn_Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(\Gdn_Session $session, \Gdn_Request $request, LoggerInterface $logger) {
        $this->session = $session;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array()) {
        $defaults = [
            'userid' => $this->session->UserID,
            'username' => $this->session->User->Name ?? 'anonymous',
            'ip' => $this->request->ipAddress(),
            'timestamp' => time(),
            'method' => $this->request->requestMethod(),
            'domain' => rtrim($this->request->url('/', true), '/'),
            'path' => $this->request->path(),
        ];

        $this->logger->log($level, $message, $context + $defaults);
    }
}
