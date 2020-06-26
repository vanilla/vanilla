<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Ramsey\Uuid\Uuid;
use Vanilla\Logger;

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
     * @var \UserModel
     */
    private $userModel;

    /**
     * @var array
     */
    private $obscureKeys = [
        'access_token',
        'authorization',
        '*password',
        '*secret',
    ];

    /**
     * LogDecorator constructor.
     *
     * @param LoggerInterface $logger
     * @param \Gdn_Request $request
     * @param \Gdn_Session $session
     * @param \UserModel $userModel
     */
    public function __construct(LoggerInterface $logger, \Gdn_Request $request, \Gdn_Session $session, \UserModel $userModel) {
        $this->session = $session;
        $this->request = $request;
        $this->logger = $logger;
        $this->userModel = $userModel;

        if (empty($request->getAttribute('requestID'))) {
            try {
                $request->setAttribute('requestID', Uuid::uuid1()->toString());
            } catch (\Exception $ex) {
                trigger_error("LogDecorator::__construct(): ".$ex->getMessage(), E_USER_WARNING);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array()) {
        $context += $this->staticContextDefaults + [
            Logger::FIELD_USERID => $this->session->UserID,
            'ip' => $this->request->getIP(),
            'timestamp' => time(),
            'method' => $this->request->requestMethod(),
            'domain' => rtrim($this->request->url('/', true), '/'),
            'path' => $this->request->getPath(),
            'requestID' => $this->request->getAttribute('requestID', null),
        ];

        $this->addUsername(Logger::FIELD_USERID, Logger::FIELD_USERNAME, $context);
        $this->addUsername(Logger::FIELD_TARGET_USERID, Logger::FIELD_TARGET_USERNAME, $context);

        $this->obscureContext($context);

        $this->logger->log($level, $message, $context);
    }

    /**
     * Add a username to a log entry.
     *
     * @param string $idField
     * @param string $nameField
     * @param array $context
     */
    private function addUsername(string $idField, string $nameField, array &$context): void {
        if (!array_key_exists($idField, $context) || array_key_exists($nameField, $context)) {
            return;
        }

        if (empty($context[$idField])) {
            $context[$nameField] = 'anonymous';
        } else {
            $user = $this->userModel->getID($context[$idField], DATASET_TYPE_OBJECT);

            if ($user === false) {
                $context[$nameField] = 'unknown';
            } else {
                $context[$nameField] = $user->Name;
            }
        }
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
     * Add a pattern to remove.
     *
     * @param string $pattern
     */
    public function addObscureKey(string $pattern): void {
        $this->obscureKeys[] = strtolower($pattern);
    }

    /**
     * Get the context defaults that will be added to every log entry.
     *
     * @return array
     */
    public function getContextOverrides(): array {
        return $this->staticContextDefaults;
    }

    /**
     * Set the context defaults that will be added to every log entry.
     *
     * @param array $staticContextDefaults
     */
    public function setContextOverrides(array $staticContextDefaults): void {
        $this->staticContextDefaults = $staticContextDefaults;
    }

    /**
     * Clean sensitive data out of the log context.
     *
     * @param array $context The context to clean.
     */
    private function obscureContext(array &$context): void {
        array_walk_recursive($context, function (&$value, $key) {
            foreach ($this->obscureKeys as $pattern) {
                if (fnmatch($pattern, strtolower($key))) {
                    $value = '***';
                }
            }
        });
    }
}
