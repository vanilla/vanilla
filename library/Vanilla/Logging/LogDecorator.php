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
     * @var array
     */
    private $staticContextDefaults = [];

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
        $defaults = $this->staticContextDefaults + [
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

    /**
     * Add log context defaults.
     *
     * @param array $defaults
     */
    public function addStaticContextDefaults(array $defaults) {
        $this->staticContextDefaults = array_replace($this->staticContextDefaults, $defaults);
    }

    /**
     * Get the context defaults that will be added to every log entry.
     *
     * @return array
     */
    public function getStaticContextDefaults(): array {
        return $this->staticContextDefaults;
    }

    /**
     * Set the context defaults that will be added to every log entry.
     *
     * @param array $staticContextDefaults
     */
    public function setStaticContextDefaults(array $staticContextDefaults): void {
        $this->staticContextDefaults = $staticContextDefaults;
    }
}
